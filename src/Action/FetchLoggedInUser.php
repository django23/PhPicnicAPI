<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\AuthenticatedApi;
use PhPicnic\Dto\User;

/**
 * The authenticated Picnic user.
 */
final readonly class FetchLoggedInUser
{
    public function __construct(private AuthenticatedApi $api)
    {
    }

    public function execute(): User
    {
        return User::fromArray($this->api->get('/user'));
    }
}
