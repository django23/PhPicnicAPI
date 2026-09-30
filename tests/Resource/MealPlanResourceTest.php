<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Resource;

use PhPicnic\Tests\Support\AbstractPicnicTestCase;

final class MealPlanResourceTest extends AbstractPicnicTestCase
{
    private const string BASE = 'https://storefront-prod.nl.picnicinternational.com/api/15';

    public function testFetchMealPlan(): void
    {
        $this->queueJson(['layout' => []]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->mealPlan()->fetchMealPlan();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/meals-page-root', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testAssignToBasket(): void
    {
        $this->queueJson(['assignedNumberOfPortions' => 4]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->mealPlan()->assignToBasket('g-1', 2, 4);

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/task/assign-selling-group-to-basket', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['payload' => ['selling_group_id' => 'g-1', 'day_offset' => 2, 'portions' => 4]], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame(4, $result['assignedNumberOfPortions']);
    }

    public function testUpdatePortionsInBasket(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->mealPlan()->updatePortionsInBasket('g-1', 1, 6);

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/task/update-selling-group-number-of-portions-task', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['payload' => ['selling_group_id' => 'g-1', 'day_offset' => 1, 'portions' => 6]], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testRemoveFromBasket(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->mealPlan()->removeFromBasket('g-1');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/task/remove-selling-group-from-basket', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['payload' => ['selling_group_id' => 'g-1']], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }
}
