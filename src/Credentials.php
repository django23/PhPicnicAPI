<?php

declare(strict_types=1);

namespace PhPicnic;

/**
 * Account credentials, plus an optional auth token cached from an earlier
 * session so the client can skip logging in again.
 */
final readonly class Credentials
{
    public function __construct(
        public string $username,
        public string $password,
        public ?string $cachedAuthToken = null,
    ) {
    }
}
