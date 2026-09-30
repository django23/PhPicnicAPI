<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\Cart;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Choose the delivery slot for the current cart.
 */
final readonly class SelectDeliverySlotForCart
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(string $deliverySlotId): Cart
    {
        return Cart::fromArray($this->api->post(ApiEndpoint::CART_SET_DELIVERY_SLOT->path(), ['slot_id' => $deliverySlotId]));
    }
}
