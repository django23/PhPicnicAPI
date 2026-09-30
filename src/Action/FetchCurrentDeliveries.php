<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\Delivery;
use PhPicnic\Dto\PayloadReader;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\Enum\DeliveryFilter;
use PhPicnic\LazyLoginApi;

/**
 * Deliveries that are current (placed but not yet delivered).
 */
final readonly class FetchCurrentDeliveries
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return list<Delivery>
     */
    public function execute(): array
    {
        return PayloadReader::hydrateList(
            $this->api->post(ApiEndpoint::DELIVERIES_SUMMARY->path(), [DeliveryFilter::CURRENT->value]),
            Delivery::fromArray(...),
        );
    }
}
