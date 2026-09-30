<?php

declare(strict_types=1);

namespace PhPicnic\Auth;

use PhPicnic\Contract\AuthTokenStoreInterface;

/**
 * Keeps the token for the lifetime of the process only.
 */
final class InMemoryAuthTokenStore implements AuthTokenStoreInterface
{
    private ?string $authToken = null;

    public function load(): ?string
    {
        return $this->authToken;
    }

    public function save(string $authToken): void
    {
        $this->authToken = $authToken;
    }
}
