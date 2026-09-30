<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Store business details. Untested upstream.
 */
final readonly class SaveBusinessDetails
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @param array<string, mixed> $businessDetails
     */
    public function execute(array $businessDetails): void
    {
        $this->api->post(ApiEndpoint::ONBOARDING_BUSINESS->path(), $businessDetails);
    }
}
