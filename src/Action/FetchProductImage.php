<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\Enum\ImageSize;
use PhPicnic\LazyLoginApi;

/**
 * Download a product image as PNG bytes. Images live on the static host and need no token.
 */
final readonly class FetchProductImage
{
    /**
     * Image ids may contain a namespace such as "recipes/abc"; every segment is encoded on its own.
     */
    public static function pathFor(string $imageId, ImageSize $size): string
    {
        $encodedImageId = implode('/', array_map(rawurlencode(...), explode('/', $imageId)));

        return sprintf(ApiEndpoint::IMAGE->value, $encodedImageId, rawurlencode($size->value));
    }

    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(string $imageId, ImageSize $size = ImageSize::MEDIUM): string
    {
        return $this->api->getStaticFile(self::pathFor($imageId, $size));
    }
}
