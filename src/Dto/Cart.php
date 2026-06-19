<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * The shopping cart ("ORDER"). Returned by every cart-mutating call.
 */
final readonly class Cart
{
    use HydratesFromArray;

    /**
     * @param list<CartItem> $items
     * @param int|null       $totalPrice total cart price in cents
     * @param array<mixed>   $raw
     */
    public function __construct(
        public ?string $id,
        public array $items,
        public ?int $totalCount,
        public ?int $totalPrice,
        public array $raw,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $items = [];
        foreach (self::arr($data, 'items') as $item) {
            if (is_array($item)) {
                $items[] = CartItem::fromArray($item);
            }
        }

        return new self(
            id: self::str($data, 'id'),
            items: $items,
            totalCount: self::int($data, 'total_count'),
            totalPrice: self::int($data, 'total_price'),
            raw: $data,
        );
    }
}
