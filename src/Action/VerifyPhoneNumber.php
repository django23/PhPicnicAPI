<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Confirm a phone number with the code that was sent to it.
 */
final readonly class VerifyPhoneNumber
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(string $phoneNumber, string $oneTimeCode): void
    {
        $this->api->post(ApiEndpoint::PHONE_VERIFICATION_VERIFY->path(), ['otp' => $oneTimeCode, 'phone_number' => $phoneNumber]);
    }
}
