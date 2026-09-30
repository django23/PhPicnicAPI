<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Resource;

use DateTimeImmutable;
use PhPicnic\Tests\Support\AbstractPicnicTestCase;

final class RecipeResourceTest extends AbstractPicnicTestCase
{
    private const string BASE = 'https://storefront-prod.nl.picnicinternational.com/api/15';

    public function testFetchCookbook(): void
    {
        $this->queueJson(['layout' => []]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->recipes()->fetchCookbook();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/cookbook-page-content', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testFetchDetailsPage(): void
    {
        $this->queueJson(['layout' => []]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->recipes()->fetchDetailsPage('g-1');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/selling-group-details-page?selling_group_id=g-1', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testFetchDetailsPageWithPortions(): void
    {
        $this->queueJson(['layout' => []]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->recipes()->fetchDetailsPage('g-1', 4);

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/selling-group-details-page?selling_group_id=g-1&portions=4', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testSave(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->recipes()->save('r-1', new DateTimeImmutable('2026-10-01T10:00:00+02:00'));

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/task/recipe-saving', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['payload' => ['recipe_id' => 'r-1', 'saved_at' => '2026-10-01T08:00:00.000Z']], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testUnsave(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->recipes()->unsave('r-1');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/task/recipe-saving', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['payload' => ['recipe_id' => 'r-1', 'saved_at' => null]], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }
}
