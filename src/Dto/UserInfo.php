<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * Account basics and the feature toggles enabled for this user.
 */
final readonly class UserInfo
{
    /**
     * @param list<string> $featureToggles names of the enabled features
     * @param array<mixed> $raw
     */
    public function __construct(
        public string $userId,
        public ?string $redactedPhoneNumber,
        public array $featureToggles,
        public array $raw,
    ) {
    }

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $featureToggles = [];
        foreach (PayloadReader::readArray($payload, 'feature_toggles') as $featureToggle) {
            $name = is_array($featureToggle) ? PayloadReader::readString($featureToggle, 'name') : null;
            if ($name !== null) {
                $featureToggles[] = $name;
            }
        }

        return new self(
            userId: PayloadReader::readRequiredString($payload, 'user_id'),
            redactedPhoneNumber: PayloadReader::readString($payload, 'redacted_phone_number'),
            featureToggles: $featureToggles,
            raw: $payload,
        );
    }
}
