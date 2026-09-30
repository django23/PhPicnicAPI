<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Consent settings the user can change. The general variant covers the general consent.
 */
final readonly class FetchConsentSettings
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return array<mixed>
     */
    public function execute(bool $general = false): array
    {
        return $this->api->get(($general ? ApiEndpoint::CONSENT_GENERAL_SETTINGS_PAGE : ApiEndpoint::CONSENT_SETTINGS_PAGE)->path());
    }
}
