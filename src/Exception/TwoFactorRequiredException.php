<?php

declare(strict_types=1);

namespace PhPicnic\Exception;

/**
 * Thrown when login succeeds with credentials but the account requires a second
 * factor. Trigger delivery with {@see \PhPicnic\Client::requestTwoFactorCode()} and finish
 * with {@see \PhPicnic\Client::verifyTwoFactorCode()}.
 */
final class TwoFactorRequiredException extends AbstractAuthenticationException
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
