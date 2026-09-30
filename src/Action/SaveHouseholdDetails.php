<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Store household details. Untested upstream; the shape follows the "household_details" of the user.
 */
final readonly class SetHouseholdDetails
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @param array<string, mixed> $householdDetails
     */
    public function execute(array $householdDetails): void
    {
        $this->api->post(ApiEndpoint::ONBOARDING_HOUSEHOLD->path(), $householdDetails);
    }
}
