<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\AuthenticatedApi;
use PhPicnic\Dto\Cart;

/**
 * Remove a product from the cart.
 */
final readonly class RemoveProductFromCart
{
    public function __construct(private AuthenticatedApi $api)
    {
    }

    public function execute(string $productId, int $quantity = 1): Cart
    {
        return Cart::fromArray($this->api->post('/cart/remove_product', [
            'product_id' => $productId,
            'count' => $quantity,
        ]));
    }
}
