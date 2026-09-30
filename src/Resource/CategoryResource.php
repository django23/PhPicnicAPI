<?php

declare(strict_types=1);

namespace PhPicnic\Resource;

use PhPicnic\Action\FetchPage;
use PhPicnic\Enum\PageId;
use PhPicnic\LazyLoginApi;

/**
 * Category pages: `$picnic->categories()`. Category ids come from the deeplink of a product page category button.
 */
final readonly class CategoryResource
{
    private FetchPage $fetchPage;

    public function __construct(LazyLoginApi $api)
    {
        $this->fetchPage = new FetchPage($api);
    }

    /**
     * @return array<mixed>
     */
    public function fetchLevelOnePage(string $categoryId): array
    {
        return $this->fetchPage->execute(PageId::CATEGORY_LEVEL_ONE, ['category_id' => $categoryId]);
    }

    /**
     * @return array<mixed>
     */
    public function fetchLevelTwoPage(string $categoryId): array
    {
        return $this->fetchPage->execute(PageId::CATEGORY_LEVEL_TWO, ['category_id' => $categoryId]);
    }

    /**
     * @return array<mixed>
     */
    public function fetchLevelThreePage(string $levelTwoCategoryId, string $levelThreeCategoryId): array
    {
        return $this->fetchPage->execute(PageId::CATEGORY_LEVEL_TWO, [
            'category_id' => $levelTwoCategoryId,
            'l3_category_id' => $levelThreeCategoryId,
        ]);
    }
}
