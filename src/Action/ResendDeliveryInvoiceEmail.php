<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Email the invoice of a delivery again.
 */
final readonly class ResendDeliveryInvoiceEmail
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(string $deliveryId): void
    {
        $this->api->post(ApiEndpoint::DELIVERY_RESEND_INVOICE->path($deliveryId), null);
    }
}
