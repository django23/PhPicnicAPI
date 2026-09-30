<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\UiTree;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Live driver position and ETA for a delivery (a UI tree).
 */
final readonly class FetchDeliveryDriverPosition
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(string $deliveryId): UiTree
    {
        return UiTree::fromArray($this->api->get(ApiEndpoint::DELIVERY_POSITION->path($deliveryId)));
    }
}
