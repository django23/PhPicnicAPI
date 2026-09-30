<?php

declare(strict_types=1);

namespace PhPicnic;

/**
 * Gives actions authenticated GET/POST access to the Picnic API. Logs in lazily
 * with the stored credentials the first time no auth token is present.
 */
final readonly class AuthenticatedApi
{
    public function __construct(
        private Session $session,
        private string $username,
        private string $password,
    ) {
    }

    /**
     * @throws Exception\TwoFactorRequiredException when the account needs 2FA
     */
    public function login(): void
    {
        $this->session->login($this->username, $this->password);
    }

    /**
     * @return array<mixed>
     */
    public function get(string $path): array
    {
        $this->loginWhenNotAuthenticated();

        return $this->session->get($path);
    }

    /**
     * @param array<mixed>|string $payload
     *
     * @return array<mixed>
     */
    public function post(string $path, array|string $payload = []): array
    {
        $this->loginWhenNotAuthenticated();

        return $this->session->post($path, $payload);
    }

    private function loginWhenNotAuthenticated(): void
    {
        if (! $this->session->isAuthenticated()) {
            $this->login();
        }
    }
}
