<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * The same contact details from the public API. No login needed, no token sent.
 */
final readonly class FetchPublicContactInfo
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return array<mixed>
     */
    public function execute(): array
    {
        return $this->api->getPublicApi(ApiEndpoint::CS_CONTACT_INFO->path());
    }
}
