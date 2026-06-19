<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * A delivery (past, current, or scheduled).
 */
final readonly class Delivery
{
    use HydratesFromArray;

    /**
     * @param list<string> $orderIds
     * @param array<mixed> $raw
     */
    public function __construct(
        public ?string $deliveryId,
        public ?string $status,
        public ?string $slotId,
        public ?string $eta2Start,
        public ?string $eta2End,
        public array $orderIds,
        public array $raw,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $slot = self::arr($data, 'slot');
        $eta2 = self::arr($data, 'eta2');

        $orderIds = [];
        foreach (self::arr($data, 'orders') as $order) {
            if (is_array($order) && isset($order['id']) && is_string($order['id'])) {
                $orderIds[] = $order['id'];
            }
        }

        return new self(
            deliveryId: self::str($data, 'delivery_id') ?? self::str($data, 'id'),
            status: self::str($data, 'status'),
            slotId: self::str($slot, 'slot_id') ?? self::str($data, 'slot_id'),
            eta2Start: self::str($eta2, 'start'),
            eta2End: self::str($eta2, 'end'),
            orderIds: $orderIds,
            raw: $data,
        );
    }
}
