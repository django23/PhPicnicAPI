<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * A line in the shopping cart. Picnic groups order lines; this maps the common
 * fields and keeps the full node in {@see $raw}.
 */
final readonly class CartItem
{
    /**
     * @param int|null     $price total price for this line in cents
     * @param array<mixed> $raw
     */
    public function __construct(
        public string $id,
        public ?int $count,
        public ?int $price,
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
            count: PayloadReader::readInt($payload, 'count'),
            price: PayloadReader::readFirstInt($payload, 'price', 'display_price'),
            raw: $payload,
        );
    }
}
