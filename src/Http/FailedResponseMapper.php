<?php

declare(strict_types=1);

namespace PhPicnic\Http;

use PhPicnic\ApiErrorBody;
use PhPicnic\Exception\CheckoutIssueException;
use PhPicnic\Exception\InvalidCredentialsException;
use PhPicnic\Exception\PicnicApiException;
use PhPicnic\Exception\TwoFactorRequiredException;
use Psr\Http\Message\ResponseInterface;

/**
 * Maps a non-2xx response to the exception that names its cause. Picnic explains
 * most failures in the body, so the body is read before falling back to a generic HTTP error.
 */
final readonly class FailedResponseMapper
{
    public function exceptionFor(ResponseInterface $response, OutgoingRequest $request): PicnicApiException|CheckoutIssueException|TwoFactorRequiredException|InvalidCredentialsException
    {
        $responseBody = (string) $response->getBody();
        $decodedBody = json_decode($responseBody, true);
        $error = ApiErrorBody::fromResponseBody(is_array($decodedBody) ? $decodedBody : []);

        if ($error->isCheckoutIssue()) {
            return new CheckoutIssueException(
                $error->message ?? 'The cart has issues that block the checkout.',
                is_string($error->details['type'] ?? null) ? $error->details['type'] : null,
                ($error->details['blocking'] ?? false) === true,
                is_string($error->details['resolve_key'] ?? null) ? $error->details['resolve_key'] : null,
                $error->details,
            );
        }

        if ($error->isTwoFactorRequired()) {
            return new TwoFactorRequiredException($error->message ?? 'Two-factor authentication required.');
        }

        if ($error->isAuthError()) {
            return new InvalidCredentialsException($error->message ?? 'Picnic authentication error.');
        }

        return new PicnicApiException(
            sprintf('Picnic API returned HTTP %d for "%s".', $response->getStatusCode(), $request->label),
            $response->getStatusCode(),
            $responseBody,
            null,
            $request->method,
        );
    }
}
