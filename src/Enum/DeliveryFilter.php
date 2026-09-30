<?php

declare(strict_types=1);

namespace PhPicnic\Enum;

/**
 * Filters accepted by the deliveries summary endpoint.
 */
enum DeliveryFilter: string
{
    case CURRENT = 'CURRENT';
}
