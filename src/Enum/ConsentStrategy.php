<?php

declare(strict_types=1);

namespace PhPicnic\Enum;

/**
 * How wide Picnic searches consent requests for the given topics.
 */
enum ConsentStrategy: string
{
    case WIDE = 'WIDE';
    case NARROW = 'NARROW';
}
