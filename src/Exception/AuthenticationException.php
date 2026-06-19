<?php

declare(strict_types=1);

namespace PhPicnic\Exception;

/**
 * Thrown when logging in fails (bad credentials, missing auth token, etc.).
 *
 * Specialized by {@see TwoFactorRequiredException} and {@see TwoFactorException}.
 */
class AuthenticationException extends PicnicException
{
}
