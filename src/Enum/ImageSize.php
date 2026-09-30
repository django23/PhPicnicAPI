<?php

declare(strict_types=1);

namespace PhPicnic\Enum;

/**
 * Sizes of the PNG product images under /static/images/{imageId}/{size}.png.
 */
enum ImageSize: string
{
    case TINY = 'tiny';
    case SMALL = 'small';
    case MEDIUM = 'medium';
    case LARGE = 'large';
    case EXTRA_LARGE = 'extra-large';
}
