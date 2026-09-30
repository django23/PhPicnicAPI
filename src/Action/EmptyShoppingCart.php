<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\Cart;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Remove every product from the cart.
 */
final readonly class EmptyShoppingCart
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(): Cart
    {
        return Cart::fromArray($this->api->post(ApiEndpoint::CART_CLEAR->path()));
    }
}
