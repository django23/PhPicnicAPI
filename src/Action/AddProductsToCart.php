<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use InvalidArgumentException;
use PhPicnic\AuthenticatedApi;
use PhPicnic\Dto\Cart;

/**
 * Add several products at once. Numeric product-id keys are stored as PHP int keys but still JSON-encode to the object Picnic expects ({ "<productId>": <quantity> }).
 */
final readonly class AddProductsToCart
{
    public function __construct(private AuthenticatedApi $api)
    {
    }

    /**
     * @param array<int|string, int> $quantitiesByProductId map of product id => quantity
     *
     * @throws InvalidArgumentException when given an empty map
     */
    public function execute(array $quantitiesByProductId): Cart
    {
        if ($quantitiesByProductId === []) {
            throw new InvalidArgumentException('At least one product is required.');
        }

        return Cart::fromArray($this->api->post('/cart/products/add', $quantitiesByProductId));
    }
}
