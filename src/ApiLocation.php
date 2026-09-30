<?php

declare(strict_types=1);

namespace PhPicnic;

use PhPicnic\Enum\CountryCode;
use PhPicnic\Exception\InvalidConfigurationException;

/**
 * Where the Picnic API lives: a country storefront plus API version, with an
 * optional base URL override for tests or proxies (HTTPS only, except loopback).
 */
final readonly class ApiLocation
{
    public CountryCode $countryCode;

    /**
     * @throws InvalidConfigurationException on a non-numeric version or an unsafe base URL
     */
    public function __construct(
        CountryCode|string $countryCode = CountryCode::NL,
        public string $apiVersion = '15',
        private ?string $baseUrlOverride = null,
    ) {
        $this->countryCode = CountryCode::parse($countryCode);

        if (! ctype_digit($apiVersion)) {
            throw new InvalidConfigurationException('The API version must be numeric, such as "15".');
        }

        if ($baseUrlOverride !== null) {
            $this->assertSafeBaseUrl($baseUrlOverride);
        }
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

        return sprintf('%s/api/%s', $this->originUrl(), $this->apiVersion);
    }

    /**
     * The unauthenticated "public-api" root next to the regular API.
     */
    public function publicApiBaseUrl(): string
    {
        return sprintf('%s/public-api/%s', $this->originUrl(), $this->apiVersion);
    }

    /**
     * Scheme and host only. Static files such as product images live here.
     */
    public function originUrl(): string
    {
        if ($this->baseUrlOverride !== null) {
            $parts = parse_url($this->baseUrlOverride);

            return sprintf('%s://%s%s', $parts['scheme'] ?? 'https', $parts['host'] ?? '', isset($parts['port']) ? ':' . $parts['port'] : '');
        }

        return sprintf('https://storefront-prod.%s.picnicinternational.com', strtolower($this->countryCode->value));
    }

    private function assertSafeBaseUrl(string $baseUrl): void
    {
        $parts = parse_url($baseUrl);
        $host = $parts['host'] ?? '';
        $isLoopback = in_array($host, ['localhost', '127.0.0.1', '[::1]'], true);

        if ($host === '' || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
            throw new InvalidConfigurationException('The base URL needs a host and must not contain credentials or a fragment.');
        }

        if (($parts['scheme'] ?? '') !== 'https' && (($parts['scheme'] ?? '') !== 'http' || !$isLoopback)) {
            throw new InvalidConfigurationException('The base URL must use HTTPS (plain HTTP is only allowed for localhost).');
        }
    }
}
