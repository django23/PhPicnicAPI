<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Live routing scenario for a delivery (a UI tree).
 */
final readonly class FetchDeliveryRoutingScenario
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return array<mixed>
     */
    public function execute(string $deliveryId): array
    {
        return $this->api->get(ApiEndpoint::DELIVERY_SCENARIO->path($deliveryId));
    }
}
