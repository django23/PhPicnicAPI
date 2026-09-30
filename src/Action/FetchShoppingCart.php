<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\AuthenticatedApi;
use PhPicnic\Dto\Cart;

/**
 * The current shopping cart.
 */
final readonly class FetchShoppingCart
{
    public function __construct(private AuthenticatedApi $api)
    {
    }

    public function execute(): Cart
    {
        return Cart::fromArray($this->api->get('/cart'));
    }
}
