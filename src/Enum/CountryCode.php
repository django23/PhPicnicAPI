<?php

declare(strict_types=1);

namespace PhPicnic\Enum;

use PhPicnic\Exception\UnsupportedCountryException;

/**
 * Picnic storefronts that this client supports.
 */
enum CountryCode: string
{
    case NL = 'NL';
    case DE = 'DE';
    case BE = 'BE';
    case FR = 'FR';

    /**
     * Language for the Accept-Language header.
     */
    public function languageCode(): string
    {
        return match ($this) {
            self::NL, self::BE => 'nl',
            self::DE => 'de',
            self::FR => 'fr',
        };
    }

    /**
     * Resolve a {@see CountryCode} from itself or a (case-insensitive) string.
     *
     * @throws UnsupportedCountryException when the country is not supported
     */
    public static function parse(self|string $country): self
    {
        if ($country instanceof self) {
            return $country;
        }

        $normalizedCountry = strtoupper(trim($country));

        return self::tryFrom($normalizedCountry)
            ?? throw new UnsupportedCountryException(sprintf(
                'Unsupported country code "%s". Supported: %s.',
                $country,
                implode(', ', array_map(static fn (self $supportedCountry): string => $supportedCountry->value, self::cases())),
            ));
    }
}
