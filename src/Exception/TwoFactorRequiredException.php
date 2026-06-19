<?php

declare(strict_types=1);

namespace PhPicnic\Exception;

/**
 * Thrown when login succeeds with credentials but the account requires a second
 * factor. Trigger delivery with {@see \PhPicnic\Client::generate2FA()} and finish
 * with {@see \PhPicnic\Client::verify2FA()}.
 */
final class TwoFactorRequiredException extends AuthenticationException
{
    /**
     * @param array<mixed> $response the decoded login response
     */
    public function __construct(
        string $message = 'Two-factor authentication required.',
        public readonly array $response = [],
    ) {
        parent::__construct($message);
    }
}
