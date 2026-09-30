<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Register a push notification token for this device. Untested upstream.
 */
final readonly class RegisterPushToken
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(string $pushToken, string $platform): void
    {
        $this->api->post(ApiEndpoint::PUSH_REGISTER->path(), ['push_token' => $pushToken, 'platform' => $platform]);
    }
}
