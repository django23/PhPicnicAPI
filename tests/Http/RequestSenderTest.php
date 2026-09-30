<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Http;

use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PhPicnic\Auth\InMemoryAuthTokenStore;
use PhPicnic\Exception\PicnicApiException;
use PhPicnic\Http\AuthTokenHolder;
use PhPicnic\Http\FailedResponseMapper;
use PhPicnic\Http\OutgoingRequest;
use PhPicnic\Http\RequestSender;
use PhPicnic\HttpTransport;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\NetworkExceptionInterface;
use RuntimeException;

final class RequestSenderTest extends TestCase
{
    private MockClient $http;

    private AuthTokenHolder $token;

    private RequestSender $sender;

    protected function setUp(): void
    {
        $this->http = new MockClient();
        $psr17 = new Psr17Factory();
        $this->token = new AuthTokenHolder(new InMemoryAuthTokenStore(), 'old');
        $this->sender = new RequestSender(new HttpTransport($this->http, $psr17, $psr17), new FailedResponseMapper(), $this->token);
    }

    public function testBuildsMethodUrlHeadersAndBody(): void
    {
        $this->http->addResponse(new Response(200));

        $this->sender->sendAuthenticated(new OutgoingRequest('PUT', 'https://x.test/a', ['X-One' => '1'], '{"a":1}', '/a'));

        $sent = $this->http->getRequests()[0];
        self::assertSame('PUT', $sent->getMethod());
        self::assertSame('https://x.test/a', (string) $sent->getUri());
        self::assertSame('1', $sent->getHeaderLine('X-One'));
        self::assertSame('{"a":1}', (string) $sent->getBody());
    }

    public function testAuthenticatedRotatesTheTokenEvenWhenTheStatusFails(): void
    {
        $this->http->addResponse(new Response(500, ['x-picnic-auth' => 'rotated']));

        try {
            $this->sender->sendAuthenticated($this->request());
            self::fail('Expected an exception.');
        } catch (PicnicApiException) {
            self::assertSame('rotated', $this->token->current());
        }
    }

    public function testUnauthenticatedNeverTouchesTheToken(): void
    {
        $this->http->addResponse(new Response(200, ['x-picnic-auth' => 'leaked']));

        $this->sender->sendUnauthenticated($this->request());

        self::assertSame('old', $this->token->current());
    }

    public function testUnauthenticatedFailsOnHttpError(): void
    {
        $this->http->addResponse(new Response(404));

        $this->expectException(PicnicApiException::class);

        $this->sender->sendUnauthenticated($this->request());
    }

    public function testWithoutStatusCheckReturnsRedirectsUntouched(): void
    {
        $this->http->addResponse(new Response(302, ['Location' => 'https://y.test', 'x-picnic-auth' => 'leaked']));

        $response = $this->sender->sendUnauthenticatedWithoutStatusCheck($this->request());

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('old', $this->token->current());
    }

    public function testNetworkFailureBecomesStatusZeroException(): void
    {
        $this->http->addException(new class ('down') extends RuntimeException implements NetworkExceptionInterface {
            public function getRequest(): Request
            {
                return new Request('GET', 'https://x.test');
            }
        });

        try {
            $this->sender->sendUnauthenticatedWithoutStatusCheck($this->request());
            self::fail('Expected an exception.');
        } catch (PicnicApiException $picnicApiException) {
            self::assertSame(0, $picnicApiException->getCode());
            self::assertStringContainsString('/a', $picnicApiException->getMessage());
        }
    }

    private function request(): OutgoingRequest
    {
        return new OutgoingRequest('GET', 'https://x.test/a', [], null, '/a');
    }
}
