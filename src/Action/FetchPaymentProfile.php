<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Payment methods and stored payment options.
 */
final readonly class FetchPaymentProfile
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return array<mixed>
     */
    public function execute(): array
    {
        return $this->api->get(ApiEndpoint::PAYMENT_PROFILE->path());
    }
}
