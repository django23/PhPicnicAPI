<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Resource;

use PhPicnic\Enum\ImageSize;
use PhPicnic\Tests\Support\AbstractPicnicTestCase;

final class ProductResourceTest extends AbstractPicnicTestCase
{
    private const string BASE = 'https://storefront-prod.nl.picnicinternational.com/api/15';

    public function testSuggest(): void
    {
        $this->queueJson([['type' => 'SEARCH_SUGGESTION', 'id' => 'x1', 'suggestion' => 'melk']]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->products()->suggest('mel k');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/suggest?search_term=mel%20k', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
        self::assertCount(1, $result);
        self::assertSame('melk', $result[0]->suggestion);
    }

    public function testFetchDetailsPage(): void
    {
        $this->queueJson(['layout' => []]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->products()->fetchDetailsPage('s1001524');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/product-details-page-root?id=s1001524&show_category_action=true&show_remove_from_purchases_page_action=true', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame(['layout' => []], $result->raw);
    }

    public function testImageUrlBuildsAStaticUrl(): void
    {
        $client = $this->makeClient(cachedAuthToken: 'tok');
        $result = $client->products()->imageUrl('abc', ImageSize::SMALL);

        self::assertSame('https://storefront-prod.nl.picnicinternational.com/static/images/abc/small.png', $result);
    }
}
