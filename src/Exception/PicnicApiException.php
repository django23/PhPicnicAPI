<?php

declare(strict_types=1);

namespace PhPicnic\Exception;

use Throwable;

/**
 * Thrown when the Picnic API responds with a non-successful HTTP status, or the
 * request never reached it (status code 0).
 *
 * Picnic applies cart changes and renders the cart in the same request, so a
 * failed POST or PUT may still have changed state. Check
 * {@see mayHaveChangedState()} and read the cart before retrying.
 */
final class PicnicApiException extends AbstractPicnicException
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly string $responseBody = '',
        ?Throwable $previous = null,
        public readonly string $requestMethod = 'GET',
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function isNetworkFailure(): bool
    {
        return $this->statusCode === 0;
    }

    public function mayHaveChangedState(): bool
    {
        return $this->requestMethod !== 'GET';
    }
}
