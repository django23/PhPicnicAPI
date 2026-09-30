<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Resource;

use PhPicnic\Tests\Support\AbstractPicnicTestCase;

final class DeliveryResourceTest extends AbstractPicnicTestCase
{
    private const string BASE = 'https://storefront-prod.nl.picnicinternational.com/api/15';

    public function testCancel(): void
    {
        $this->queueJson(['status' => 'CANCELLED']);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->deliveries()->cancel('d-1');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/order/delivery/d-1/cancel', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame(['status' => 'CANCELLED'], $result);
    }

    public function testRate(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->deliveries()->rate('d-1', 9);

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/deliveries/d-1/rating', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['rating' => 9], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testResendInvoiceEmail(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->deliveries()->resendInvoiceEmail('d-1');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/deliveries/d-1/resend_invoice_email', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testFetchReceiptPage(): void
    {
        $this->queueJson(['layout' => []]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->deliveries()->fetchReceiptPage('d-1');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/delivery-receipt-page?delivery_id=d-1', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame(['layout' => []], $result->raw);
    }
}
