<?php

declare(strict_types=1);

namespace PhPicnic;

use InvalidArgumentException;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\Exception\CheckoutIssueException;
use PhPicnic\Exception\InvalidCredentialsException;
use PhPicnic\Exception\PicnicApiException;
use PhPicnic\Exception\TwoFactorException;
use PhPicnic\Exception\TwoFactorRequiredException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Transport + authentication layer.
 *
 * Owns the rotating auth token and sends every API call through the
 * {@see HttpTransport}. Notably it:
 *  - sends the client-identity headers Picnic requires (x-picnic-agent / -did) on every API call;
 *  - refreshes the rotating x-picnic-auth token from every API response and hands it to the token store;
 *  - surfaces auth errors that Picnic returns as HTTP 200 with an error body;
 *  - detects the two-factor-authentication-required login response;
 *  - only accepts relative paths, so the auth token can never be sent to another host.
 *
 * Nothing here retries: a failed POST or PUT may still have changed state.
 */
final class Session
{
    private const string AUTH_HEADER = 'x-picnic-auth';

    /** Hosts the unauthenticated redirect requests (GTIN lookup) may talk to. */
    private const array PUBLIC_HOST_SUFFIXES = ['picnic.app', 'picnicinternational.com'];

    private ?string $authToken;

    public function __construct(
        private readonly PicnicConfig $config,
        private readonly HttpTransport $transport,
        ?string $cachedAuthToken = null,
    ) {
        $storedAuthToken = $cachedAuthToken ?? $config->tokenStore->load();
        $this->authToken = $storedAuthToken === '' ? null : $storedAuthToken;
    }

    public function isAuthenticated(): bool
    {
        return $this->authToken !== null;
    }

    public function authToken(): ?string
    {
        return $this->authToken;
    }

    public function identity(): ClientIdentity
    {
        return $this->config->identity;
    }

    public function location(): ApiLocation
    {
        return $this->config->location;
    }

    /**
     * Exchange credentials for an auth token and store it for later requests.
     *
     * @throws TwoFactorRequiredException when the account needs a second factor
     * @throws InvalidCredentialsException on bad credentials / missing token
     * @throws PicnicApiException
     */
    public function login(Credentials $credentials): void
    {
        $this->authToken = null;

        $loginResponse = $this->send('POST', ApiEndpoint::LOGIN->path(), [
            'key' => $credentials->username,
            'secret' => $credentials->secret,
            'client_id' => $this->config->identity->clientId,
        ]);

        $loginResponseBody = JsonResponseDecoder::decode($loginResponse, ApiEndpoint::LOGIN->path());
        $error = ApiErrorBody::fromResponseBody($loginResponseBody);

        if (($loginResponseBody['second_factor_authentication_required'] ?? false) === true) {
            throw new TwoFactorRequiredException(
                $error->message ?? 'Two-factor authentication required.',
                $loginResponseBody,
            );
        }

        $this->assertNoAuthError($error);

        if (! $this->isAuthenticated()) {
            throw new InvalidCredentialsException(
                'Login failed: the Picnic API did not return an auth token. Check your credentials.',
            );
        }
    }

    /**
     * Drive a 2FA endpoint (generate/verify). These can answer with HTTP 204 /
     * an empty body on success, or an error body with a code on failure.
     *
     * @param array<mixed> $payload
     *
     * @throws TwoFactorException
     * @throws PicnicApiException
     */
    public function twoFactor(ApiEndpoint $endpoint, array $payload): void
    {
        $response = $this->send('POST', $endpoint->path(), $payload);

        if ($response->getStatusCode() === 204 || (string) $response->getBody() === '') {
            return;
        }

        $error = ApiErrorBody::fromResponseBody(JsonResponseDecoder::decode($response, $endpoint->path()));
        $this->assertNoAuthError($error);

        if ($error->code !== null) {
            throw new TwoFactorException(
                $error->message ?? 'Two-factor authentication failed.',
                $error->code,
            );
        }
    }

    /**
     * @return array<mixed>
     *
     * @throws PicnicApiException
     * @throws InvalidCredentialsException
     */
    public function get(string $path, ?ClientIdentity $identityOverride = null): array
    {
        return $this->request('GET', $path, null, $identityOverride);
    }

    /**
     * The raw response text, for pages that may be React Server Components.
     *
     * @throws PicnicApiException
     * @throws InvalidCredentialsException
     */
    public function getText(string $path, ?ClientIdentity $identityOverride = null): string
    {
        return (string) $this->send('GET', $path, null, $identityOverride)->getBody();
    }

    /**
     * A null payload sends no body at all; an empty array sends "[]".
     *
     * @param array<mixed>|string|null $payload
     *
     * @return array<mixed>
     *
     * @throws PicnicApiException
     * @throws InvalidCredentialsException
     */
    public function post(string $path, array|string|null $payload = []): array
    {
        return $this->request('POST', $path, $payload);
    }

    /**
     * @param array<mixed> $payload
     *
     * @return array<mixed>
     *
     * @throws PicnicApiException
     * @throws InvalidCredentialsException
     */
    public function put(string $path, array $payload): array
    {
        return $this->request('PUT', $path, $payload);
    }

    /**
     * POST bytes as they are (used for image uploads; multipart is rejected with 415).
     *
     * @return array<mixed>
     *
     * @throws PicnicApiException
     */
    public function postRaw(string $path, string $bytes, string $contentType): array
    {
        $response = $this->dispatch(
            'POST',
            $this->config->location->baseUrl() . $this->relativePath($path),
            [...$this->apiHeaders(), 'Content-Type' => $contentType],
            $bytes,
            $path,
            true,
        );

        return JsonResponseDecoder::decode($response, $path);
    }

    /**
     * GET from the unauthenticated public-api root. Sends no token and no
     * Picnic agent, only the country.
     *
     * @return array<mixed>
     *
     * @throws PicnicApiException
     */
    public function getPublicApi(string $path): array
    {
        $response = $this->dispatch(
            'GET',
            $this->config->location->publicApiBaseUrl() . $this->relativePath($path),
            [
                'User-Agent' => $this->config->identity->userAgent,
                'picnic-country' => $this->config->location->countryCode->value,
            ],
            null,
            $path,
            false,
        );

        return JsonResponseDecoder::decode($response, $path);
    }

    /**
     * The absolute URL of a static file such as a product image.
     */
    public function staticFileUrl(string $path): string
    {
        return $this->config->location->originUrl() . $this->relativePath($path);
    }

    /**
     * Download a static file without sending any Picnic headers or token.
     *
     * @throws PicnicApiException
     */
    public function getStaticFile(string $path): string
    {
        $response = $this->dispatch(
            'GET',
            $this->staticFileUrl($path),
            ['User-Agent' => $this->config->identity->userAgent],
            null,
            $path,
            false,
        );

        return (string) $response->getBody();
    }

    /**
     * One unauthenticated GET to a Picnic web host, without following
     * redirects, so the caller can inspect each hop. The auth token is never
     * sent, and only https hosts under picnic.app and picnicinternational.com
     * are allowed.
     *
     * @throws PicnicApiException
     * @throws InvalidArgumentException when the URL is not an allowed Picnic host
     */
    public function sendPublicRequest(string $url): ResponseInterface
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

        return $this->dispatch(
            'GET',
            $url,
            [
                'User-Agent' => $this->config->identity->userAgent,
                'x-picnic-agent' => $this->config->identity->picnicAgent,
                'x-picnic-did' => $this->config->identity->picnicDeviceId,
            ],
            null,
            $url,
            false,
            false,
        );
    }

    /**
     * @param array<mixed>|string|null $body
     *
     * @return array<mixed>
     */
    private function request(string $method, string $path, array|string|null $body = null, ?ClientIdentity $identityOverride = null): array
    {
        $decodedBody = JsonResponseDecoder::decode($this->send($method, $path, $body, $identityOverride), $path);
        $this->assertNoAuthError(ApiErrorBody::fromResponseBody($decodedBody));

        return $decodedBody;
    }

    /**
     * @param array<mixed>|string|null $body
     *
     * @throws PicnicApiException
     */
    private function send(string $method, string $path, array|string|null $body = null, ?ClientIdentity $identityOverride = null): ResponseInterface
    {
        return $this->dispatch(
            $method,
            $this->config->location->baseUrl() . $this->relativePath($path),
            $this->apiHeaders($identityOverride),
            $body === null ? null : json_encode($body, JSON_THROW_ON_ERROR),
            $path,
            true,
        );
    }

    /**
     * @param array<string, string> $headers
     *
     * @throws PicnicApiException
     */
    private function dispatch(
        string $method,
        string $url,
        array $headers,
        ?string $encodedBody,
        string $label,
        bool $isApiCall,
        bool $failOnHttpError = true,
    ): ResponseInterface {
        $request = $this->transport->createRequest($method, $url);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($encodedBody !== null) {
            $request = $request->withBody($this->transport->createStream($encodedBody));
        }

        try {
            $response = $this->transport->sendRequest($request);
        } catch (ClientExceptionInterface $clientException) {
            throw new PicnicApiException(
                sprintf('HTTP request to "%s" failed: %s', $label, $clientException->getMessage()),
                0,
                '',
                $clientException,
                $method,
            );
        }

        if ($isApiCall) {
            $this->refreshAuthToken($response);
        }

        $httpStatusCode = $response->getStatusCode();
        if ($failOnHttpError && ($httpStatusCode < 200 || $httpStatusCode >= 300)) {
            throw $this->exceptionForFailedResponse($response, $label, $method);
        }

        return $response;
    }

    /**
     * The identity headers, the auth token when there is one, and the language.
     *
     * @return array<string, string>
     */
    private function apiHeaders(?ClientIdentity $identityOverride = null): array
    {
        $headers = $this->config->defaultHeaders($identityOverride);

        if ($this->authToken !== null) {
            $headers[self::AUTH_HEADER] = $this->authToken;
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

    /**
     * Picnic explains most failures in the body, so read it before falling back
     * to a generic HTTP error.
     */
    private function exceptionForFailedResponse(ResponseInterface $response, string $path, string $method): PicnicApiException|CheckoutIssueException|TwoFactorRequiredException|InvalidCredentialsException
    {
        $httpStatusCode = $response->getStatusCode();
        $responseBody = (string) $response->getBody();
        $decodedBody = json_decode($responseBody, true);
        $error = ApiErrorBody::fromResponseBody(is_array($decodedBody) ? $decodedBody : []);

        if ($error->isCheckoutIssue()) {
            return new CheckoutIssueException(
                $error->message ?? 'The cart has issues that block the checkout.',
                is_string($error->details['type'] ?? null) ? $error->details['type'] : null,
                ($error->details['blocking'] ?? false) === true,
                is_string($error->details['resolve_key'] ?? null) ? $error->details['resolve_key'] : null,
                $error->details,
            );
        }

        if ($error->isTwoFactorRequired()) {
            return new TwoFactorRequiredException($error->message ?? 'Two-factor authentication required.');
        }

        if ($error->isAuthError()) {
            return new InvalidCredentialsException($error->message ?? 'Picnic authentication error.');
        }

        return new PicnicApiException(
            sprintf('Picnic API returned HTTP %d for "%s".', $httpStatusCode, $path),
            $httpStatusCode,
            $responseBody,
            null,
            $method,
        );
    }

    /**
     * The auth token rotates: capture the latest one Picnic sends back and hand
     * it to the token store.
     */
    private function refreshAuthToken(ResponseInterface $response): void
    {
        $rotatedToken = trim($response->getHeaderLine(self::AUTH_HEADER));
        if ($rotatedToken === '' || $rotatedToken === $this->authToken) {
            return;
        }

        $this->authToken = $rotatedToken;
        $this->config->tokenStore->save($rotatedToken);
    }

    /**
     * @throws InvalidCredentialsException
     */
    private function assertNoAuthError(ApiErrorBody $error): void
    {
        if ($error->isAuthError()) {
            throw new InvalidCredentialsException($error->message ?? 'Picnic authentication error.');
        }
    }
}
