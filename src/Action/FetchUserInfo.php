<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\UserInfo;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Account basics and the enabled feature toggles.
 */
final readonly class FetchUserInfo
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(): UserInfo
    {
        return UserInfo::fromArray($this->api->get(ApiEndpoint::USER_INFO->path()));
    }
}
