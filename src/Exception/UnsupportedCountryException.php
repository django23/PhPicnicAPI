<?php

declare(strict_types=1);

namespace PhPicnic\Exception;

/**
 * Thrown when a country code has no known Picnic API endpoint.
 */
final class UnsupportedCountryException extends AbstractPicnicException
{
}
