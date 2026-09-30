<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\UiTree;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * The FAQ content tree.
 */
final readonly class FetchFaq
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(): UiTree
    {
        return UiTree::fromArray($this->api->get(ApiEndpoint::CONTENT_FAQ->path()));
    }
}
