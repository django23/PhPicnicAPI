<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * A line in the shopping cart. Picnic groups order lines; this maps the common
 * fields and keeps the full node in {@see $raw}.
 */
final readonly class CartItem
{
    use HydratesFromArray;

    /**
     * @param int|null     $price total price for this line in cents
     * @param array<mixed> $raw
     */
    public function __construct(
        public ?string $id,
        public ?int $count,
        public ?int $price,
        public array $raw,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: self::str($data, 'id'),
            count: self::int($data, 'count'),
            price: self::int($data, 'price') ?? self::int($data, 'display_price'),
            raw: $data,
        );
    }
}
