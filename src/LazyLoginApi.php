<?php

declare(strict_types=1);

namespace PhPicnic;

use PhPicnic\Exception\TwoFactorRequiredException;

/**
 * Authenticated GET/POST access for the actions. The side effect is in the
 * name: whenever no auth token is present, the first call logs in.
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

    /**
     * @return array<mixed>
     */
    public function get(string $path): array
    {
        $this->loginWhenNoAuthToken();

        return $this->session->get($path);
    }

    /**
     * @param array<mixed>|string $payload
     *
     * @return array<mixed>
     */
    public function post(string $path, array|string $payload = []): array
    {
        $this->loginWhenNoAuthToken();

        return $this->session->post($path, $payload);
    }

    private function loginWhenNoAuthToken(): void
    {
        if (! $this->session->isAuthenticated()) {
            $this->login();
        }
    }
}
