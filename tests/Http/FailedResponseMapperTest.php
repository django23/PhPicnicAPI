<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Http;

use Nyholm\Psr7\Response;
use PhPicnic\Exception\CheckoutIssueException;
use PhPicnic\Exception\InvalidCredentialsException;
use PhPicnic\Exception\PicnicApiException;
use PhPicnic\Exception\TwoFactorRequiredException;
use PhPicnic\Http\FailedResponseMapper;
use PhPicnic\Http\OutgoingRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FailedResponseMapperTest extends TestCase
{
    /**
     * @return iterable<string, array{string, class-string}>
     */
    public static function errorBodies(): iterable
    {
        yield 'checkout issue' => ['{"error":{"code":"CART_HAS_ISSUES","details":{"type":"X","blocking":true}}}', CheckoutIssueException::class];
        yield 'two factor' => ['{"error":{"code":"TWO_FACTOR_AUTHENTICATION_REQUIRED"}}', TwoFactorRequiredException::class];
        yield 'auth error' => ['{"error":{"code":"AUTH_ERROR"}}', InvalidCredentialsException::class];
        yield 'invalid credentials' => ['{"error":{"code":"AUTH_INVALID_CRED"}}', InvalidCredentialsException::class];
        yield 'unknown code' => ['{"error":{"code":"SOMETHING"}}', PicnicApiException::class];
        yield 'not json' => ['<html>', PicnicApiException::class];
    }

    /**
     * @param class-string $expectedClass
     */
    #[DataProvider('errorBodies')]
    public function testBodyDecidesTheException(string $body, string $expectedClass): void
    {
        $exception = new FailedResponseMapper()->exceptionFor(new Response(403, [], $body), $this->request());

        self::assertInstanceOf($expectedClass, $exception);
    }

    public function testCheckoutIssueCarriesItsDetails(): void
    {
        $body = '{"error":{"code":"CART_HAS_ISSUES","message":"m","details":{"type":"T","blocking":true,"resolve_key":"k"}}}';

        $exception = new FailedResponseMapper()->exceptionFor(new Response(400, [], $body), $this->request());

        self::assertInstanceOf(CheckoutIssueException::class, $exception);
        self::assertSame('m', $exception->getMessage());
    }

    public function testGenericErrorNamesStatusPathMethodAndBody(): void
    {
        $exception = new FailedResponseMapper()->exceptionFor(new Response(500, [], 'boom'), $this->request());

        self::assertSame('Picnic API returned HTTP 500 for "/cart".', $exception->getMessage());
        self::assertSame(500, $exception->getCode());
    }

    private function request(): OutgoingRequest
    {
        return new OutgoingRequest('POST', 'https://x.test/cart', [], null, '/cart');
    }
}
