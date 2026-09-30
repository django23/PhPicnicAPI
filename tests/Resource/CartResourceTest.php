<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Resource;

use PhPicnic\Tests\Support\AbstractPicnicTestCase;

final class CartResourceTest extends AbstractPicnicTestCase
{
    private const string BASE = 'https://storefront-prod.nl.picnicinternational.com/api/15';

    public function testAddProductWithSellingUnitContexts(): void
    {
        $this->queueJson(['id' => 'shopping_cart', 'items' => [], 'mts' => 1700000000000]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->cart()->addProduct('s1', 2, [['type' => 'MEAL_PLAN', 'day_offset' => 0, 'servings' => 2]]);

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cart/add_product', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['product_id' => 's1', 'count' => 2, 'selling_unit_contexts' => [['type' => 'MEAL_PLAN', 'day_offset' => 0, 'servings' => 2]]], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame('shopping_cart', $result->id);
    }

    public function testRemoveGroup(): void
    {
        $this->queueJson(['id' => 'shopping_cart', 'items' => [], 'mts' => 1700000000000]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->cart()->removeGroup('group-1');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cart/remove_group', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['group_id' => 'group-1'], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame('shopping_cart', $result->id);
    }

    public function testFetchMinimumOrderValue(): void
    {
        $this->queueJson(['slot_id' => 's1', 'minimum_order_value' => 3500]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->cart()->fetchMinimumOrderValue();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/user-slot-minimum-order-value/minimum', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame('s1', $result->slotId);
        self::assertSame(3500, $result->minimumOrderValueInCents);
    }
}
