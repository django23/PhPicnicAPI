<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\AuthenticatedApi;
use PhPicnic\Dto\Cart;

/**
 * Add a single product to the cart.
 */
final readonly class AddProductToCart
{
    public function __construct(private AuthenticatedApi $api)
    {
    }

    public function execute(string $productId, int $quantity = 1): Cart
    {
        return Cart::fromArray($this->api->post('/cart/add_product', [
            'product_id' => $productId,
            'count' => $quantity,
        ]));
    }
}
