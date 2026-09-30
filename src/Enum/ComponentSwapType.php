<?php

declare(strict_types=1);

namespace PhPicnic\Enum;

/**
 * How an ingredient of a user-defined recipe was swapped for another product.
 */
enum ComponentSwapType: string
{
    case WITHIN_SELLING_GROUP_COMPONENT = 'WITHIN_SELLING_GROUP_COMPONENT';
    case POPULAR_SELECTION = 'POPULAR_SELECTION';
    case SEARCH_SELECTION = 'SEARCH_SELECTION';
}
