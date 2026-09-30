<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\UiTree;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * The content shown before a search term is entered.
 */
final readonly class FetchSearchEmptyState
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(): UiTree
    {
        return UiTree::fromArray($this->api->get(ApiEndpoint::CONTENT_SEARCH_EMPTY_STATE->path()));
    }
}
