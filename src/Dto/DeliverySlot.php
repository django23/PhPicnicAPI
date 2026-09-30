<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * An available delivery window.
 */
final readonly class DeliverySlot
{
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
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            slotId: PayloadReader::readString($payload, 'slot_id'),
            windowStart: PayloadReader::readString($payload, 'window_start'),
            windowEnd: PayloadReader::readString($payload, 'window_end'),
            cutOffTime: PayloadReader::readString($payload, 'cut_off_time'),
            isAvailable: PayloadReader::readBool($payload, 'is_available'),
            raw: $payload,
        );
    }

    /**
     * @param array<mixed> $items
     *
     * @return list<self>
     */
    public static function fromList(array $items): array
    {
        $hydratedItems = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $hydratedItems[] = self::fromArray($item);
            }
        }

        return $hydratedItems;
    }
}
