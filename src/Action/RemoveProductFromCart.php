<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\Cart;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Remove a product from the cart.
 */
final readonly class RemoveProductFromCart
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(string $productId, int $quantity = 1): Cart
    {
        return Cart::fromArray($this->api->post(ApiEndpoint::CART_REMOVE_PRODUCT->path(), [
            'product_id' => $productId,
            'count' => $quantity,
        ]));
    }
}
