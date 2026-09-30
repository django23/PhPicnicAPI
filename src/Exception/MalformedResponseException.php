<?php

declare(strict_types=1);

namespace PhPicnic\Exception;

/**
 * Thrown when a Picnic response lacks a field the library cannot work without,
 * such as the id of a cart or delivery.
 */
final class MalformedResponseException extends AbstractPicnicException
{
    /**
     * @param array<mixed> $payload the response part that was missing the field
     */
    public function __construct(
        string $message,
        public readonly array $payload = [],
    ) {
        parent::__construct($message);
    }
}
