<?php

declare(strict_types=1);

namespace PhPicnic\Tests;

use PhPicnic\Dto\Cart;
use PhPicnic\Dto\Delivery;
use PhPicnic\Dto\DeliverySlot;
use PhPicnic\Dto\Product;
use PhPicnic\Dto\User;
use PhPicnic\Tests\Support\PicnicTestCase;

final class ClientTest extends PicnicTestCase
{
    private const string BASE = 'https://storefront-prod.nl.picnicinternational.com/api/15';

    public function testLazyLoginSendsClientIdAndPicnicHeaders(): void
    {
        $this->queueLoginThen(['user_id' => 'u-1']);

        $user = $this->makeClient()->getUser();

        // request 0 = login, request 1 = the actual call
        $login = $this->sentRequest(0);
        self::assertSame('POST', $login->getMethod());
        self::assertSame(self::BASE . '/user/login', (string) $login->getUri());
        self::assertSame('okhttp/4.9.0', $login->getHeaderLine('User-Agent'));
        self::assertSame('30100;1.206.1-#15408', $login->getHeaderLine('x-picnic-agent'));
        self::assertSame('598F770380CA54B6', $login->getHeaderLine('x-picnic-did'));

        $body = $this->sentJsonBody(0);
        self::assertSame(30100, $body['client_id']);
        self::assertSame(md5('secret'), $body['secret']);

        self::assertSame('test-token', $this->sentRequest(1)->getHeaderLine('x-picnic-auth'));
        self::assertInstanceOf(User::class, $user);
        self::assertSame('u-1', $user->userId);
    }

    public function testCachedAuthTokenSkipsLogin(): void
    {
        $this->queueJson(['user_id' => 'u-1']);

        $user = $this->makeClient(authToken: 'cached')->getUser();

        self::assertSame(self::BASE . '/user', (string) $this->sentRequest(0)->getUri());
        self::assertSame('cached', $this->sentRequest(0)->getHeaderLine('x-picnic-auth'));
        self::assertSame('u-1', $user->userId);
    }

    public function testSearchHitsNewEndpointAndParsesProducts(): void
    {
        $this->queueJson($this->searchFixture());

        $products = $this->makeClient(authToken: 'tok')->search('coffee');

        self::assertSame(
            self::BASE . '/pages/search-page-results?search_term=coffee',
            (string) $this->sentRequest(0)->getUri(),
        );
        self::assertCount(1, $products);
        self::assertInstanceOf(Product::class, $products[0]);
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

        self::assertSame($fixture, $this->makeClient(authToken: 'tok')->searchRaw('tea'));
    }

    public function testGetCartReturnsDto(): void
    {
        $this->queueJson([
            'id' => 'shopping_cart',
            'total_count' => 2,
            'total_price' => 1198,
            'items' => [['id' => 'line-1', 'count' => 2, 'price' => 1198]],
        ]);

        $cart = $this->makeClient(authToken: 'tok')->getCart();

        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cart', (string) $this->sentRequest(0)->getUri());
        self::assertInstanceOf(Cart::class, $cart);
        self::assertSame('shopping_cart', $cart->id);
        self::assertSame(1198, $cart->totalPrice);
        self::assertCount(1, $cart->items);
        self::assertSame('line-1', $cart->items[0]->id);
    }

    public function testAddProduct(): void
    {
        $this->queueJson(['id' => 'shopping_cart']);
        $this->makeClient(authToken: 'tok')->addProduct('10511523', 2);

        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cart/add_product', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['product_id' => '10511523', 'count' => 2], $this->sentJsonBody(0));
    }

    public function testAddProductsBatchSendsMap(): void
    {
        $this->queueJson(['id' => 'shopping_cart']);
        $this->makeClient(authToken: 'tok')->addProducts(['10511523' => 2, '20622634' => 1]);

        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cart/products/add', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['10511523' => 2, '20622634' => 1], $this->sentJsonBody(0));
    }

    public function testAddProductsRejectsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->makeClient(authToken: 'tok')->addProducts([]);
    }

    public function testRemoveProductDefaultsToOne(): void
    {
        $this->queueJson(['id' => 'shopping_cart']);
        $this->makeClient(authToken: 'tok')->removeProduct('10511523');

        self::assertSame(self::BASE . '/cart/remove_product', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['product_id' => '10511523', 'count' => 1], $this->sentJsonBody(0));
    }

    public function testClearCartPostsEmptyBody(): void
    {
        // Regression: v1 clearCart() errored because post() required a $data arg.
        $this->queueJson(['id' => 'shopping_cart', 'items' => []]);
        $cart = $this->makeClient(authToken: 'tok')->clearCart();

        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cart/clear', (string) $this->sentRequest(0)->getUri());
        self::assertSame([], $this->sentJsonBody(0));
        self::assertInstanceOf(Cart::class, $cart);
    }

    public function testSetDeliverySlot(): void
    {
        $this->queueJson(['id' => 'shopping_cart']);
        $this->makeClient(authToken: 'tok')->setDeliverySlot('slot-9');

        self::assertSame(self::BASE . '/cart/set_delivery_slot', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['slot_id' => 'slot-9'], $this->sentJsonBody(0));
    }

    public function testGetDeliverySlots(): void
    {
        $this->queueJson(['delivery_slots' => [
            ['slot_id' => 's1', 'window_start' => '2026-06-20T10:00:00', 'is_available' => true],
        ]]);

        $slots = $this->makeClient(authToken: 'tok')->getDeliverySlots();

        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cart/delivery_slots', (string) $this->sentRequest(0)->getUri());
        self::assertCount(1, $slots);
        self::assertInstanceOf(DeliverySlot::class, $slots[0]);
        self::assertSame('s1', $slots[0]->slotId);
        self::assertTrue($slots[0]->isAvailable);
    }

    public function testGetListAll(): void
    {
        $this->queueJson([]);
        $this->makeClient(authToken: 'tok')->getList();

        self::assertSame(self::BASE . '/lists', (string) $this->sentRequest(0)->getUri());
    }

    public function testGetListById(): void
    {
        $this->queueJson([]);
        $this->makeClient(authToken: 'tok')->getList('purchases');

        self::assertSame(self::BASE . '/lists/purchases', (string) $this->sentRequest(0)->getUri());
    }

    public function testGetSublist(): void
    {
        $this->queueJson([]);
        $this->makeClient(authToken: 'tok')->getSublist('promotions', 'sub-1');

        self::assertSame(self::BASE . '/lists/promotions?sublist=sub-1', (string) $this->sentRequest(0)->getUri());
    }

    public function testGetDeliveryUsesGet(): void
    {
        // Regression: Picnic switched this endpoint from POST to GET.
        $this->queueJson(['delivery_id' => 'd-42', 'status' => 'COMPLETED']);
        $delivery = $this->makeClient(authToken: 'tok')->getDelivery('d-42');

        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/deliveries/d-42', (string) $this->sentRequest(0)->getUri());
        self::assertInstanceOf(Delivery::class, $delivery);
        self::assertSame('d-42', $delivery->deliveryId);
        self::assertSame('COMPLETED', $delivery->status);
    }

    public function testGetDeliveryScenario(): void
    {
        $this->queueJson(['scenario' => 'EN_ROUTE']);
        $this->makeClient(authToken: 'tok')->getDeliveryScenario('d-42');

        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/deliveries/d-42/scenario', (string) $this->sentRequest(0)->getUri());
    }

    public function testGetDeliveryPosition(): void
    {
        $this->queueJson([]);
        $this->makeClient(authToken: 'tok')->getDeliveryPosition('d-42');

        self::assertSame(self::BASE . '/deliveries/d-42/position', (string) $this->sentRequest(0)->getUri());
    }

    public function testGetDeliveriesPostsToSummary(): void
    {
        // Regression: unsummarized /deliveries was removed by Picnic.
        $this->queueJson([['delivery_id' => 'd-1'], ['delivery_id' => 'd-2']]);
        $deliveries = $this->makeClient(authToken: 'tok')->getDeliveries();

        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/deliveries/summary', (string) $this->sentRequest(0)->getUri());
        self::assertSame([], $this->sentJsonBody(0));
        self::assertCount(2, $deliveries);
        self::assertSame('d-1', $deliveries[0]->deliveryId);
    }

    public function testGetCurrentDeliveriesPostsStatusFilter(): void
    {
        $this->queueJson([['delivery_id' => 'd-1']]);
        $this->makeClient(authToken: 'tok')->getCurrentDeliveries();

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
