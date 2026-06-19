<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * An available delivery window.
 */
final readonly class DeliverySlot
{
    use HydratesFromArray;

    /**
     * @param array<mixed> $raw
     */
    public function __construct(
        public ?string $slotId,
        public ?string $windowStart,
        public ?string $windowEnd,
        public ?string $cutOffTime,
        public ?bool $isAvailable,
        public array $raw,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            slotId: self::str($data, 'slot_id'),
            windowStart: self::str($data, 'window_start'),
            windowEnd: self::str($data, 'window_end'),
            cutOffTime: self::str($data, 'cut_off_time'),
            isAvailable: self::bool($data, 'is_available'),
            raw: $data,
        );
    }
}
