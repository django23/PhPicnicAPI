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
     * @param int|null       $modificationTimestamp the "mts" a checkout start must echo back
     * @param array<mixed>   $raw
     */
    public function __construct(
        public string $id,
        public array $items,
        public ?int $totalCount,
        public ?int $totalPrice,
        public ?int $modificationTimestamp,
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
            items: PayloadReader::hydrateList(PayloadReader::readArray($payload, 'items'), CartItem::fromArray(...)),
            totalCount: PayloadReader::readInt($payload, 'total_count'),
            totalPrice: PayloadReader::readInt($payload, 'total_price'),
            modificationTimestamp: PayloadReader::readInt($payload, 'mts'),
            raw: $payload,
        );
    }
}
