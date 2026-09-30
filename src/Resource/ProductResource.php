<?php

declare(strict_types=1);

namespace PhPicnic\Resource;

use PhPicnic\Action\SearchProducts;
use PhPicnic\Action\SearchProductsRawResponse;
use PhPicnic\Dto\Product;
use PhPicnic\LazyLoginApi;

/**
 * Product search: `$picnic->products()`.
 */
final readonly class ProductResource
{
    private SearchProducts $searchProducts;

    private SearchProductsRawResponse $searchProductsRawResponse;

    public function __construct(LazyLoginApi $api)
    {
        $this->searchProducts = new SearchProducts($api);
        $this->searchProductsRawResponse = new SearchProductsRawResponse($api);
    }

    /**
     * @return list<Product>
     */
    public function search(string $searchTerm): array
    {
        return $this->searchProducts->execute($searchTerm);
    }

    /**
     * @return array<mixed>
     */
    public function searchRawResponse(string $searchTerm): array
    {
        return $this->searchProductsRawResponse->execute($searchTerm);
    }
}
