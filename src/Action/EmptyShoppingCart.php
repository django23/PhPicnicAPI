<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\AuthenticatedApi;
use PhPicnic\Dto\Cart;

/**
 * Remove every product from the cart.
 */
final readonly class EmptyShoppingCart
{
    public function __construct(private AuthenticatedApi $api)
    {
    }

    public function execute(): Cart
    {
        return Cart::fromArray($this->api->post('/cart/clear'));
    }
}
