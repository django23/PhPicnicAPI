<?php

declare(strict_types=1);

namespace PhPicnic\Tests;

use PhPicnic\Enum\CountryCode;
use PhPicnic\Exception\PicnicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CountryCodeTest extends TestCase
{
    public function testParseAcceptsEnum(): void
    {
        self::assertSame(CountryCode::DE, CountryCode::parse(CountryCode::DE));
    }

    #[DataProvider('countryStrings')]
    public function testParseAcceptsCaseInsensitiveStrings(string $input, CountryCode $expected): void
    {
        self::assertSame($expected, CountryCode::parse($input));
    }

    /**
     * @return iterable<string, array{string, CountryCode}>
     */
    public static function countryStrings(): iterable
    {
        yield 'upper' => ['NL', CountryCode::NL];
        yield 'lower' => ['de', CountryCode::DE];
        yield 'mixed' => ['Be', CountryCode::BE];
        yield 'padded' => ['  fr ', CountryCode::FR];
    }

    public function testParseRejectsUnsupportedCountry(): void
    {
        $this->expectException(PicnicException::class);
        $this->expectExceptionMessage('Unsupported country code "US"');

        CountryCode::parse('US');
    }
}
