<?php

declare(strict_types=1);

namespace PhPicnic\Http;

use PhPicnic\ApiErrorBody;
use PhPicnic\Exception\InvalidCredentialsException;
use PhPicnic\Exception\PicnicApiException;
use PhPicnic\Exception\TwoFactorException;
use PhPicnic\Exception\TwoFactorRequiredException;
use PhPicnic\JsonResponseDecoder;
use Psr\Http\Message\ResponseInterface;

/**
 * Turns the auth failures Picnic hides inside HTTP 200 bodies into exceptions:
 * an AUTH_ERROR / AUTH_INVALID_CRED code, the login 2FA flag, and the 2FA
 * generate/verify answers (HTTP 204 or an empty body on success).
 */
final readonly class AuthResponseGuard
{
    /**
     * @param array<mixed> $decodedBody
     *
     * @throws InvalidCredentialsException
     */
    public function assertNoAuthError(array $decodedBody): void
    {
        $error = ApiErrorBody::fromResponseBody($decodedBody);

        if ($error->isAuthError()) {
            throw new InvalidCredentialsException($error->message ?? 'Picnic authentication error.');
        }
    }

    /**
     * @param array<mixed> $loginBody
     *
     * @throws TwoFactorRequiredException
     * @throws InvalidCredentialsException
     */
    public function assertLoginAccepted(array $loginBody): void
    {
        if (($loginBody['second_factor_authentication_required'] ?? false) === true) {
            throw new TwoFactorRequiredException(
                ApiErrorBody::fromResponseBody($loginBody)->message ?? 'Two-factor authentication required.',
                $loginBody,
            );
        }

        $this->assertNoAuthError($loginBody);
    }

    /**
     * @throws TwoFactorException
     * @throws InvalidCredentialsException
     * @throws PicnicApiException
     */
    public function assertTwoFactorAccepted(ResponseInterface $response, string $path): void
    {
        if ($response->getStatusCode() === 204 || (string) $response->getBody() === '') {
            return;
        }

        $decodedBody = JsonResponseDecoder::decode($response, $path);
        $this->assertNoAuthError($decodedBody);
        $error = ApiErrorBody::fromResponseBody($decodedBody);

        if ($error->code !== null) {
            throw new TwoFactorException(
                $error->message ?? 'Two-factor authentication failed.',
                $error->code,
            );
        }
    }
}
