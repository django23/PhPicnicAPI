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

    public function __construct(
        public ?string $code,
        public ?string $message,
    ) {
    }

    /**
     * @param array<mixed> $responseBody
     */
    public static function fromResponseBody(array $responseBody): self
    {
        $error = $responseBody['error'] ?? null;
        if (! is_array($error)) {
            return new self(null, null);
        }

        $code = $error['code'] ?? null;
        $message = $error['message'] ?? null;

        return new self(
            is_string($code) ? $code : null,
            is_string($message) ? $message : null,
        );
    }

    public function isAuthError(): bool
    {
        return $this->code !== null && in_array($this->code, self::AUTH_ERROR_CODES, true);
    }
}
