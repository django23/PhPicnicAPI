<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\Delivery;
use PhPicnic\Dto\PayloadReader;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * All deliveries. Picnic removed the unsummarized variant, so this always posts to the summary endpoint.
 */
final readonly class FetchAllDeliveries
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
            $this->api->post(ApiEndpoint::DELIVERIES_SUMMARY->path(), []),
            Delivery::fromArray(...),
        );
    }
}
