<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Subscribe to push notification topics. Untested upstream.
 */
final readonly class SubscribeToPush
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @param list<string> $topics
     */
    public function execute(array $topics): void
    {
        $this->api->post(ApiEndpoint::ONBOARDING_SUBSCRIBE_PUSH->path(), ['topics' => $topics]);
    }
}
