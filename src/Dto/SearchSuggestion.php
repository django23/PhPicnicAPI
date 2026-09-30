<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * One autocomplete suggestion for a search term.
 */
final readonly class SearchSuggestion
{
    /**
     * @param array<mixed> $raw
     */
    public function __construct(
        public string $id,
        public string $suggestion,
        public array $raw,
    ) {
    }

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: PayloadReader::readRequiredString($payload, 'id'),
            suggestion: PayloadReader::readRequiredString($payload, 'suggestion'),
            raw: $payload,
        );
    }
}
