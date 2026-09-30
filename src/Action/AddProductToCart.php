<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\Cart;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Add a single product to the cart.
 */
final readonly class AddProductToCart
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(string $productId, int $quantity = 1): Cart
    {
        return Cart::fromArray($this->api->post(ApiEndpoint::CART_ADD_PRODUCT->path(), [
            'product_id' => $productId,
            'count' => $quantity,
        ]));
    }
}
