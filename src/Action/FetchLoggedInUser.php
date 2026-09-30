<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\User;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * The authenticated Picnic user.
 */
final readonly class FetchLoggedInUser
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(): User
    {
        return User::fromArray($this->api->get(ApiEndpoint::USER->path()));
    }
}
