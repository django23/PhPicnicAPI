<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\MinimumOrderValue;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * The minimum order value for the selected delivery slot. Picnic answers HTTP 500 when no slot is selected.
 */
final readonly class FetchMinimumOrderValue
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(): MinimumOrderValue
    {
        return MinimumOrderValue::fromArray($this->api->get(ApiEndpoint::USER_SLOT_MINIMUM_ORDER_VALUE->path()));
    }
}
