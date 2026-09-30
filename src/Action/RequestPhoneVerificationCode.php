<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Send a verification code to a phone number.
 */
final readonly class RequestPhoneVerificationCode
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(string $phoneNumber): void
    {
        $this->api->post(ApiEndpoint::PHONE_VERIFICATION_GENERATE->path(), ['phone_number' => $phoneNumber]);
    }
}
