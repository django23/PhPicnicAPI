<?php

declare(strict_types=1);

namespace PhPicnic\Enum;

use PhPicnic\Exception\PicnicException;

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
     * Resolve a {@see CountryCode} from itself or a (case-insensitive) string.
     *
     * @throws PicnicException when the country is not supported
     */
    public static function parse(self|string $country): self
    {
        if ($country instanceof self) {
            return $country;
        }

        $normalized = strtoupper(trim($country));

        return self::tryFrom($normalized)
            ?? throw new PicnicException(sprintf(
                'Unsupported country code "%s". Supported: %s.',
                $country,
                implode(', ', array_map(static fn (self $c): string => $c->value, self::cases())),
            ));
    }
}
