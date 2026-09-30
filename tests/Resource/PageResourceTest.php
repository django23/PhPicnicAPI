<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Resource;

use PhPicnic\Tests\Support\AbstractPicnicTestCase;

final class PageResourceTest extends AbstractPicnicTestCase
{
    private const string BASE = 'https://storefront-prod.nl.picnicinternational.com/api/15';

    public function testFetchBootstrap(): void
    {
        $this->queueJson(['landing_tab_id' => 'home']);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->pages()->fetchBootstrap();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/bootstrap', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame('home', $result['landing_tab_id']);
    }

    public function testFetchPage(): void
    {
        $this->queueJson(['layout' => []]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->pages()->fetchPage('some-page', ['x' => 'a b']);

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/some-page?x=a%20b', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame(['layout' => []], $result->raw);
    }

    public function testResolveDeeplink(): void
    {
        $this->queueJson(['url' => 'app.picnic://categories/1']);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->pages()->resolveDeeplink('https://picnic.app/nl/deeplink/x');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/deeplink/resolve', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['url' => 'https://picnic.app/nl/deeplink/x'], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame('app.picnic://categories/1', $result);
    }

    public function testFetchFaq(): void
    {
        $this->queueJson(['pml_version' => '1']);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->pages()->fetchFaq();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/content/faq', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testFetchSearchEmptyState(): void
    {
        $this->queueJson(['pml_version' => '1']);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->pages()->fetchSearchEmptyState();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/content/search_empty_state', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testFetchHome(): void
    {
        $this->queueJson(['layout' => []]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->pages()->fetchHome();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/home_page_root', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testFetchPurchases(): void
    {
        $this->queueJson(['layout' => []]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->pages()->fetchPurchases();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/purchases-page-root', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testFetchSlotSelector(): void
    {
        $this->queueJson(['layout' => []]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->pages()->fetchSlotSelector();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/slot-selector-root', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testFetchParcelsOverview(): void
    {
        $this->queueJson(['layout' => []]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->pages()->fetchParcelsOverview();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/parcels-overview-page-root', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testFetchEmptySearch(): void
    {
        $this->queueJson(['layout' => []]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->pages()->fetchEmptySearch();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/empty-search-page-root', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testFetchParcelTracking(): void
    {
        $this->queueJson(['layout' => []]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->pages()->fetchParcelTracking('JVGL1');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/parcel-tracking-page-root?parcel_id=JVGL1', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testFetchRscPageParsesAnRscResponseForAnyPageId(): void
    {
        $this->queueText("0:[\"$\"]\n1:I[\"chunk\"]\n", 'text/x-component');

        $page = $this->makeClient(cachedAuthToken: 'tok')->pages()->fetchRscPage('some-rsc-page', ['id' => '7']);

        self::assertSame(self::BASE . '/pages/some-rsc-page?id=7', (string) $this->sentRequest(0)->getUri());
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame(['0' => ['$']], $page->rows);
        self::assertSame(['1' => ['chunk']], $page->modules);
    }
}
