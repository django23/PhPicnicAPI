<?php

declare(strict_types=1);

namespace PhPicnic\Exception;

/**
 * Thrown when a client setting is unusable, such as a non-HTTPS base URL.
 */
final class InvalidConfigurationException extends AbstractPicnicException
{
}
