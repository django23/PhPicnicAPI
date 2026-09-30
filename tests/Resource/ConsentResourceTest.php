<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Resource;

use PhPicnic\Dto\ConsentDeclaration;
use PhPicnic\Enum\ConsentStrategy;
use PhPicnic\Tests\Support\AbstractPicnicTestCase;

final class ConsentResourceTest extends AbstractPicnicTestCase
{
    private const string BASE = 'https://storefront-prod.nl.picnicinternational.com/api/15';

    public function testFetchSettings(): void
    {
        $this->queueJson([['id' => 'c-1']]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->consents()->fetchSettings();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/consents/settings-page', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame([['id' => 'c-1']], $result);
    }

    public function testFetchGeneralSettings(): void
    {
        $this->queueJson([['id' => 'c-1']]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->consents()->fetchGeneralSettings();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/consents/general/settings-page', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testSaveSettings(): void
    {
        $this->queueJson(['consent_request_text_ids' => ['t-1']]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->consents()->saveSettings(new ConsentDeclaration('t-1', 'nl_NL', true));

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('PUT', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/consents', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['consent_declarations' => [['consent_request_text_id' => 't-1', 'consent_request_locale' => 'nl_NL', 'agreement' => true]]], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame(['t-1'], $result['consent_request_text_ids']);
    }

    public function testFetch(): void
    {
        $this->queueJson([['id' => 'c-1']]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->consents()->fetch(ConsentStrategy::NARROW, 'MISC_COMMERCIAL_ADS', 'MISC_READ_ADVERTISING_ID');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/consents?consent_topics=MISC_COMMERCIAL_ADS&consent_topics=MISC_READ_ADVERTISING_ID&strategy=NARROW', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testFetchWithoutTopicsOnlySendsTheStrategy(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->consents()->fetch(ConsentStrategy::WIDE);

        self::assertSame(self::BASE . '/consents?strategy=WIDE', (string) $this->sentRequest(0)->getUri());
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testFetchGeneral(): void
    {
        $this->queueJson(['id' => 'g-1']);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->consents()->fetchGeneral();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/consents/general', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame(['id' => 'g-1'], $result);
    }

    public function testSaveGeneral(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->consents()->saveGeneral(true, new ConsentDeclaration('t-1', 'nl_NL', true));

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('PUT', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/consents/general', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['consent_declarations' => [['consent_request_text_id' => 't-1', 'consent_request_locale' => 'nl_NL', 'agreement' => true]], 'general_consent' => true], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }
}
