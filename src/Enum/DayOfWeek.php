<?php

declare(strict_types=1);

namespace PhPicnic\Enum;

/**
 * Weekday of a delivery reminder, as Picnic spells it on the wire.
 */
enum DayOfWeek: string
{
    case MONDAY = 'MONDAY';
    case TUESDAY = 'TUESDAY';
    case WEDNESDAY = 'WEDNESDAY';
    case THURSDAY = 'THURSDAY';
    case FRIDAY = 'FRIDAY';
    case SATURDAY = 'SATURDAY';
    case SUNDAY = 'SUNDAY';
}
