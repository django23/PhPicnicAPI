<?php

declare(strict_types=1);

namespace PhPicnic;

use PhPicnic\Auth\InMemoryAuthTokenStore;
use PhPicnic\Contract\AuthTokenStoreInterface;

/**
 * Immutable connection settings: where the API lives, how we identify, and
 * where the rotating auth token is kept (in memory unless you pass a persistent store).
 */
final readonly class PicnicConfig
{
    public function __construct(
        public ApiLocation $location = new ApiLocation(),
        public ClientIdentity $identity = new ClientIdentity(),
        public AuthTokenStoreInterface $tokenStore = new InMemoryAuthTokenStore(),
    ) {
    }

    /**
     * Headers Picnic requires on every request, before any auth token. Pass an
     * identity to present another app version for a single request.
     *
     * @return array<string, string>
     */
    public function defaultHeaders(?ClientIdentity $identityOverride = null): array
    {
        $identity = $identityOverride ?? $this->identity;

        return [
            'User-Agent' => $identity->userAgent,
            'Content-Type' => 'application/json; charset=UTF-8',
            'Accept-Language' => $this->location->countryCode->languageCode(),
            'x-picnic-agent' => $identity->picnicAgent,
            'x-picnic-did' => $identity->picnicDeviceId,
        ];
    }
}
