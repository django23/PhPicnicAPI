<?php

declare(strict_types=1);

namespace PhPicnic;

use PhPicnic\Enum\CountryCode;

/**
 * Immutable connection configuration for the Picnic API.
 *
 * Replaces the old reliance on $_ENV globals: the base URL is computed from the
 * country code and API version, with an optional override for testing or proxies.
 */
final readonly class PicnicConfig
{
    public CountryCode $countryCode;

    /**
     * @param int    $clientId       Picnic client id sent at login (current Android client: 30100)
     * @param string $userAgent      HTTP User-Agent header
     * @param string $picnicAgent    x-picnic-agent header (client + app version)
     * @param string $picnicDeviceId x-picnic-did header (device id)
     */
    public function __construct(
        CountryCode|string $countryCode = CountryCode::NL,
        public string $apiVersion = '15',
        public ?string $authToken = null,
        public int $clientId = 30100,
        public string $userAgent = 'okhttp/4.9.0',
        public string $picnicAgent = '30100;1.206.1-#15408',
        public string $picnicDeviceId = '598F770380CA54B6',
        private ?string $baseUrl = null,
    ) {
        $this->countryCode = CountryCode::parse($countryCode);
    }

    /**
     * Fully-qualified API base URL, e.g.
     * "https://storefront-prod.nl.picnicinternational.com/api/15".
     */
    public function baseUrl(): string
    {
        if ($this->baseUrl !== null) {
            return rtrim($this->baseUrl, '/');
        }

        return sprintf(
            'https://storefront-prod.%s.picnicinternational.com/api/%s',
            strtolower($this->countryCode->value),
            $this->apiVersion,
        );
    }

    /**
     * Return a copy of this config carrying a cached auth token, so callers can
     * persist the token and skip a re-login on the next request.
     */
    public function withAuthToken(?string $authToken): self
    {
        return new self(
            $this->countryCode,
            $this->apiVersion,
            $authToken,
            $this->clientId,
            $this->userAgent,
            $this->picnicAgent,
            $this->picnicDeviceId,
            $this->baseUrl,
        );
    }
}
