<?php

declare(strict_types=1);

namespace PhPicnic\Http;

use InvalidArgumentException;
use PhPicnic\ClientIdentity;
use PhPicnic\PicnicConfig;

/**
 * Picks URL, headers and body per URL family: API calls carry the identity headers
 * and the token, everything else (public-api, static files, redirect hops) never does.
 */
final readonly class RequestBuilder
{
    /** Hosts the unauthenticated redirect requests (GTIN lookup) may talk to. */
    private const array PUBLIC_HOST_SUFFIXES = ['picnic.app', 'picnicinternational.com'];

    public function __construct(
        private PicnicConfig $config,
        private AuthTokenHolder $authToken,
    ) {
    }

    public function apiGet(string $path, ?ClientIdentity $identityOverride = null): OutgoingRequest
    {
        return new OutgoingRequest('GET', $this->apiUrl($path), $this->apiHeaders($identityOverride), null, $path);
    }

    /**
     * A null payload sends no body at all; an empty array sends "[]".
     *
     * @param array<mixed>|string|null $payload
     */
    public function apiWithBody(string $method, string $path, array|string|null $payload): OutgoingRequest
    {
        $encodedBody = $payload === null ? null : json_encode($payload, JSON_THROW_ON_ERROR);

        return new OutgoingRequest($method, $this->apiUrl($path), $this->apiHeaders(), $encodedBody, $path);
    }

    public function apiBytes(string $path, string $bytes, string $contentType): OutgoingRequest
    {
        return new OutgoingRequest('POST', $this->apiUrl($path), [...$this->apiHeaders(), 'Content-Type' => $contentType], $bytes, $path);
    }

    /**
     * Sends only the country, no token and no Picnic agent.
     */
    public function publicApiGet(string $path): OutgoingRequest
    {
        return new OutgoingRequest(
            'GET',
            $this->config->location->publicApiBaseUrl() . $this->relativePath($path),
            [
                'User-Agent' => $this->config->identity->userAgent,
                'picnic-country' => $this->config->location->countryCode->value,
            ],
            null,
            $path,
        );
    }

    public function staticFileUrl(string $path): string
    {
        return $this->config->location->originUrl() . $this->relativePath($path);
    }

    public function staticFileGet(string $path): OutgoingRequest
    {
        return new OutgoingRequest(
            'GET',
            $this->staticFileUrl($path),
            ['User-Agent' => $this->config->identity->userAgent],
            null,
            $path,
        );
    }

    /**
     * Only https hosts under picnic.app and picnicinternational.com are allowed.
     *
     * @throws InvalidArgumentException when the URL is not an allowed Picnic host
     */
    public function webGet(string $url): OutgoingRequest
    {
        $parts = parse_url($url);
        $host = $parts['host'] ?? '';
        $isAllowedHost = array_any(
            self::PUBLIC_HOST_SUFFIXES,
            static fn (string $suffix): bool => $host === $suffix || str_ends_with($host, '.' . $suffix),
        );

        if (($parts['scheme'] ?? '') !== 'https' || ! $isAllowedHost) {
            throw new InvalidArgumentException(sprintf('Refusing to send an unauthenticated request to "%s".', $url));
        }

        return new OutgoingRequest(
            'GET',
            $url,
            [
                'User-Agent' => $this->config->identity->userAgent,
                'x-picnic-agent' => $this->config->identity->picnicAgent,
                'x-picnic-did' => $this->config->identity->picnicDeviceId,
            ],
            null,
            $url,
        );
    }

    private function apiUrl(string $path): string
    {
        return $this->config->location->baseUrl() . $this->relativePath($path);
    }

    /**
     * @return array<string, string>
     */
    private function apiHeaders(?ClientIdentity $identityOverride = null): array
    {
        $headers = $this->config->defaultHeaders($identityOverride);
        $token = $this->authToken->current();

        if ($token !== null) {
            $headers[AuthTokenHolder::HEADER] = $token;
        }

        return $headers;
    }

    /**
     * Only paths on the Picnic API are accepted, never a full URL: the auth
     * token travels with every API call.
     */
    private function relativePath(string $path): string
    {
        if (! str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '://')) {
            throw new InvalidArgumentException(sprintf('Expected a path starting with a single "/", got "%s".', $path));
        }

        return $path;
    }
}
