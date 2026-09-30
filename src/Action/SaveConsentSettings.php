<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\ConsentDeclaration;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Save answers to consent settings.
 */
final readonly class SaveConsentSettings
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return array<mixed>
     */
    public function execute(ConsentDeclaration ...$declarations): array
    {
        return $this->api->put(ApiEndpoint::CONSENTS->path(), [
            'consent_declarations' => array_map(static fn (ConsentDeclaration $declaration): array => $declaration->toArray(), $declarations),
        ]);
    }
}
