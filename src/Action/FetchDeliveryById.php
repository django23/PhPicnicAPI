<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\AuthenticatedApi;
use PhPicnic\Dto\Delivery;

/**
 * A single delivery. Picnic switched this endpoint from POST to GET.
 */
final readonly class FetchDeliveryById
{
    public function __construct(private AuthenticatedApi $api)
    {
    }

    public function execute(string $deliveryId): Delivery
    {
        return Delivery::fromArray($this->api->get('/deliveries/' . $deliveryId));
    }
}
