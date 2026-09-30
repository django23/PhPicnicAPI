<?php

declare(strict_types=1);

namespace PhPicnic;

/**
 * Immutable connection settings: where the API lives and how we identify.
 */
final readonly class PicnicConfig
{
    public function __construct(
        public ApiLocation $location = new ApiLocation(),
        public ClientIdentity $identity = new ClientIdentity(),
    ) {
    }

    /**
     * Headers Picnic requires on every request, before any auth token.
     *
     * @return array<string, string>
     */
    public function defaultHeaders(): array
    {
        return [
            'User-Agent' => $this->identity->userAgent,
            'Content-Type' => 'application/json; charset=UTF-8',
            'x-picnic-agent' => $this->identity->picnicAgent,
            'x-picnic-did' => $this->identity->picnicDeviceId,
        ];
    }
}
