<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Upload a picture for a user-defined recipe as raw bytes (multipart is rejected with HTTP 415). Select it afterwards with the returned image id.
 */
final readonly class UploadRecipeImage
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return array<mixed>
     */
    public function execute(string $sellingGroupId, string $imageBytes, string $contentType = 'image/jpeg'): array
    {
        $normalizedContentType = $contentType === 'image/jpg' ? 'image/jpeg' : $contentType;

        return $this->api->postRaw(ApiEndpoint::USER_DEFINED_SELLABLE->path($sellingGroupId), $imageBytes, $normalizedContentType);
    }
}
