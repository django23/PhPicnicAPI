<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Dto;

use PhPicnic\Dto\PayloadReader;
use PHPUnit\Framework\TestCase;

final class PayloadReaderTest extends TestCase
{
    public function testReadFirstStringReturnsTheFirstKeyThatHoldsAValue(): void
    {
        $payload = ['second' => 'b', 'third' => 'c'];

        self::assertSame('b', PayloadReader::readFirstString($payload, 'first', 'second', 'third'));
    }

    public function testReadFirstStringReturnsNullWhenNoKeyMatches(): void
    {
        self::assertNull(PayloadReader::readFirstString(['other' => 'x'], 'first', 'second'));
    }

    public function testReadStringConvertsNumbersButIgnoresArrays(): void
    {
        self::assertSame('42', PayloadReader::readString(['id' => 42], 'id'));
        self::assertNull(PayloadReader::readString(['id' => [1]], 'id'));
    }

    public function testHydrateListSkipsEntriesThatAreNotArrays(): void
    {
        $names = PayloadReader::hydrateList(
            [['name' => 'a'], 'not-an-array', ['name' => 'b']],
            static fn (array $item): mixed => $item['name'],
        );

        self::assertSame(['a', 'b'], $names);
    }
}
