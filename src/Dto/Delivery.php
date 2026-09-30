<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * A delivery (past, current, or scheduled).
 */
final readonly class Delivery
{
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
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $slot = PayloadReader::readArray($payload, 'slot');
        $eta2 = PayloadReader::readArray($payload, 'eta2');

        $orderIds = [];
        foreach (PayloadReader::readArray($payload, 'orders') as $order) {
            if (is_array($order) && isset($order['id']) && is_string($order['id'])) {
                $orderIds[] = $order['id'];
            }
        }

        return new self(
            deliveryId: PayloadReader::readString($payload, 'delivery_id') ?? PayloadReader::readString($payload, 'id'),
            status: PayloadReader::readString($payload, 'status'),
            slotId: PayloadReader::readString($slot, 'slot_id') ?? PayloadReader::readString($payload, 'slot_id'),
            eta2Start: PayloadReader::readString($eta2, 'start'),
            eta2End: PayloadReader::readString($eta2, 'end'),
            orderIds: $orderIds,
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
