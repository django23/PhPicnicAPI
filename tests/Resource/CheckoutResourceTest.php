<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Resource;

use PhPicnic\Tests\Support\AbstractPicnicTestCase;

final class CheckoutResourceTest extends AbstractPicnicTestCase
{
    private const string BASE = 'https://storefront-prod.nl.picnicinternational.com/api/15';

    public function testStart(): void
    {
        $this->queueJson(['order_id' => 'o-1', 'total_price' => 2500, 'total_count' => 3, 'transaction_expiry' => '2026-10-01T10:00:00Z']);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->checkout()->start(1700000000000);

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cart/checkout/start', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['mts' => 1700000000000, 'oos_article_ids' => null], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame('o-1', $result->orderId);
        self::assertSame(2500, $result->totalPrice);
    }

    public function testStartWithResolveKey(): void
    {
        $this->queueJson(['order_id' => 'o-1']);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->checkout()->start(1700000000000, ['a1'], 'rk-1');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cart/checkout/start', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['mts' => 1700000000000, 'oos_article_ids' => ['a1'], 'resolve_key' => 'rk-1'], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testInitiatePayment(): void
    {
        $this->queueJson(['payment_id' => 'p-1', 'transaction_id' => 't-1', 'action' => ['type' => 'REDIRECT', 'redirect_url' => 'https://bank.example/pay']]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->checkout()->initiatePayment('o-1', 'myapp://return');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cart/checkout/initiate_payment', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['order_id' => 'o-1', 'app_return_url' => 'myapp://return'], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame('t-1', $result->transactionId);
        self::assertSame('https://bank.example/pay', $result->redirectUrl);
    }

    public function testFetchTransactionStatus(): void
    {
        $this->queueJson(['checkout_status' => 'FINISHED']);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->checkout()->fetchTransactionStatus('t-1');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cart/checkout/t-1/status', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
        self::assertTrue($result->isFinished());
    }

    public function testFetchTransactionStatusWhilePending(): void
    {
        $this->queueJson(['checkout_status' => 'PENDING']);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->checkout()->fetchTransactionStatus('t-1');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cart/checkout/t-1/status', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
        self::assertFalse($result->isFinished());
    }

    public function testCancel(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->checkout()->cancel('t-1');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cart/checkout/cancel', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['transaction_id' => 't-1'], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testConfirmOrder(): void
    {
        $this->queueJson(['order_id' => 'o-1', 'delivery_slot' => ['slot_id' => 's1']]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->checkout()->confirmOrder('o-1');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cart/checkout/order/o-1/confirm', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame('o-1', $result->orderId);
    }

    public function testFetchOrderStatus(): void
    {
        $this->queueJson(['checkout_status' => 'FINISHED']);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->checkout()->fetchOrderStatus('o-1');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cart/checkout/order/o-1/status', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
        self::assertTrue($result->isFinished());
    }
}
