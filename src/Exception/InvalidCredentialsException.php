<?php

declare(strict_types=1);

namespace PhPicnic\Exception;

/**
 * Thrown when logging in fails: bad credentials, an auth error body from
 * Picnic, or no auth token in the login response.
 */
final class InvalidCredentialsException extends AbstractAuthenticationException
{
}
