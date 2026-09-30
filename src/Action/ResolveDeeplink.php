<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\PayloadReader;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Resolve a universal link or app deeplink to its canonical target.
 */
final readonly class ResolveDeeplink
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(string $url): string
    {
        return PayloadReader::readRequiredString($this->api->post(ApiEndpoint::DEEPLINK_RESOLVE->path(), ['url' => $url]), 'url');
    }
}
