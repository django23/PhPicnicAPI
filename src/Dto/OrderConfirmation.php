<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * The confirmed order. The chosen delivery slot is in $raw under "delivery_slot".
 */
final readonly class OrderConfirmation
{
    /**
     * @param array<mixed> $raw
     */
    public function __construct(
        public string $orderId,
        public array $raw,
    ) {
    }

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            orderId: PayloadReader::readRequiredString($payload, 'order_id'),
            raw: $payload,
        );
    }
}
