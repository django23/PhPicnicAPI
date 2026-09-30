<?php

declare(strict_types=1);

namespace PhPicnic\Resource;

use PhPicnic\Action\FetchConsents;
use PhPicnic\Action\FetchConsentSettings;
use PhPicnic\Action\FetchGeneralConsents;
use PhPicnic\Action\SaveConsentSettings;
use PhPicnic\Action\SaveGeneralConsents;
use PhPicnic\Dto\ConsentDeclaration;
use PhPicnic\Enum\ConsentStrategy;
use PhPicnic\LazyLoginApi;

/**
 * Privacy consents: `$picnic->consents()`.
 */
final readonly class ConsentResource
{
    private FetchConsentSettings $fetchConsentSettings;

    private SaveConsentSettings $saveConsentSettings;

    private FetchConsents $fetchConsents;

    private FetchGeneralConsents $fetchGeneralConsents;

    private SaveGeneralConsents $saveGeneralConsents;

    public function __construct(LazyLoginApi $api)
    {
        $this->fetchConsentSettings = new FetchConsentSettings($api);
        $this->saveConsentSettings = new SaveConsentSettings($api);
        $this->fetchConsents = new FetchConsents($api);
        $this->fetchGeneralConsents = new FetchGeneralConsents($api);
        $this->saveGeneralConsents = new SaveGeneralConsents($api);
    }

    /**
     * @return array<mixed>
     */
    public function fetchSettings(bool $general = false): array
    {
        return $this->fetchConsentSettings->execute($general);
    }

    /**
     * @param list<ConsentDeclaration> $declarations
     *
     * @return array<mixed>
     */
    public function saveSettings(array $declarations): array
    {
        return $this->saveConsentSettings->execute($declarations);
    }

    /**
     * @param list<string> $topics
     *
     * @return array<mixed>
     */
    public function fetch(array $topics, ConsentStrategy $strategy = ConsentStrategy::WIDE): array
    {
        return $this->fetchConsents->execute($topics, $strategy);
    }

    /**
     * @return array<mixed>
     */
    public function fetchGeneral(): array
    {
        return $this->fetchGeneralConsents->execute();
    }

    /**
     * @param list<ConsentDeclaration> $declarations
     */
    public function saveGeneral(array $declarations, bool $generalConsent): void
    {
        $this->saveGeneralConsents->execute($declarations, $generalConsent);
    }
}
