<?php

declare(strict_types=1);

namespace PhPicnic;

/**
 * The {"error": {"code": ..., "message": ...}} envelope Picnic puts in response
 * bodies, including HTTP 200 ones.
 */
final readonly class ApiErrorBody
{
    /** Error codes Picnic returns (inside an HTTP 200 body) for auth failures. */
    private const array AUTH_ERROR_CODES = ['AUTH_ERROR', 'AUTH_INVALID_CRED'];

    private const string TWO_FACTOR_REQUIRED_CODE = 'TWO_FACTOR_AUTHENTICATION_REQUIRED';

    private const string CHECKOUT_ISSUE_CODE = 'CART_HAS_ISSUES';

    /**
     * @param array<mixed> $details
     */
    public function __construct(
        public ?string $code,
        public ?string $message,
        public array $details = [],
    ) {
    }

    /**
     * Reads both {"error": {"code": ...}} and the flat {"code": ..., "details": ...} form.
     *
     * @param array<mixed> $responseBody
     */
    public static function fromResponseBody(array $responseBody): self
    {
        $error = $responseBody['error'] ?? null;
        $envelope = is_array($error) ? $error : $responseBody;

        $code = $envelope['code'] ?? null;
        $message = $envelope['message'] ?? null;
        $details = $envelope['details'] ?? $responseBody['details'] ?? [];

        return new self(
            is_string($code) ? $code : null,
            is_string($message) ? $message : null,
            is_array($details) ? $details : [],
        );
    }

    public function isAuthError(): bool
    {
        return $this->code !== null && in_array($this->code, self::AUTH_ERROR_CODES, true);
    }

    public function isTwoFactorRequired(): bool
    {
        return $this->code === self::TWO_FACTOR_REQUIRED_CODE;
    }

    public function isCheckoutIssue(): bool
    {
        return $this->code === self::CHECKOUT_ISSUE_CODE;
    }
}
