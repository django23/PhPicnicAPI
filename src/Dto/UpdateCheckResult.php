<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * Whether Picnic wants the app updated, and address autocomplete settings.
 */
final readonly class UpdateCheckResult
{
    /**
     * @param array<mixed> $raw
     */
    public function __construct(
        public bool $isUpdateRequired,
        public array $raw,
    ) {
    }

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            isUpdateRequired: PayloadReader::readBool($payload, 'update_required') ?? false,
            raw: $payload,
        );
    }
}
