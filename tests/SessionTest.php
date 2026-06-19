<?php

declare(strict_types=1);

namespace PhPicnic\Tests;

use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PhPicnic\Enum\CountryCode;
use PhPicnic\Exception\AuthenticationException;
use PhPicnic\Exception\PicnicApiException;
use PhPicnic\Exception\TwoFactorException;
use PhPicnic\Exception\TwoFactorRequiredException;
use PhPicnic\PicnicConfig;
use PhPicnic\Session;
use PHPUnit\Framework\TestCase;

final class SessionTest extends TestCase
{
    private MockClient $http;

    private Psr17Factory $psr17;

    protected function setUp(): void
    {
        $this->http = new MockClient();
        $this->psr17 = new Psr17Factory();
    }

    private function makeSession(?string $authToken = null): Session
    {
        return new Session(
            new PicnicConfig(CountryCode::NL, '15', $authToken),
            $this->http,
            $this->psr17,
            $this->psr17,
        );
    }

    /**
     * @param array<mixed> $body
     */
    private function json(array $body, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($body, JSON_THROW_ON_ERROR));
    }

    public function testLoginCapturesRotatingTokenAndSendsHashedSecret(): void
    {
        $this->http->addResponse((new Response(200))->withHeader('x-picnic-auth', 'tok-123'));

        $session = $this->makeSession();
        $session->login('user@example.com', 'secret');

        self::assertTrue($session->isAuthenticated());
        self::assertSame('tok-123', $session->authToken());

        $request = $this->http->getRequests()[0];
        self::assertSame('30100;1.206.1-#15408', $request->getHeaderLine('x-picnic-agent'));
        $body = json_decode((string) $request->getBody(), true);
        self::assertSame(md5('secret'), $body['secret']);
        self::assertSame(30100, $body['client_id']);
    }

    public function testLoginWithoutTokenThrowsAuthenticationException(): void
    {
        $this->http->addResponse(new Response(200));

        $this->expectException(AuthenticationException::class);
        $this->makeSession()->login('user@example.com', 'secret');
    }

    public function testLoginWithAuthErrorBodyThrows(): void
    {
        $this->http->addResponse($this->json(['error' => ['code' => 'AUTH_INVALID_CRED', 'message' => 'Wrong']]));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Wrong');
        $this->makeSession()->login('user@example.com', 'secret');
    }

    public function testLoginRequiring2faThrowsTwoFactorRequired(): void
    {
        $this->http->addResponse($this->json(['second_factor_authentication_required' => true]));

        try {
            $this->makeSession()->login('user@example.com', 'secret');
            self::fail('Expected TwoFactorRequiredException.');
        } catch (TwoFactorRequiredException $e) {
            self::assertTrue($e->response['second_factor_authentication_required']);
        }
    }

    public function testAuthTokenRotatesOnEveryResponse(): void
    {
        $session = $this->makeSession('preset-token');
        $this->http->addResponse((new Response(200, [], '{}'))->withHeader('x-picnic-auth', 'rotated'));

        $session->get('/user');

        self::assertSame('preset-token', $this->http->getRequests()[0]->getHeaderLine('x-picnic-auth'));
        self::assertSame('rotated', $session->authToken());
    }

    public function testAuthErrorInBodyOnRegularRequestThrows(): void
    {
        $session = $this->makeSession('tok');
        $this->http->addResponse($this->json(['error' => ['code' => 'AUTH_ERROR', 'message' => 'Expired']]));

        $this->expectException(AuthenticationException::class);
        $session->get('/user');
    }

    public function testNon2xxResponseThrowsWithStatusAndBody(): void
    {
        $session = $this->makeSession('tok');
        $this->http->addResponse(new Response(403, [], 'forbidden'));

        try {
            $session->get('/user');
            self::fail('Expected PicnicApiException.');
        } catch (PicnicApiException $e) {
            self::assertSame(403, $e->statusCode);
            self::assertSame('forbidden', $e->responseBody);
        }
    }

    public function testTwoFactorSucceedsOnEmptyResponse(): void
    {
        $session = $this->makeSession('tok');
        $this->http->addResponse(new Response(204));

        $session->twoFactor('/user/2fa/verify', ['otp' => '123456']);

        self::assertSame(['otp' => '123456'], json_decode((string) $this->http->getRequests()[0]->getBody(), true));
    }

    public function testTwoFactorErrorBodyThrows(): void
    {
        $session = $this->makeSession('tok');
        $this->http->addResponse($this->json(['error' => ['code' => 'INVALID_OTP', 'message' => 'Bad code']]));

        try {
            $session->twoFactor('/user/2fa/verify', ['otp' => '000000']);
            self::fail('Expected TwoFactorException.');
        } catch (TwoFactorException $e) {
            self::assertSame('INVALID_OTP', $e->errorCode);
            self::assertSame('Bad code', $e->getMessage());
        }
    }

    public function testEmptyBodyDecodesToEmptyArray(): void
    {
        $session = $this->makeSession('tok');
        $this->http->addResponse(new Response(200, [], ''));

        self::assertSame([], $session->get('/cart'));
    }

    public function testInvalidJsonThrows(): void
    {
        $session = $this->makeSession('tok');
        $this->http->addResponse(new Response(200, [], '{not json'));

        $this->expectException(PicnicApiException::class);
        $session->get('/cart');
    }
}
