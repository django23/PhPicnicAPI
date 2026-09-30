<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * The shopping cart ("ORDER"). Returned by every cart-mutating call.
 */
final readonly class Cart
{
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
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $items = [];
        foreach (PayloadReader::readArray($payload, 'items') as $item) {
            if (is_array($item)) {
                $items[] = CartItem::fromArray($item);
            }
        }

        return new self(
            id: PayloadReader::readString($payload, 'id'),
            items: $items,
            totalCount: PayloadReader::readInt($payload, 'total_count'),
            totalPrice: PayloadReader::readInt($payload, 'total_price'),
            raw: $payload,
        );
    }
}
