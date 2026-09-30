<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Cancel a checkout transaction that is in progress.
 */
final readonly class CancelCheckout
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(string $transactionId): void
    {
        $this->api->post(ApiEndpoint::CHECKOUT_CANCEL->path(), ['transaction_id' => $transactionId]);
    }
}
