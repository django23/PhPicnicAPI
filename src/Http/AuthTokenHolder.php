<?php

declare(strict_types=1);

namespace PhPicnic\Http;

use PhPicnic\Contract\AuthTokenStoreInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The current x-picnic-auth token, kept in sync with the token store. Picnic
 * rotates the token, so every API response may carry a newer one.
 */
final class AuthTokenHolder
{
    public const string HEADER = 'x-picnic-auth';

    private ?string $token;

    public function __construct(
        private readonly AuthTokenStoreInterface $tokenStore,
        ?string $cachedToken = null,
    ) {
        $initialToken = $cachedToken ?? $tokenStore->load();
        $this->token = $initialToken === '' ? null : $initialToken;
    }

    public function current(): ?string
    {
        return $this->token;
    }

    public function isPresent(): bool
    {
        return $this->token !== null;
    }

    /**
     * Forget the token in memory only; the store keeps it until a new one arrives.
     */
    public function forget(): void
    {
        $this->token = null;
    }

    public function rotateFrom(ResponseInterface $response): void
    {
        $rotatedToken = trim($response->getHeaderLine(self::HEADER));
        if ($rotatedToken === '' || $rotatedToken === $this->token) {
            return;
        }

        $this->token = $rotatedToken;
        $this->tokenStore->save($rotatedToken);
    }
}
