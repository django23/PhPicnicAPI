<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\AuthenticatedApi;
use PhPicnic\Dto\Delivery;

/**
 * All deliveries. Picnic removed the unsummarized variant, so this always posts to /deliveries/summary.
 */
final readonly class FetchAllDeliveries
{
    public function __construct(private AuthenticatedApi $api)
    {
    }

    /**
     * @return list<Delivery>
     */
    public function execute(): array
    {
        return Delivery::fromList($this->api->post('/deliveries/summary', []));
    }
}
