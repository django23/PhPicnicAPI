<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\PaymentInitiation;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Start the payment for a prepared order. Send the customer to the redirect URL.
 */
final readonly class InitiatePayment
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(string $orderId, string $appReturnUrl): PaymentInitiation
    {
        return PaymentInitiation::fromArray($this->api->post(ApiEndpoint::CHECKOUT_INITIATE_PAYMENT->path(), [
            'order_id' => $orderId,
            'app_return_url' => $appReturnUrl,
        ]));
    }
}
