<?php

declare(strict_types=1);

namespace PhPicnic\Tests;

use PhPicnic\Enum\CountryCode;
use PhPicnic\PicnicConfig;
use PHPUnit\Framework\TestCase;

final class PicnicConfigTest extends TestCase
{
    public function testBuildsBaseUrlFromCountryAndVersion(): void
    {
        $config = new PicnicConfig(CountryCode::NL, '15');

        self::assertSame(
            'https://storefront-prod.nl.picnicinternational.com/api/15',
            $config->baseUrl(),
        );
    }

    public function testBaseUrlReflectsCountryAndVersion(): void
    {
        $config = new PicnicConfig('DE', '17');

        self::assertSame(
            'https://storefront-prod.de.picnicinternational.com/api/17',
            $config->baseUrl(),
        );
    }

    public function testBaseUrlOverrideWinsAndTrailingSlashTrimmed(): void
    {
        $config = new PicnicConfig(CountryCode::NL, '15', baseUrl: 'https://proxy.local/api/15/');

        self::assertSame('https://proxy.local/api/15', $config->baseUrl());
    }

    public function testWithAuthTokenReturnsCopyAndKeepsImmutable(): void
    {
        $config = new PicnicConfig(CountryCode::BE, '15');
        $withToken = $config->withAuthToken('abc');

        self::assertNull($config->authToken);
        self::assertSame('abc', $withToken->authToken);
        self::assertSame($config->baseUrl(), $withToken->baseUrl());
    }

    public function testNormalizesStringCountryToEnum(): void
    {
        $config = new PicnicConfig('nl');

        self::assertSame(CountryCode::NL, $config->countryCode);
    }
}
