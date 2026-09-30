<?php

declare(strict_types=1);

namespace PhPicnic\Exception;

/**
 * Thrown when Picnic answers in a different format than the endpoint promised,
 * typically a React Server Components page where JSON was expected (or the
 * other way round). The format depends on the page and on the x-picnic-agent
 * version; see {@see \PhPicnic\Enum\AppProfile}.
 */
final class UnexpectedResponseFormatException extends AbstractPicnicException
{
    public function __construct(
        string $message,
        public readonly string $path,
        public readonly string $contentType,
        public readonly string $responseBody = '',
    ) {
        parent::__construct($message);
    }
}
