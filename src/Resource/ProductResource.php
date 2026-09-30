<?php

declare(strict_types=1);

namespace PhPicnic\Resource;

use InvalidArgumentException;
use PhPicnic\Action\FetchPage;
use PhPicnic\Action\FetchProductImage;
use PhPicnic\Action\FindProductIdByGtin;
use PhPicnic\Action\SearchProducts;
use PhPicnic\Action\SearchProductsRawResponse;
use PhPicnic\Action\SuggestSearchTerms;
use PhPicnic\Dto\Product;
use PhPicnic\Dto\SearchSuggestion;
use PhPicnic\Dto\UiTree;
use PhPicnic\Enum\ImageSize;
use PhPicnic\Enum\PageId;
use PhPicnic\LazyLoginApi;

/**
 * Product search, suggestions, details, barcodes and images: `$picnic->products()`.
 */
final readonly class ProductResource
{
    private SearchProducts $searchProducts;

    private SearchProductsRawResponse $searchProductsRawResponse;

    private SuggestSearchTerms $suggestSearchTerms;

    private FetchPage $fetchPage;

    private FindProductIdByGtin $findProductIdByGtin;

    private FetchProductImage $fetchProductImage;

    public function __construct(private LazyLoginApi $api)
    {
        $this->searchProducts = new SearchProducts($this->api);
        $this->searchProductsRawResponse = new SearchProductsRawResponse($this->api);
        $this->suggestSearchTerms = new SuggestSearchTerms($this->api);
        $this->fetchPage = new FetchPage($this->api);
        $this->findProductIdByGtin = new FindProductIdByGtin($this->api);
        $this->fetchProductImage = new FetchProductImage($this->api);
    }

    /**
     * @return list<Product>
     */
    public function search(string $searchTerm): array
    {
        return $this->searchProducts->execute($searchTerm);
    }

    public function searchRawResponse(string $searchTerm): UiTree
    {
        return $this->searchProductsRawResponse->execute($searchTerm);
    }

    /**
     * @return list<SearchSuggestion>
     */
    public function suggest(string $searchTerm): array
    {
        return $this->suggestSearchTerms->execute($searchTerm);
    }

    public function fetchDetailsPage(string $productId): UiTree
    {
        return $this->fetchPage->execute(PageId::PRODUCT_DETAILS, [
            'id' => $productId,
            'show_category_action' => 'true',
            'show_remove_from_purchases_page_action' => 'true',
        ]);
    }

    /**
     * @throws InvalidArgumentException when the GTIN is malformed
     */
    public function findIdByGtin(string $gtin): ?string
    {
        return $this->findProductIdByGtin->execute($gtin);
    }

    public function imageUrl(string $imageId, ImageSize $size = ImageSize::MEDIUM): string
    {
        return $this->api->staticFileUrl(FetchProductImage::pathFor($imageId, $size));
    }

    public function fetchImage(string $imageId, ImageSize $size = ImageSize::MEDIUM): string
    {
        return $this->fetchProductImage->execute($imageId, $size);
    }
}
