<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\ConsentDeclaration;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Save the general consent together with per-topic answers.
 */
final readonly class SaveGeneralConsents
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(bool $generalConsent, ConsentDeclaration ...$declarations): void
    {
        $this->api->put(ApiEndpoint::CONSENTS_GENERAL->path(), [
            'consent_declarations' => array_map(static fn (ConsentDeclaration $declaration): array => $declaration->toArray(), $declarations),
            'general_consent' => $generalConsent,
        ]);
    }
}
