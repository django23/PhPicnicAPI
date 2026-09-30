<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\Cart;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * The current shopping cart.
 */
final readonly class FetchShoppingCart
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(): Cart
    {
        return Cart::fromArray($this->api->get(ApiEndpoint::CART->path()));
    }
}
