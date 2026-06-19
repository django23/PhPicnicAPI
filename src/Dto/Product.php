<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * A single product (selling unit) parsed out of a search result.
 *
 * The modern search response nests products as "SELLING_UNIT_TILE" nodes whose
 * field names are camelCase; older payloads used snake_case. Both are accepted,
 * and anything not mapped here is available via {@see $raw}.
 */
final readonly class Product
{
    use HydratesFromArray;

    /**
     * @param int|null     $price        price in cents
     * @param int|null     $displayPrice display price in cents (may differ from $price on promo)
     * @param array<mixed> $raw          the full selling-unit payload
     */
    public function __construct(
        public ?string $id,
        public ?string $name,
        public ?int $price,
        public ?int $displayPrice,
        public ?string $unitQuantity,
        public ?string $imageId,
        public ?string $soleArticleId,
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
            name: self::str($data, 'name'),
            price: self::int($data, 'price'),
            displayPrice: self::int($data, 'displayPrice') ?? self::int($data, 'display_price'),
            unitQuantity: self::str($data, 'unitQuantity') ?? self::str($data, 'unit_quantity'),
            imageId: self::str($data, 'imageId') ?? self::str($data, 'image_id'),
            soleArticleId: self::str($data, 'soleArticleId') ?? self::str($data, 'sole_article_id'),
            raw: $data,
        );
    }
}
