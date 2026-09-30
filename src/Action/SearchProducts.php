<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\Product;
use PhPicnic\LazyLoginApi;
use PhPicnic\Search\SearchResultParser;

/**
 * Search for products and return them parsed. See {@see SearchProductsRawResponse} for the untouched tree.
 */
final readonly class SearchProducts
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return list<Product>
     */
    public function execute(string $searchTerm): array
    {
        return SearchResultParser::parse(new SearchProductsRawResponse($this->api)->execute($searchTerm)->raw);
    }
}
