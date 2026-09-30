<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\CheckoutStatus;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Status of a payment transaction. Poll until it is finished.
 */
final readonly class FetchCheckoutTransactionStatus
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(string $transactionId): CheckoutStatus
    {
        return CheckoutStatus::fromArray($this->api->get(ApiEndpoint::CHECKOUT_TRANSACTION_STATUS->path($transactionId)));
    }
}
