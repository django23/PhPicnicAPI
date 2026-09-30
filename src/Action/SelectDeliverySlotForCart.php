<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\AuthenticatedApi;
use PhPicnic\Dto\Cart;

/**
 * Choose the delivery slot for the current cart.
 */
final readonly class SelectDeliverySlotForCart
{
    public function __construct(private AuthenticatedApi $api)
    {
    }

    public function execute(string $deliverySlotId): Cart
    {
        return Cart::fromArray($this->api->post('/cart/set_delivery_slot', ['slot_id' => $deliverySlotId]));
    }
}
