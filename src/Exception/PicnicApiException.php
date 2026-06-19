<?php

declare(strict_types=1);

namespace PhPicnic\Exception;

use Throwable;

/**
 * Thrown when the Picnic API responds with a non-successful HTTP status.
 */
final class PicnicApiException extends PicnicException
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly string $responseBody = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
