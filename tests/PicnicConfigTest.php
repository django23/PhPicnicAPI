<?php

declare(strict_types=1);

namespace PhPicnic\Tests;

use PhPicnic\ApiLocation;
use PhPicnic\ClientIdentity;
use PhPicnic\Enum\CountryCode;
use PhPicnic\PicnicConfig;
use PHPUnit\Framework\TestCase;

final class PicnicConfigTest extends TestCase
{
    public function testBuildsBaseUrlFromCountryAndVersion(): void
    {
        $location = new ApiLocation(CountryCode::NL, '15');

        self::assertSame(
            'https://storefront-prod.nl.picnicinternational.com/api/15',
            $location->baseUrl(),
        );
    }

    public function testBaseUrlReflectsCountryAndVersion(): void
    {
        $location = new ApiLocation('DE', '17');

        self::assertSame(
            'https://storefront-prod.de.picnicinternational.com/api/17',
            $location->baseUrl(),
        );
    }

    public function testBaseUrlOverrideWinsAndTrailingSlashTrimmed(): void
    {
        $location = new ApiLocation(CountryCode::NL, '15', baseUrlOverride: 'https://proxy.local/api/15/');

        self::assertSame('https://proxy.local/api/15', $location->baseUrl());
    }

    public function testNormalizesStringCountryToEnum(): void
    {
        $location = new ApiLocation('nl');

        self::assertSame(CountryCode::NL, $location->countryCode);
    }

    public function testDefaultHeadersCarryTheClientIdentity(): void
    {
        $config = new PicnicConfig(identity: new ClientIdentity(userAgent: 'custom/1.0', picnicDeviceId: 'ABCDEF0123456789'));

        $headers = $config->defaultHeaders();

        self::assertSame('custom/1.0', $headers['User-Agent']);
        self::assertSame('ABCDEF0123456789', $headers['x-picnic-did']);
        self::assertSame('30100;1.246.1-15599;', $headers['x-picnic-agent']);
        self::assertArrayNotHasKey('x-picnic-auth', $headers);
    }
}
