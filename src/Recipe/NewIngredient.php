<?php

declare(strict_types=1);

namespace PhPicnic\Recipe;

use PhPicnic\Exception\InvalidConfigurationException;

/**
 * A product to append to a user-defined recipe.
 */
final readonly class NewIngredient
{
    /**
     * @throws InvalidConfigurationException on an empty selling unit id, a quantity below 1 or a negative order
     */
    public function __construct(
        public string $sellingUnitId,
        public int $quantity = 1,
        public int $order = 0,
    ) {
        if ($sellingUnitId === '') {
            throw new InvalidConfigurationException('The selling unit id must not be empty.');
        }

        if ($quantity < 1) {
            throw new InvalidConfigurationException('The ingredient quantity must be at least 1.');
        }

        if ($order < 0) {
            throw new InvalidConfigurationException('The ingredient order must not be negative.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(string $sellingGroupId, int $portions): array
    {
        // Picnic expects order and portions as strings here.
        return [
            'order' => (string) $this->order,
            'quantity' => $this->quantity,
            'requested_portions' => (string) $portions,
            'selling_group_id' => $sellingGroupId,
            'selling_unit_id' => $this->sellingUnitId,
        ];
    }
}
