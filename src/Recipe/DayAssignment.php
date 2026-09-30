<?php

declare(strict_types=1);

namespace PhPicnic\Recipe;

use PhPicnic\Enum\ComponentSwapType;
use PhPicnic\Exception\InvalidConfigurationException;

/**
 * The selected units of one ingredient, assigned to a day of the meal plan.
 */
final readonly class DayAssignment
{
    /**
     * @param SellingUnitQuantities $quantities only the selected selling units
     *
     * @throws InvalidConfigurationException on an empty component id or fewer than 1 portion
     */
    public function __construct(
        public string $componentId,
        public SellingUnitQuantities $quantities,
        public int $portions,
        public ComponentSwapType $swapType,
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
    public function toPayload(string $sellingGroupId): array
    {
        // Picnic expects the portions as a string here.
        return [
            'component_swap_type' => $this->swapType->value,
            'portions' => (string) $this->portions,
            'required_amount_by_selling_unit_id' => $this->quantities->byId,
            'selected_component_id' => $this->componentId,
            'selling_group_id' => $sellingGroupId,
        ];
    }
}
