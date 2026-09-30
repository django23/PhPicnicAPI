<?php

declare(strict_types=1);

namespace PhPicnic\Action;

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

    /**
     * @return array<mixed>
     */
    public function execute(string $searchTerm): array
    {
        return $this->api->get(ApiEndpoint::SEARCH_PAGE_RESULTS->path($searchTerm));
    }
}
