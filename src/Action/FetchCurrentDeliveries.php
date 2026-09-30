<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\AuthenticatedApi;
use PhPicnic\Dto\Delivery;

/**
 * Deliveries that are current (placed but not yet delivered).
 */
final readonly class FetchCurrentDeliveries
{
    public function __construct(private AuthenticatedApi $api)
    {
    }

    /**
     * @return list<Delivery>
     */
    public function execute(): array
    {
        return Delivery::fromList($this->api->post('/deliveries/summary', ['CURRENT']));
    }
}
