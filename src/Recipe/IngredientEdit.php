<?php

declare(strict_types=1);

namespace PhPicnic\Recipe;

use PhPicnic\Enum\ComponentSwapType;
use PhPicnic\Exception\InvalidConfigurationException;

/**
 * New quantities for one ingredient of a user-defined recipe.
 */
final readonly class IngredientEdit
{
    /**
     * @throws InvalidConfigurationException on an empty component id or fewer than 1 portion
     */
    public function __construct(
        public string $componentId,
        public SellingUnitQuantities $quantities,
        public int $portions = 4,
    ) {
        if ($componentId === '') {
            throw new InvalidConfigurationException('The component id must not be empty.');
        }

        if ($portions < 1) {
            throw new InvalidConfigurationException('The portions must be at least 1.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(string $sellingGroupId, ?ComponentSwapType $swapType = null): array
    {
        // Picnic expects the portions as a string here.
        $payload = [
            'requested_sellable_portions' => (string) $this->portions,
            'selling_group_component_id' => $this->componentId,
            'selling_group_id' => $sellingGroupId,
            'selling_unit_quantity_by_id' => $this->quantities->byId,
        ];
        if ($swapType instanceof ComponentSwapType) {
            $payload['swapType'] = $swapType->value;
        }

        return $payload;
    }
}
