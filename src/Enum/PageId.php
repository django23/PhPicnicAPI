<?php

declare(strict_types=1);

namespace PhPicnic\Enum;

/**
 * Picnic "Fusion" page ids served from /pages/{id}. Pages only exist on API
 * version 15. The three RSC pages come back as React Server Components with
 * newer app versions: see {@see AppProfile}.
 */
enum PageId: string
{
    case HOME = 'home_page_root';
    case PURCHASES = 'purchases-page-root';
    case MEALS = 'meals-page-root';
    case SLOT_SELECTOR = 'slot-selector-root';
    case PARCELS_OVERVIEW = 'parcels-overview-page-root';
    case EMPTY_SEARCH = 'empty-search-page-root';
    case PRODUCT_DETAILS = 'product-details-page-root';
    case CATEGORY_LEVEL_ONE = 'L1-category-page-root';
    case CATEGORY_LEVEL_TWO = 'L2-category-page-root';
    case DELIVERY_RECEIPT = 'delivery-receipt-page';
    case PARCEL_TRACKING = 'parcel-tracking-page-root';
    case COOKBOOK = 'cookbook-page-content';
    case SELLING_GROUP_DETAILS = 'selling-group-details-page';

    /** RSC with AppProfile::V1_246_1 and newer. */
    case CATEGORY_TREE = 'category-tree-root';

    /** RSC with AppProfile::V1_246_1 and newer. */
    case PROFILE = 'profile-root';

    /** RSC with AppProfile::V1_246_1 and newer. */
    case PROMO_GROUP_DEEP_DIVE = 'promo-group-deep-dive';
}
