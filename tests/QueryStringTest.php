<?php

declare(strict_types=1);

namespace PhPicnic\Tests;

use PhPicnic\QueryString;
use PHPUnit\Framework\TestCase;

final class QueryStringTest extends TestCase
{
    public function testRepeatedRepeatsTheKeyForEveryValue(): void
    {
        self::assertSame('k=a&k=b', QueryString::repeated('k', 'a', 'b'));
    }

    public function testRepeatedIsEmptyWithoutValues(): void
    {
        self::assertSame('', QueryString::repeated('k'));
    }

    public function testRepeatedUsesRfc3986Encoding(): void
    {
        self::assertSame('k=a%20b%26c', QueryString::repeated('k', 'a b&c'));
    }

    public function testFromPairsUsesRfc3986Encoding(): void
    {
        self::assertSame('x=a%20b&y=2', QueryString::fromPairs(['x' => 'a b', 'y' => '2']));
    }

    public function testFromPairsIsEmptyWithoutPairs(): void
    {
        self::assertSame('', QueryString::fromPairs([]));
    }

    public function testJoinSkipsEmptyFragments(): void
    {
        self::assertSame('a=1&b=2', QueryString::join('a=1', '', 'b=2'));
        self::assertSame('', QueryString::join('', ''));
    }

    public function testAppendToOnlyAddsTheSeparatorWhenThereIsAQuery(): void
    {
        self::assertSame('/p', QueryString::appendTo('/p', ''));
        self::assertSame('/p?a=1', QueryString::appendTo('/p', 'a=1'));
    }
}
