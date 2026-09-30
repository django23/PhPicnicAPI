<?php

declare(strict_types=1);

namespace PhPicnic\Tests;

use PhPicnic\ApiErrorBody;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ApiErrorBodyTest extends TestCase
{
    public function testReadsCodeAndMessageFromTheErrorEnvelope(): void
    {
        $error = ApiErrorBody::fromResponseBody(['error' => ['code' => 'X', 'message' => 'Oops']]);

        self::assertSame('X', $error->code);
        self::assertSame('Oops', $error->message);
    }

    public function testBodyWithoutErrorEnvelopeHasNoCode(): void
    {
        $error = ApiErrorBody::fromResponseBody(['user_id' => 'u-1']);

        self::assertNull($error->code);
        self::assertFalse($error->isAuthError());
    }

    #[DataProvider('authErrorCodes')]
    public function testAuthErrorCodesAreRecognised(string $code, bool $expectedIsAuthError): void
    {
        $error = ApiErrorBody::fromResponseBody(['error' => ['code' => $code]]);

        self::assertSame($expectedIsAuthError, $error->isAuthError());
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function authErrorCodes(): iterable
    {
        yield 'auth error' => ['AUTH_ERROR', true];
        yield 'invalid credentials' => ['AUTH_INVALID_CRED', true];
        yield 'invalid otp' => ['INVALID_OTP', false];
    }
}
