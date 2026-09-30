<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\AuthenticatedApi;

/**
 * Live routing scenario for a delivery (a UI tree).
 */
final readonly class FetchDeliveryRoutingScenario
{
    public function __construct(private AuthenticatedApi $api)
    {
    }

    /**
     * @return array<mixed>
     */
    public function execute(string $deliveryId): array
    {
        return $this->api->get('/deliveries/' . $deliveryId . '/scenario');
    }
}
