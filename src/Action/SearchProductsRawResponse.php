<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\AuthenticatedApi;

/**
 * Search for products and return the untouched PML UI tree.
 */
final readonly class SearchProductsRawResponse
{
    public function __construct(private AuthenticatedApi $api)
    {
    }

    /**
     * @return array<mixed>
     */
    public function execute(string $searchTerm): array
    {
        return $this->api->get('/pages/search-page-results?search_term=' . rawurlencode($searchTerm));
    }
}
