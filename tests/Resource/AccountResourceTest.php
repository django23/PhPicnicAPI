<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Resource;

use PhPicnic\Tests\Support\AbstractPicnicTestCase;

final class AccountResourceTest extends AbstractPicnicTestCase
{
    private const string BASE = 'https://storefront-prod.nl.picnicinternational.com/api/15';

    public function testFetchInfo(): void
    {
        $this->queueJson(['user_id' => 'u-1', 'redacted_phone_number' => '06****12', 'feature_toggles' => [['name' => 'A'], ['name' => 'B']]]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->account()->fetchInfo();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/user-info', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame('u-1', $result->userId);
        self::assertSame(['A', 'B'], $result->featureToggles);
    }

    public function testFetchProfileMenu(): void
    {
        $this->queueJson(['user' => ['name' => 'Ada']]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->account()->fetchProfileMenu();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('GET', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/profile-menu?fetch_mgm=true', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame(['user' => ['name' => 'Ada']], $result);
    }

    public function testLogout(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->account()->logout();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/user/logout', (string) $this->sentRequest(0)->getUri());
        self::assertSame('', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testSendSuggestion(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->account()->sendSuggestion('More oat milk');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/user/suggestion', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['suggestion' => 'More oat milk'], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testRegisterPushToken(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->account()->registerPushToken('tok-1', 'firebase');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/user/device/register_push', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['push_token' => 'tok-1', 'platform' => 'firebase'], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testCheckForUpdates(): void
    {
        $this->queueJson(['update_required' => true]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->account()->checkForUpdates();

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/update_check', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['device_id' => '598F770380CA54B6', 'device_name' => 'notAvailable', 'client_id' => '30100', 'version' => '1.246.1', 'device_os' => '30100;1.246.1-15599;', 'build_number' => '15599'], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
        self::assertTrue($result->isUpdateRequired);
    }

    public function testRequestPhoneVerificationCode(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->account()->requestPhoneVerificationCode('+31612345678');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/user/phone_verification/generate', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['phone_number' => '+31612345678'], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testVerifyPhoneNumber(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->account()->verifyPhoneNumber('+31612345678', '123456');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/user/phone_verification/verify', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['otp' => '123456', 'phone_number' => '+31612345678'], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testSaveHouseholdDetails(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->account()->saveHouseholdDetails(['adults' => 2, 'children' => 1]);

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/user-onboarding/household-details', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['adults' => 2, 'children' => 1], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testSaveBusinessDetails(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->account()->saveBusinessDetails(['business_name' => 'Acme']);

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/user-onboarding/business-details', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['business_name' => 'Acme'], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testSubscribeToPush(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->account()->subscribeToPush('DELIVERY', 'PROMO');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/user-onboarding/subscribe-push', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['topics' => ['DELIVERY', 'PROMO']], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }
}
