<?php

declare(strict_types=1);

namespace PhPicnic;

use InvalidArgumentException;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\Exception\InvalidCredentialsException;
use PhPicnic\Exception\PicnicApiException;
use PhPicnic\Exception\TwoFactorException;
use PhPicnic\Exception\TwoFactorRequiredException;
use PhPicnic\Http\AuthResponseGuard;
use PhPicnic\Http\AuthTokenHolder;
use PhPicnic\Http\FailedResponseMapper;
use PhPicnic\Http\LoginFlow;
use PhPicnic\Http\OutgoingRequest;
use PhPicnic\Http\RequestBuilder;
use PhPicnic\Http\RequestSender;
use Psr\Http\Message\ResponseInterface;

/**
 * The one class the rest of the library talks to. API calls carry the Picnic identity
 * headers and the rotating token and accept relative paths only, so the token cannot
 * reach another host. Public-api, static and redirect requests never carry it.
 */
final readonly class Session
{
    private AuthTokenHolder $authToken;

    private AuthResponseGuard $guard;

    private RequestBuilder $requests;

    private RequestSender $sender;

    private LoginFlow $loginFlow;

    public function __construct(
        private PicnicConfig $config,
        HttpTransport $transport,
        ?string $cachedAuthToken = null,
    ) {
        $this->authToken = new AuthTokenHolder($config->tokenStore, $cachedAuthToken);
        $this->guard = new AuthResponseGuard();
        $this->requests = new RequestBuilder($config, $this->authToken);
        $this->sender = new RequestSender($transport, new FailedResponseMapper(), $this->authToken);
        $this->loginFlow = new LoginFlow($config, $this->requests, $this->sender, $this->authToken);
    }

    public function isAuthenticated(): bool
    {
        return $this->authToken->isPresent();
    }

    public function authToken(): ?string
    {
        return $this->authToken->current();
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
        $this->loginFlow->perform($credentials);
    }

    /**
     * @param array<mixed> $payload
     *
     * @throws TwoFactorException
     */
    public function twoFactor(ApiEndpoint $endpoint, array $payload): void
    {
        $response = $this->sender->sendAuthenticated($this->requests->apiWithBody('POST', $endpoint->path(), $payload));
        $this->guard->assertTwoFactorAccepted($response, $endpoint->path());
    }

    /**
     * @return array<mixed>
     *
     * @throws PicnicApiException
     */
    public function get(string $path, ?ClientIdentity $identityOverride = null): array
    {
        return $this->decodeAuthenticated($this->requests->apiGet($path, $identityOverride));
    }

    /**
     * The raw response text, for pages that may be React Server Components.
     *
     * @throws PicnicApiException
     */
    public function getText(string $path, ?ClientIdentity $identityOverride = null): string
    {
        return (string) $this->sender->sendAuthenticated($this->requests->apiGet($path, $identityOverride))->getBody();
    }

    /**
     * @param array<mixed>|string|null $payload
     *
     * @return array<mixed>
     */
    public function post(string $path, array|string|null $payload = []): array
    {
        return $this->decodeAuthenticated($this->requests->apiWithBody('POST', $path, $payload));
    }

    /**
     * @param array<mixed> $payload
     *
     * @return array<mixed>
     */
    public function put(string $path, array $payload): array
    {
        return $this->decodeAuthenticated($this->requests->apiWithBody('PUT', $path, $payload));
    }

    /**
     * POST bytes as they are (used for image uploads; multipart is rejected with 415).
     *
     * @return array<mixed>
     */
    public function postRaw(string $path, string $bytes, string $contentType): array
    {
        return JsonResponseDecoder::decode(
            $this->sender->sendAuthenticated($this->requests->apiBytes($path, $bytes, $contentType)),
            $path,
        );
    }

    /**
     * GET from the unauthenticated public-api root. Sends no token and no
     * Picnic agent, only the country.
     *
     * @return array<mixed>
     */
    public function getPublicApi(string $path): array
    {
        return JsonResponseDecoder::decode($this->sender->sendUnauthenticated($this->requests->publicApiGet($path)), $path);
    }

    public function staticFileUrl(string $path): string
    {
        return $this->requests->staticFileUrl($path);
    }

    /**
     * Download a static file without sending any Picnic headers or token.
     *
     * @throws PicnicApiException
     */
    public function getStaticFile(string $path): string
    {
        return (string) $this->sender->sendUnauthenticated($this->requests->staticFileGet($path))->getBody();
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
        return $this->sender->sendUnauthenticatedWithoutStatusCheck($this->requests->webGet($url));
    }

    /**
     * @return array<mixed>
     */
    private function decodeAuthenticated(OutgoingRequest $request): array
    {
        $decodedBody = JsonResponseDecoder::decode($this->sender->sendAuthenticated($request), $request->label);
        $this->guard->assertNoAuthError($decodedBody);

        return $decodedBody;
    }
}
