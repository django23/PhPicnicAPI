<?php

declare(strict_types=1);

namespace PhPicnic\Tests;

use InvalidArgumentException;
use PhPicnic\Client;
use PhPicnic\Credentials;
use PhPicnic\Tests\Support\AbstractPicnicTestCase;

final class ClientTest extends AbstractPicnicTestCase
{
    private const string BASE = 'https://storefront-prod.nl.picnicinternational.com/api/15';

    public function testFirstCallLogsInBeforeTheRealRequest(): void
    {
        $this->queueLoginThen(['user_id' => 'u-1']);

        $user = $this->makeClient()->fetchLoggedInUser();

        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/user/login', (string) $this->sentRequest(0)->getUri());
        self::assertSame('test-token', $this->sentRequest(1)->getHeaderLine('x-picnic-auth'));
        self::assertSame('u-1', $user->userId);
    }

    public function testLoginSendsPicnicIdentityHeaders(): void
    {
        $this->queueLoginThen(['user_id' => 'u-1']);

        $this->makeClient()->fetchLoggedInUser();

        $login = $this->sentRequest(0);
        self::assertSame('okhttp/4.9.0', $login->getHeaderLine('User-Agent'));
        self::assertSame('30100;1.206.1-#15408', $login->getHeaderLine('x-picnic-agent'));
        self::assertSame('598F770380CA54B6', $login->getHeaderLine('x-picnic-did'));
    }

    public function testLoginBodyCarriesClientIdAndHashedSecret(): void
    {
        $this->queueLoginThen(['user_id' => 'u-1']);

        $this->makeClient()->fetchLoggedInUser();

        $body = $this->sentJsonBody(0);
        self::assertSame(30100, $body['client_id']);
        self::assertSame(md5('secret'), $body['secret']);
    }

    public function testCreateWithoutTransportDiscoversHttpClientAndKeepsCachedToken(): void
    {
        $client = Client::create(new Credentials('user@example.com', 'secret', 'cached'));

        self::assertSame('cached', $client->currentAuthToken());
    }

    public function testCachedAuthTokenSkipsLogin(): void
    {
        $this->queueJson(['user_id' => 'u-1']);

        $user = $this->makeClient(cachedAuthToken: 'cached')->fetchLoggedInUser();

        self::assertSame(self::BASE . '/user', (string) $this->sentRequest(0)->getUri());
        self::assertSame('cached', $this->sentRequest(0)->getHeaderLine('x-picnic-auth'));
        self::assertSame('u-1', $user->userId);
    }

    public function testSearchHitsNewEndpointAndParsesProducts(): void
    {
        $this->queueJson($this->searchFixture());

        $products = $this->makeClient(cachedAuthToken: 'tok')->products()->search('coffee');

        self::assertSame(
            self::BASE . '/pages/search-page-results?search_term=coffee',
            (string) $this->sentRequest(0)->getUri(),
        );
        self::assertCount(1, $products);
        self::assertSame('10511523', $products[0]->id);
        self::assertSame('Lavazza espresso koffiebonen', $products[0]->name);
        self::assertSame(599, $products[0]->displayPrice);
        self::assertSame('500 gram', $products[0]->unitQuantity);
        self::assertSame('s10511523', $products[0]->soleArticleId);
    }

    public function testSearchRawReturnsUntouchedTree(): void
    {
        $fixture = $this->searchFixture();
        $this->queueJson($fixture);

        self::assertSame($fixture, $this->makeClient(cachedAuthToken: 'tok')->products()->searchRawResponse('tea'));
    }

    public function testGetCartReturnsDto(): void
    {
        $this->queueJson([
            'id' => 'shopping_cart',
            'total_count' => 2,
            'total_price' => 1198,
            'items' => [['id' => 'line-1', 'count' => 2, 'price' => 1198]],
        ]);

        $cart = $this->makeClient(cachedAuthToken: 'tok')->cart()->fetch();

        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cart', (string) $this->sentRequest(0)->getUri());
        self::assertSame('shopping_cart', $cart->id);
        self::assertSame(1198, $cart->totalPrice);
        self::assertCount(1, $cart->items);
        self::assertSame('line-1', $cart->items[0]->id);
    }

    public function testAddProduct(): void
    {
        $this->queueJson(['id' => 'shopping_cart']);
        $this->makeClient(cachedAuthToken: 'tok')->cart()->addProduct('10511523', 2);

        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cart/add_product', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['product_id' => '10511523', 'count' => 2], $this->sentJsonBody(0));
    }

    public function testAddProductsBatchSendsMap(): void
    {
        $this->queueJson(['id' => 'shopping_cart']);
        $this->makeClient(cachedAuthToken: 'tok')->cart()->addMultipleProducts(['10511523' => 2, '20622634' => 1]);

        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cart/products/add', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['10511523' => 2, '20622634' => 1], $this->sentJsonBody(0));
    }

    public function testAddProductsRejectsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeClient(cachedAuthToken: 'tok')->cart()->addMultipleProducts([]);
    }

    public function testRemoveProductDefaultsToOne(): void
    {
        $this->queueJson(['id' => 'shopping_cart']);
        $this->makeClient(cachedAuthToken: 'tok')->cart()->removeProduct('10511523');

        self::assertSame(self::BASE . '/cart/remove_product', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['product_id' => '10511523', 'count' => 1], $this->sentJsonBody(0));
    }

    public function testClearCartPostsEmptyBody(): void
    {
        // Regression: v1 clearCart() errored because post() required a $data arg.
        $this->queueJson(['id' => 'shopping_cart', 'items' => []]);
        $this->makeClient(cachedAuthToken: 'tok')->cart()->empty();

        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cart/clear', (string) $this->sentRequest(0)->getUri());
        self::assertSame([], $this->sentJsonBody(0));
    }

    public function testSetDeliverySlot(): void
    {
        $this->queueJson(['id' => 'shopping_cart']);
        $this->makeClient(cachedAuthToken: 'tok')->cart()->selectDeliverySlot('slot-9');

        self::assertSame(self::BASE . '/cart/set_delivery_slot', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['slot_id' => 'slot-9'], $this->sentJsonBody(0));
    }

    public function testGetDeliverySlots(): void
    {
        $this->queueJson(['delivery_slots' => [
            ['slot_id' => 's1', 'window_start' => '2026-06-20T10:00:00', 'is_available' => true],
        ]]);

        $slots = $this->makeClient(cachedAuthToken: 'tok')->deliveries()->fetchAvailableSlots();

        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cart/delivery_slots', (string) $this->sentRequest(0)->getUri());
        self::assertCount(1, $slots);
        self::assertSame('s1', $slots[0]->slotId);
        self::assertTrue($slots[0]->isAvailable);
    }

    public function testGetListAll(): void
    {
        $this->queueJson([]);
        $this->makeClient(cachedAuthToken: 'tok')->shoppingLists()->fetchAll();

        self::assertSame(self::BASE . '/lists', (string) $this->sentRequest(0)->getUri());
    }

    public function testGetListById(): void
    {
        $this->queueJson([]);
        $this->makeClient(cachedAuthToken: 'tok')->shoppingLists()->fetchById('purchases');

        self::assertSame(self::BASE . '/lists/purchases', (string) $this->sentRequest(0)->getUri());
    }

    public function testGetSublist(): void
    {
        $this->queueJson([]);
        $this->makeClient(cachedAuthToken: 'tok')->shoppingLists()->fetchSublist('promotions', 'sub-1');

        self::assertSame(self::BASE . '/lists/promotions?sublist=sub-1', (string) $this->sentRequest(0)->getUri());
    }

    public function testGetDeliveryUsesGet(): void
    {
        // Regression: Picnic switched this endpoint from POST to GET.
        $this->queueJson(['delivery_id' => 'd-42', 'status' => 'COMPLETED']);
        $delivery = $this->makeClient(cachedAuthToken: 'tok')->deliveries()->fetchById('d-42');

        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/deliveries/d-42', (string) $this->sentRequest(0)->getUri());
        self::assertSame('d-42', $delivery->deliveryId);
        self::assertSame('COMPLETED', $delivery->status);
    }

    public function testGetDeliveryScenario(): void
    {
        $this->queueJson(['scenario' => 'EN_ROUTE']);
        $this->makeClient(cachedAuthToken: 'tok')->deliveries()->fetchRoutingScenario('d-42');

        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/deliveries/d-42/scenario', (string) $this->sentRequest(0)->getUri());
    }

    public function testGetDeliveryPosition(): void
    {
        $this->queueJson([]);
        $this->makeClient(cachedAuthToken: 'tok')->deliveries()->fetchDriverPosition('d-42');

        self::assertSame(self::BASE . '/deliveries/d-42/position', (string) $this->sentRequest(0)->getUri());
    }

    public function testGetDeliveriesPostsToSummary(): void
    {
        // Regression: unsummarized /deliveries was removed by Picnic.
        $this->queueJson([['delivery_id' => 'd-1'], ['delivery_id' => 'd-2']]);
        $deliveries = $this->makeClient(cachedAuthToken: 'tok')->deliveries()->fetchAll();

        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/deliveries/summary', (string) $this->sentRequest(0)->getUri());
        self::assertSame([], $this->sentJsonBody(0));
        self::assertCount(2, $deliveries);
        self::assertSame('d-1', $deliveries[0]->deliveryId);
    }

    public function testGetCurrentDeliveriesPostsStatusFilter(): void
    {
        $this->queueJson([['delivery_id' => 'd-1']]);
        $this->makeClient(cachedAuthToken: 'tok')->deliveries()->fetchCurrent();

        self::assertSame(self::BASE . '/deliveries/summary', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['CURRENT'], $this->sentJsonBody(0));
    }

    /**
     * A minimal slice of the /pages/search-page-results PML tree.
     *
     * @return array<mixed>
     */
    private function searchFixture(): array
    {
        return [
            'body' => [
                'child' => [
                    'children' => [
                        [
                            'type' => 'SELLING_UNIT_TILE',
                            'sole_article_id' => 's10511523',
                            'sellingUnit' => [
                                'id' => '10511523',
                                'name' => 'Lavazza espresso koffiebonen',
                                'display_price' => 599,
                                'unit_quantity' => '500 gram',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
