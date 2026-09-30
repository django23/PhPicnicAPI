<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\Delivery;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * A single delivery. Picnic switched this endpoint from POST to GET.
 */
final readonly class FetchDeliveryById
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(string $deliveryId): Delivery
    {
        return Delivery::fromArray($this->api->get(ApiEndpoint::DELIVERY->path($deliveryId)));
    }
}
