<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\OrderConfirmation;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Place the order. This is what makes Picnic deliver.
 */
final readonly class ConfirmOrder
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(string $orderId): OrderConfirmation
    {
        return OrderConfirmation::fromArray($this->api->post(ApiEndpoint::CHECKOUT_ORDER_CONFIRM->path($orderId), null));
    }
}
