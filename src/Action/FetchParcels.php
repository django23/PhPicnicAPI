<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\Parcel;
use PhPicnic\Dto\PayloadReader;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Packages shipped by external carriers.
 */
final readonly class FetchParcels
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return list<Parcel>
     */
    public function execute(): array
    {
        return PayloadReader::hydrateList($this->api->get(ApiEndpoint::PARCELS->path()), Parcel::fromArray(...));
    }
}
