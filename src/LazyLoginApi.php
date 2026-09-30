<?php

declare(strict_types=1);

namespace PhPicnic;

use PhPicnic\Exception\TwoFactorRequiredException;
use Psr\Http\Message\ResponseInterface;

/**
 * Access to the Picnic API for the actions. The side effect is in the name:
 * whenever no auth token is present, the first authenticated call logs in.
 * The unauthenticated methods (public API, static files, public redirects)
 * never log in and never send the token.
 */
final readonly class LazyLoginApi
{
    public function __construct(
        private Session $session,
        private Credentials $credentials,
    ) {
    }

    /**
     * @throws TwoFactorRequiredException when the account needs 2FA
     */
    public function login(): void
    {
        $this->session->login($this->credentials);
    }

    public function identity(): ClientIdentity
    {
        return $this->session->identity();
    }

    public function location(): ApiLocation
    {
        return $this->session->location();
    }

    /**
     * @return array<mixed>
     */
    public function get(string $path, ?ClientIdentity $identityOverride = null): array
    {
        $this->loginWhenNoAuthToken();

        return $this->session->get($path, $identityOverride);
    }

    public function getText(string $path, ?ClientIdentity $identityOverride = null): string
    {
        $this->loginWhenNoAuthToken();

        return $this->session->getText($path, $identityOverride);
    }

    /**
     * @param array<mixed>|string|null $payload null sends no body
     *
     * @return array<mixed>
     */
    public function post(string $path, array|string|null $payload = []): array
    {
        $this->loginWhenNoAuthToken();

        return $this->session->post($path, $payload);
    }

    /**
     * @param array<mixed> $payload
     *
     * @return array<mixed>
     */
    public function put(string $path, array $payload): array
    {
        $this->loginWhenNoAuthToken();

        return $this->session->put($path, $payload);
    }

    /**
     * @return array<mixed>
     */
    public function postRaw(string $path, string $bytes, string $contentType): array
    {
        $this->loginWhenNoAuthToken();

        return $this->session->postRaw($path, $bytes, $contentType);
    }

    /**
     * @return array<mixed>
     */
    public function getPublicApi(string $path): array
    {
        return $this->session->getPublicApi($path);
    }

    public function staticFileUrl(string $path): string
    {
        return $this->session->staticFileUrl($path);
    }

    public function getStaticFile(string $path): string
    {
        return $this->session->getStaticFile($path);
    }

    public function sendPublicRequest(string $url): ResponseInterface
    {
        return $this->session->sendPublicRequest($url);
    }

    private function loginWhenNoAuthToken(): void
    {
        if (! $this->session->isAuthenticated()) {
            $this->login();
        }
    }
}
