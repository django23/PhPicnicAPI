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
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: PayloadReader::readString($payload, 'id'),
            name: PayloadReader::readString($payload, 'name'),
            price: PayloadReader::readInt($payload, 'price'),
            displayPrice: PayloadReader::readInt($payload, 'displayPrice') ?? PayloadReader::readInt($payload, 'display_price'),
            unitQuantity: PayloadReader::readString($payload, 'unitQuantity') ?? PayloadReader::readString($payload, 'unit_quantity'),
            imageId: PayloadReader::readString($payload, 'imageId') ?? PayloadReader::readString($payload, 'image_id'),
            soleArticleId: PayloadReader::readString($payload, 'soleArticleId') ?? PayloadReader::readString($payload, 'sole_article_id'),
            raw: $payload,
        );
    }
}
