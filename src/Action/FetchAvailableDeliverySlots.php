<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\DeliverySlot;
use PhPicnic\Dto\PayloadReader;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Delivery slots that can be chosen for the cart.
 */
final readonly class FetchAvailableDeliverySlots
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return list<DeliverySlot>
     */
    public function execute(): array
    {
        $responsePayload = $this->api->get(ApiEndpoint::CART_DELIVERY_SLOTS->path());
        $slotPayloads = isset($responsePayload['delivery_slots']) && is_array($responsePayload['delivery_slots'])
            ? $responsePayload['delivery_slots']
            : $responsePayload;

        return PayloadReader::hydrateList($slotPayloads, DeliverySlot::fromArray(...));
    }
}
