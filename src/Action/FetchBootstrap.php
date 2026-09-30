<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Tabs, page ids and message behaviour the app starts with. The tab targets are the authoritative page ids.
 */
final readonly class FetchBootstrap
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return array<mixed>
     */
    public function execute(): array
    {
        return $this->api->get(ApiEndpoint::BOOTSTRAP->path());
    }
}
