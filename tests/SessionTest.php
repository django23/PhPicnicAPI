<?php

declare(strict_types=1);

namespace PhPicnic\Tests;

use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PhPicnic\Credentials;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\Exception\InvalidCredentialsException;
use PhPicnic\Exception\PicnicApiException;
use PhPicnic\Exception\TwoFactorException;
use PhPicnic\Exception\TwoFactorRequiredException;
use PhPicnic\HttpTransport;
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

    private function makeSession(?string $cachedAuthToken = null): Session
    {
        return new Session(
            new PicnicConfig(),
            new HttpTransport($this->http, $this->psr17, $this->psr17),
            $cachedAuthToken,
        );
    }

    private function credentials(): Credentials
    {
        return new Credentials('user@example.com', 'secret');
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
        $this->http->addResponse(new Response(200)->withHeader('x-picnic-auth', 'tok-123'));

        $session = $this->makeSession();
        $session->login($this->credentials());

        self::assertTrue($session->isAuthenticated());
        self::assertSame('tok-123', $session->authToken());

        $request = $this->http->getRequests()[0];
        self::assertSame('30100;1.206.1-#15408', $request->getHeaderLine('x-picnic-agent'));
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($body);
        self::assertSame(md5('secret'), $body['secret']);
        self::assertSame(30100, $body['client_id']);
    }

    public function testLoginWithoutTokenThrowsAuthenticationException(): void
    {
        $this->http->addResponse(new Response(200));

        $this->expectException(InvalidCredentialsException::class);
        $this->makeSession()->login($this->credentials());
    }

    public function testLoginWithAuthErrorBodyThrows(): void
    {
        $this->http->addResponse($this->json(['error' => ['code' => 'AUTH_INVALID_CRED', 'message' => 'Wrong']]));

        try {
            $this->makeSession()->login($this->credentials());
            self::fail('Expected InvalidCredentialsException.');
        } catch (InvalidCredentialsException $invalidCredentialsException) {
            self::assertSame('Wrong', $invalidCredentialsException->getMessage());
        }
    }

    public function testLoginRequiring2faThrowsTwoFactorRequired(): void
    {
        $this->http->addResponse($this->json(['second_factor_authentication_required' => true]));

        try {
            $this->makeSession()->login($this->credentials());
            self::fail('Expected TwoFactorRequiredException.');
        } catch (TwoFactorRequiredException $twoFactorRequiredException) {
            self::assertTrue($twoFactorRequiredException->response['second_factor_authentication_required']);
        }
    }

    public function testAuthTokenRotatesOnEveryResponse(): void
    {
        $session = $this->makeSession('preset-token');
        $this->http->addResponse(new Response(200, [], '{}')->withHeader('x-picnic-auth', 'rotated'));

        $session->get('/user');

        self::assertSame('preset-token', $this->http->getRequests()[0]->getHeaderLine('x-picnic-auth'));
        self::assertSame('rotated', $session->authToken());
    }

    public function testAuthErrorInBodyOnRegularRequestThrows(): void
    {
        $session = $this->makeSession('tok');
        $this->http->addResponse($this->json(['error' => ['code' => 'AUTH_ERROR', 'message' => 'Expired']]));

        $this->expectException(InvalidCredentialsException::class);
        $session->get('/user');
    }

    public function testNon2xxResponseThrowsWithStatusAndBody(): void
    {
        $session = $this->makeSession('tok');
        $this->http->addResponse(new Response(403, [], 'forbidden'));

        try {
            $session->get('/user');
            self::fail('Expected PicnicApiException.');
        } catch (PicnicApiException $picnicApiException) {
            self::assertSame(403, $picnicApiException->statusCode);
            self::assertSame('forbidden', $picnicApiException->responseBody);
        }
    }

    public function testTwoFactorSucceedsOnEmptyResponse(): void
    {
        $session = $this->makeSession('tok');
        $this->http->addResponse(new Response(204));

        $session->twoFactor(ApiEndpoint::TWO_FACTOR_VERIFY, ['otp' => '123456']);

        self::assertSame(['otp' => '123456'], json_decode((string) $this->http->getRequests()[0]->getBody(), true));
    }

    public function testTwoFactorErrorBodyThrows(): void
    {
        $session = $this->makeSession('tok');
        $this->http->addResponse($this->json(['error' => ['code' => 'INVALID_OTP', 'message' => 'Bad code']]));

        try {
            $session->twoFactor(ApiEndpoint::TWO_FACTOR_VERIFY, ['otp' => '000000']);
            self::fail('Expected TwoFactorException.');
        } catch (TwoFactorException $twoFactorException) {
            self::assertSame('INVALID_OTP', $twoFactorException->errorCode);
            self::assertSame('Bad code', $twoFactorException->getMessage());
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
