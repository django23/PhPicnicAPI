<?php

declare(strict_types=1);

namespace PhPicnic\Resource;

use PhPicnic\Action\FetchConsents;
use PhPicnic\Action\FetchConsentSettings;
use PhPicnic\Action\FetchGeneralConsents;
use PhPicnic\Action\FetchGeneralConsentSettings;
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

    private FetchGeneralConsentSettings $fetchGeneralConsentSettings;

    private FetchConsents $fetchConsents;

    private FetchGeneralConsents $fetchGeneralConsents;

    private SaveGeneralConsents $saveGeneralConsents;

    public function __construct(LazyLoginApi $api)
    {
        $this->fetchConsentSettings = new FetchConsentSettings($api);
        $this->saveConsentSettings = new SaveConsentSettings($api);
        $this->fetchGeneralConsentSettings = new FetchGeneralConsentSettings($api);
        $this->fetchConsents = new FetchConsents($api);
        $this->fetchGeneralConsents = new FetchGeneralConsents($api);
        $this->saveGeneralConsents = new SaveGeneralConsents($api);
    }

    /**
     * @return array<mixed>
     */
    public function fetchSettings(): array
    {
        return $this->fetchConsentSettings->execute();
    }

    /**
     * @return array<mixed>
     */
    public function fetchGeneralSettings(): array
    {
        return $this->fetchGeneralConsentSettings->execute();
    }

    /**
     * @return array<mixed>
     */
    public function saveSettings(ConsentDeclaration ...$declarations): array
    {
        return $this->saveConsentSettings->execute(...$declarations);
    }

    /**
     * @return array<mixed>
     */
    public function fetch(ConsentStrategy $strategy, string ...$topics): array
    {
        return $this->fetchConsents->execute($strategy, ...$topics);
    }

    /**
     * @return array<mixed>
     */
    public function fetchGeneral(): array
    {
        return $this->fetchGeneralConsents->execute();
    }

    public function saveGeneral(bool $generalConsent, ConsentDeclaration ...$declarations): void
    {
        $this->saveGeneralConsents->execute($generalConsent, ...$declarations);
    }
}
