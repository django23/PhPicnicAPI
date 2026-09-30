<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * Your answer to one consent request.
 */
final readonly class ConsentDeclaration
{
    public function __construct(
        public string $consentRequestTextId,
        public string $consentRequestLocale,
        public bool $agreement,
    ) {
    }

    /**
     * @return array{consent_request_text_id: string, consent_request_locale: string, agreement: bool}
     */
    public function toArray(): array
    {
        return [
            'consent_request_text_id' => $this->consentRequestTextId,
            'consent_request_locale' => $this->consentRequestLocale,
            'agreement' => $this->agreement,
        ];
    }
}
