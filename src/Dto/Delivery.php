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
        public string $deliveryId,
        public ?string $status,
        public ?string $slotId,
        public ?string $estimatedArrivalStart,
        public ?string $estimatedArrivalEnd,
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
        $estimatedArrival = PayloadReader::readArray($payload, 'eta2');

        return new self(
            deliveryId: PayloadReader::readRequiredString($payload, 'delivery_id', 'id'),
            status: PayloadReader::readString($payload, 'status'),
            slotId: PayloadReader::readString($slot, 'slot_id') ?? PayloadReader::readString($payload, 'slot_id'),
            estimatedArrivalStart: PayloadReader::readString($estimatedArrival, 'start'),
            estimatedArrivalEnd: PayloadReader::readString($estimatedArrival, 'end'),
            orderIds: self::readOrderIds($payload),
            raw: $payload,
        );
    }

    /**
     * @param array<mixed> $payload
     *
     * @return list<string>
     */
    private static function readOrderIds(array $payload): array
    {
        $orderIds = [];
        foreach (PayloadReader::readArray($payload, 'orders') as $order) {
            $orderId = is_array($order) ? PayloadReader::readString($order, 'id') : null;
            if ($orderId !== null) {
                $orderIds[] = $orderId;
            }
        }

        return $orderIds;
    }
}
