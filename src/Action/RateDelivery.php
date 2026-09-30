<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use InvalidArgumentException;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Rate a delivery from 0 to 10. Picnic answers HTTP 400 when it was rated already.
 */
final readonly class RateDelivery
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @throws InvalidArgumentException when the rating is outside 0 to 10
     */
    public function execute(string $deliveryId, int $rating): void
    {
        if ($rating < 0 || $rating > 10) {
            throw new InvalidArgumentException('A rating is a number from 0 to 10.');
        }

        $this->api->post(ApiEndpoint::DELIVERY_RATING->path($deliveryId), ['rating' => $rating]);
    }
}
