<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Resource;

use PhPicnic\Tests\Support\AbstractPicnicTestCase;

final class PaymentResourceTest extends AbstractPicnicTestCase
{
    private const string BASE = 'https://storefront-prod.nl.picnicinternational.com/api/15';

    public function testFetchProfile(): void
    {
        $this->queueJson(['preferred_payment_option_id' => 'ideal']);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->payments()->fetchProfile();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/payment-profile', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame('ideal', $result['preferred_payment_option_id']);
    }

    public function testFetchWalletTransactions(): void
    {
        $this->queueJson([['id' => 'w-1', 'amount_in_cents' => 2500, 'timestamp' => 1700000000000, 'display_name' => 'iDEAL']]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->payments()->fetchWalletTransactions(2);

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/wallet/transactions', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['page_number' => 2], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
        self::assertCount(1, $result);
        self::assertSame('w-1', $result[0]->id);
        self::assertSame(2500, $result[0]->amountInCents);
    }

    public function testFetchWalletTransactionDetails(): void
    {
        $this->queueJson(['delivery_id' => 'd-1']);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->payments()->fetchWalletTransactionDetails('w-1');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/wallet/transactions/w-1', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame('d-1', $result['delivery_id']);
    }
}
