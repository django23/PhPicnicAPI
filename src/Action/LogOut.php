<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Invalidate the auth token on the server. The client needs a new login afterwards.
 */
final readonly class LogOut
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(): void
    {
        $this->api->post(ApiEndpoint::USER_LOGOUT->path(), null);
    }
}
