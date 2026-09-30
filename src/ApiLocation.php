<?php

declare(strict_types=1);

namespace PhPicnic;

use PhPicnic\Enum\CountryCode;

/**
 * Where the Picnic API lives: a country storefront plus API version, with an
 * optional base URL override for tests or proxies.
 */
final readonly class ApiLocation
{
    public CountryCode $countryCode;

    public function __construct(
        CountryCode|string $countryCode = CountryCode::NL,
        public string $apiVersion = '15',
        private ?string $baseUrlOverride = null,
    ) {
        $this->countryCode = CountryCode::parse($countryCode);
    }

    /**
     * Fully-qualified API base URL, e.g.
     * "https://storefront-prod.nl.picnicinternational.com/api/15".
     */
    public function baseUrl(): string
    {
        if ($this->baseUrlOverride !== null) {
            return rtrim($this->baseUrlOverride, '/');
        }

        return sprintf(
            'https://storefront-prod.%s.picnicinternational.com/api/%s',
            strtolower($this->countryCode->value),
            $this->apiVersion,
        );
    }
}
