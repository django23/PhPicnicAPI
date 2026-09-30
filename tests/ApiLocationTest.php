<?php

declare(strict_types=1);

namespace PhPicnic\Tests;

use PhPicnic\ApiLocation;
use PhPicnic\Exception\InvalidConfigurationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ApiLocationTest extends TestCase
{
    public function testPublicApiAndOriginUrlsShareTheHost(): void
    {
        $location = new ApiLocation('NL', '15');

        self::assertSame('https://storefront-prod.nl.picnicinternational.com', $location->originUrl());
        self::assertSame('https://storefront-prod.nl.picnicinternational.com/public-api/15', $location->publicApiBaseUrl());
    }

    public function testOriginFollowsTheBaseUrlOverride(): void
    {
        $location = new ApiLocation('NL', '15', 'https://proxy.local:8443/api/15');

        self::assertSame('https://proxy.local:8443', $location->originUrl());
    }

    public function testAllowsPlainHttpForLoopbackOnly(): void
    {
        self::assertSame('http://localhost:8080/api/15', new ApiLocation('NL', '15', 'http://localhost:8080/api/15')->baseUrl());

        $this->expectException(InvalidConfigurationException::class);

        new ApiLocation('NL', '15', 'http://proxy.local/api/15');
    }

    #[DataProvider('unsafeBaseUrls')]
    public function testRejectsUnsafeBaseUrls(string $baseUrl): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new ApiLocation('NL', '15', $baseUrl);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsafeBaseUrls(): iterable
    {
        yield 'credentials in the URL' => ['https://user:pass@proxy.local/api/15'];
        yield 'fragment' => ['https://proxy.local/api/15#x'];
        yield 'no host' => ['https:///api/15'];
        yield 'other scheme' => ['ftp://proxy.local/api/15'];
    }

    public function testRejectsANonNumericApiVersion(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new ApiLocation('NL', '15/../x');
    }
}
