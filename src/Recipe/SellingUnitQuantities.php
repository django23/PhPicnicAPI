<?php

declare(strict_types=1);

namespace PhPicnic\Recipe;

use PhPicnic\Exception\InvalidConfigurationException;

/**
 * How many of each selling unit an ingredient needs, keyed by selling unit id.
 */
final readonly class SellingUnitQuantities
{
    /**
     * @param array<string, int> $byId map of selling unit id => quantity, at least one entry
     *
     * @throws InvalidConfigurationException when the map is empty or a quantity is below 1
     */
    public function __construct(public array $byId)
    {
        if ($byId === []) {
            throw new InvalidConfigurationException('At least one selling unit quantity is required.');
        }

        foreach ($byId as $sellingUnitId => $quantity) {
            if ($quantity < 1) {
                throw new InvalidConfigurationException(sprintf('The quantity of selling unit "%s" must be at least 1.', $sellingUnitId));
            }
        }
    }
}
