<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\Cart;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Remove a whole group of lines (such as a recipe) from the cart.
 */
final readonly class RemoveGroupFromCart
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(string $groupId): Cart
    {
        return Cart::fromArray($this->api->post(ApiEndpoint::CART_REMOVE_GROUP->path(), ['group_id' => $groupId]));
    }
}
