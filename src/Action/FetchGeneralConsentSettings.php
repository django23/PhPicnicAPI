<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Settings of the general consent.
 */
final readonly class FetchGeneralConsentSettings
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return array<mixed>
     */
    public function execute(): array
    {
        return $this->api->get(ApiEndpoint::CONSENT_GENERAL_SETTINGS_PAGE->path());
    }
}
