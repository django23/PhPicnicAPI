<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\CheckoutStatus;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Status of an order (not of a delivery).
 */
final readonly class FetchOrderStatus
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(string $orderId): CheckoutStatus
    {
        return CheckoutStatus::fromArray($this->api->get(ApiEndpoint::CHECKOUT_ORDER_STATUS->path($orderId)));
    }
}
