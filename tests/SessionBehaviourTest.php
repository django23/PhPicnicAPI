<?php

declare(strict_types=1);

namespace PhPicnic\Tests;

use Http\Mock\Client as MockClient;
use InvalidArgumentException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PhPicnic\Auth\InMemoryAuthTokenStore;
use PhPicnic\ClientIdentity;
use PhPicnic\Enum\AppProfile;
use PhPicnic\Exception\CheckoutIssueException;
use PhPicnic\Exception\InvalidCredentialsException;
use PhPicnic\Exception\PicnicApiException;
use PhPicnic\Exception\TwoFactorRequiredException;
use PhPicnic\Exception\UnexpectedResponseFormatException;
use PhPicnic\HttpTransport;
use PhPicnic\PicnicConfig;
use PhPicnic\Session;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

final class SessionBehaviourTest extends TestCase
{
    private MockClient $http;

    private Psr17Factory $psr17;

    protected function setUp(): void
    {
        $this->http = new MockClient();
        $this->psr17 = new Psr17Factory();
    }

    private function makeSession(?PicnicConfig $config = null, ?string $cachedAuthToken = 'tok'): Session
    {
        return new Session(
            $config ?? new PicnicConfig(),
            new HttpTransport($this->http, $this->psr17, $this->psr17),
            $cachedAuthToken,
        );
    }

    private function sentRequest(int $index = 0): RequestInterface
    {
        return $this->http->getRequests()[$index];
    }

    /**
     * @param array<mixed> $body
     */
    private function json(array $body, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($body, JSON_THROW_ON_ERROR));
    }

    public function testRotatedTokenIsHandedToTheTokenStore(): void
    {
        $store = new InMemoryAuthTokenStore();
        $this->http->addResponse(new Response(200, [], '{}')->withHeader('x-picnic-auth', 'rotated'));

        $this->makeSession(new PicnicConfig(tokenStore: $store), 'old')->get('/user');

        self::assertSame('rotated', $store->load());
    }

    public function testSessionStartsFromTheTokenInTheStore(): void
    {
        $store = new InMemoryAuthTokenStore();
        $store->save('stored');

        $this->http->addResponse($this->json([]));

        $this->makeSession(new PicnicConfig(tokenStore: $store), null)->get('/user');

        self::assertSame('stored', $this->sentRequest()->getHeaderLine('x-picnic-auth'));
    }

    public function testIdentityOverrideChangesOnlyThatRequest(): void
    {
        $this->http->addResponse($this->json([]));
        $this->http->addResponse($this->json([]));

        $session = $this->makeSession();

        $session->get('/pages/profile-root', ClientIdentity::forProfile(AppProfile::V1_206_1));
        $session->get('/user');

        self::assertSame('30100;1.206.1-#15408', $this->sentRequest(0)->getHeaderLine('x-picnic-agent'));
        self::assertSame('30100;1.246.1-15599;', $this->sentRequest(1)->getHeaderLine('x-picnic-agent'));
    }

    public function testEveryApiRequestCarriesTheAgentDeviceAndLanguageHeaders(): void
    {
        $this->http->addResponse($this->json([]));

        $this->makeSession()->get('/user');

        $request = $this->sentRequest();
        self::assertSame('30100;1.246.1-15599;', $request->getHeaderLine('x-picnic-agent'));
        self::assertSame('598F770380CA54B6', $request->getHeaderLine('x-picnic-did'));
        self::assertSame('nl', $request->getHeaderLine('Accept-Language'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonRelativePaths(): iterable
    {
        yield 'absolute URL' => ['https://evil.example/steal'];
        yield 'protocol-relative' => ['//evil.example/steal'];
        yield 'no leading slash' => ['user'];
        yield 'embedded scheme' => ['/redirect?to=https://evil.example'];
    }

    #[DataProvider('nonRelativePaths')]
    public function testRefusesPathsThatCouldSendTheTokenElsewhere(string $path): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeSession()->get($path);
    }

    public function testNullPayloadSendsNoBody(): void
    {
        $this->http->addResponse($this->json([]));

        $this->makeSession()->post('/user/logout', null);

        self::assertSame('', (string) $this->sentRequest()->getBody());
    }

    public function testPutSendsTheJsonPayload(): void
    {
        $this->http->addResponse($this->json([]));

        $this->makeSession()->put('/reminders', [['day_of_week' => 'MONDAY']]);

        self::assertSame('PUT', $this->sentRequest()->getMethod());
        self::assertSame('[{"day_of_week":"MONDAY"}]', (string) $this->sentRequest()->getBody());
    }

    public function testPostRawSendsBytesWithTheGivenContentType(): void
    {
        $this->http->addResponse($this->json(['image_id' => 'i1']));

        $result = $this->makeSession()->postRaw('/user-defined-sellable/r1', 'PNGBYTES', 'image/png');

        self::assertSame('PNGBYTES', (string) $this->sentRequest()->getBody());
        self::assertSame('image/png', $this->sentRequest()->getHeaderLine('Content-Type'));
        self::assertSame('i1', $result['image_id']);
    }

    public function testRscResponseWhereJsonIsExpectedThrowsATypedException(): void
    {
        $this->http->addResponse(new Response(200, ['Content-Type' => 'text/x-component'], "0:[\"$\"]\n"));

        $this->expectException(UnexpectedResponseFormatException::class);

        $this->makeSession()->get('/pages/profile-root');
    }

    public function testGetTextReturnsTheRawBody(): void
    {
        $this->http->addResponse(new Response(200, ['Content-Type' => 'text/x-component'], "0:[]\n"));

        self::assertSame("0:[]\n", $this->makeSession()->getText('/pages/profile-root'));
    }

    public function testCheckoutIssueBodyBecomesATypedException(): void
    {
        $this->http->addResponse($this->json([
            'error' => [
                'code' => 'CART_HAS_ISSUES',
                'message' => 'Age check',
                'details' => ['type' => 'LEGACY_ALCOHOL_AGE_VERIFICATION_REQUIRED', 'blocking' => false, 'resolve_key' => 'rk-1'],
            ],
        ], 400));

        try {
            $this->makeSession()->post('/cart/checkout/start', ['mts' => 1]);
            self::fail('Expected CheckoutIssueException.');
        } catch (CheckoutIssueException $checkoutIssueException) {
            self::assertTrue($checkoutIssueException->isAgeVerificationRequired());
            self::assertFalse($checkoutIssueException->isBlocking);
            self::assertSame('rk-1', $checkoutIssueException->resolveKey);
        }
    }

    public function testUnverifiedTwoFactorOnAnyEndpointIsMappedEvenOnHttp403(): void
    {
        $this->http->addResponse($this->json(['error' => ['code' => 'TWO_FACTOR_AUTHENTICATION_REQUIRED', 'message' => 'Verify']], 403));

        $this->expectException(TwoFactorRequiredException::class);

        $this->makeSession()->get('/cart');
    }

    public function testAuthErrorOnAnErrorStatusIsAnInvalidCredentialsException(): void
    {
        $this->http->addResponse($this->json(['error' => ['code' => 'AUTH_ERROR', 'message' => 'Expired']], 401));

        $this->expectException(InvalidCredentialsException::class);

        $this->makeSession()->get('/cart');
    }

    public function testFailedMutationIsFlaggedAsPossiblyChangingState(): void
    {
        $this->http->addResponse($this->json([], 500));

        try {
            $this->makeSession()->post('/cart/add_product', ['product_id' => 's1', 'count' => 1]);
            self::fail('Expected PicnicApiException.');
        } catch (PicnicApiException $picnicApiException) {
            self::assertTrue($picnicApiException->mayHaveChangedState());
            self::assertFalse($picnicApiException->isNetworkFailure());
        }
    }

    public function testFailedReadIsNotFlaggedAsChangingState(): void
    {
        $this->http->addResponse($this->json([], 500));

        try {
            $this->makeSession()->get('/cart');
            self::fail('Expected PicnicApiException.');
        } catch (PicnicApiException $picnicApiException) {
            self::assertFalse($picnicApiException->mayHaveChangedState());
        }
    }

    public function testNetworkFailureHasStatusCodeZero(): void
    {
        $this->http->addException(new class ('down') extends RuntimeException implements NetworkExceptionInterface {
            public function getRequest(): RequestInterface
            {
                return new Request('GET', '/');
            }
        });

        try {
            $this->makeSession()->get('/cart');
            self::fail('Expected PicnicApiException.');
        } catch (PicnicApiException $picnicApiException) {
            self::assertTrue($picnicApiException->isNetworkFailure());
        }
    }

    public function testPublicApiRequestSendsNoTokenAndNoAgent(): void
    {
        $this->http->addResponse($this->json(['contact_details' => []]));

        $this->makeSession()->getPublicApi('/cs-contact-info');

        $request = $this->sentRequest();
        self::assertSame('https://storefront-prod.nl.picnicinternational.com/public-api/15/cs-contact-info', (string) $request->getUri());
        self::assertSame('NL', $request->getHeaderLine('picnic-country'));
        self::assertFalse($request->hasHeader('x-picnic-auth'));
        self::assertFalse($request->hasHeader('x-picnic-agent'));
    }

    public function testStaticFileRequestSendsNoToken(): void
    {
        $this->http->addResponse(new Response(200, [], 'PNG'));

        $bytes = $this->makeSession()->getStaticFile('/static/images/i1/small.png');

        self::assertSame('PNG', $bytes);
        self::assertFalse($this->sentRequest()->hasHeader('x-picnic-auth'));
    }

    public function testPublicRedirectRequestRefusesOtherHosts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeSession()->sendPublicRequest('https://evil.example/qr/gtin/123');
    }

    public function testPublicRedirectRequestRefusesPlainHttp(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeSession()->sendPublicRequest('http://picnic.app/nl/qr/gtin/123');
    }

    public function testPublicRedirectRequestNeverSendsTheAuthToken(): void
    {
        $this->http->addResponse(new Response(302, ['Location' => 'https://picnic.app/nl/x']));

        $response = $this->makeSession()->sendPublicRequest('https://picnic.app/nl/qr/gtin/123');

        self::assertSame(302, $response->getStatusCode());
        self::assertFalse($this->sentRequest()->hasHeader('x-picnic-auth'));
        self::assertTrue($this->sentRequest()->hasHeader('x-picnic-agent'));
    }
}
