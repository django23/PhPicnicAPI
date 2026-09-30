<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Resource;

use PhPicnic\Dto\Reminder;
use PhPicnic\Enum\DayOfWeek;
use PhPicnic\Tests\Support\AbstractPicnicTestCase;

final class CustomerServiceResourceTest extends AbstractPicnicTestCase
{
    private const string BASE = 'https://storefront-prod.nl.picnicinternational.com/api/15';

    public function testFetchContactInfo(): void
    {
        $this->queueJson(['contact_details' => ['email' => 'help@picnic.example']]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->customerService()->fetchContactInfo();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/cs-contact-info', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame(['contact_details' => ['email' => 'help@picnic.example']], $result);
    }

    public function testFetchPublicContactInfo(): void
    {
        $this->queueJson(['contact_details' => []]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->customerService()->fetchPublicContactInfo();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame('https://storefront-prod.nl.picnicinternational.com/public-api/15/cs-contact-info', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        self::assertFalse($this->sentRequest(0)->hasHeader('x-picnic-auth'));
        self::assertSame('NL', $this->sentRequest(0)->getHeaderLine('picnic-country'));
    }

    public function testFetchMessages(): void
    {
        $this->queueJson(['messages' => []]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->customerService()->fetchMessages();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/messages', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testFetchMessagesForPositions(): void
    {
        $this->queueJson(['messages' => []]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->customerService()->fetchMessages('PROMPT', 'MESSAGE_BAR');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/messages?display_position=PROMPT&display_position=MESSAGE_BAR', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testFetchReminders(): void
    {
        $this->queueJson(['reminders' => []]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->customerService()->fetchReminders();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/reminders', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testSaveReminders(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->customerService()->saveReminders(new Reminder(DayOfWeek::MONDAY, 8));

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('PUT', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/reminders', (string) $this->sentRequest(0)->getUri());
        self::assertSame([['day_of_week' => 'MONDAY', 'time_of_day' => [8, 0]]], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testFetchParcels(): void
    {
        $this->queueJson([['id' => 'JVGL1', 'handler_name' => 'DHL', 'active' => true, 'current_status' => ['status' => 'HANDED_OVER', 'timestamp' => '2026-10-01T10:00:00Z']]]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->customerService()->fetchParcels();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/parcels', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame('JVGL1', $result[0]->id);
        self::assertSame('HANDED_OVER', $result[0]->currentStatus);
        self::assertTrue($result[0]->isActive);
    }
}
