<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * The minimum order value for the currently selected delivery slot.
 */
final readonly class MinimumOrderValue
{
    /**
     * @param array<mixed> $raw
     */
    public function __construct(
        public string $slotId,
        public ?int $minimumOrderValueInCents,
        public array $raw,
    ) {
    }

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            slotId: PayloadReader::readRequiredString($payload, 'slot_id'),
            minimumOrderValueInCents: PayloadReader::readInt($payload, 'minimum_order_value'),
            raw: $payload,
        );
    }
}
