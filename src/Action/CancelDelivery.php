<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Cancel the order of a delivery.
 */
final readonly class CancelDelivery
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return array<mixed>
     */
    public function execute(string $deliveryId): array
    {
        return $this->api->post(ApiEndpoint::DELIVERY_CANCEL->path($deliveryId), null);
    }
}
