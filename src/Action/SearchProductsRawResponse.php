<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\UiTree;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Search for products and return the untouched PML UI tree.
 */
final readonly class SearchProductsRawResponse
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(string $searchTerm): UiTree
    {
        return UiTree::fromArray($this->api->get(ApiEndpoint::SEARCH_PAGE_RESULTS->path($searchTerm)));
    }
}
