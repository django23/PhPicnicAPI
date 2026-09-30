<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\AuthenticatedApi;
use PhPicnic\Dto\DeliverySlot;

/**
 * Delivery slots that can be chosen for the cart.
 */
final readonly class FetchAvailableDeliverySlots
{
    public function __construct(private AuthenticatedApi $api)
    {
    }

    /**
     * @return list<DeliverySlot>
     */
    public function execute(): array
    {
        $responsePayload = $this->api->get('/cart/delivery_slots');
        $slotPayloads = isset($responsePayload['delivery_slots']) && is_array($responsePayload['delivery_slots'])
            ? $responsePayload['delivery_slots']
            : $responsePayload;

        return DeliverySlot::fromList($slotPayloads);
    }
}
