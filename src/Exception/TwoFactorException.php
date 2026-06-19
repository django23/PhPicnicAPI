<?php

declare(strict_types=1);

namespace PhPicnic\Exception;

/**
 * Thrown when generating or verifying a two-factor code fails (e.g. invalid OTP
 * or unsupported channel).
 */
final class TwoFactorException extends AuthenticationException
{
    public function __construct(
        string $message = 'Two-factor authentication failed.',
        public readonly ?string $errorCode = null,
    ) {
        parent::__construct($message);
    }
}
