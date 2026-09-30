<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Dto;

use InvalidArgumentException;
use PhPicnic\Dto\Reminder;
use PhPicnic\Enum\DayOfWeek;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReminderTest extends TestCase
{
    public function testToArrayProducesTheWireFormat(): void
    {
        self::assertSame(
            ['day_of_week' => 'MONDAY', 'time_of_day' => [8, 0]],
            new Reminder(DayOfWeek::MONDAY, 8)->toArray(),
        );
    }

    public function testMinuteIsPartOfTheTimeOfDay(): void
    {
        self::assertSame([23, 59], new Reminder(DayOfWeek::SUNDAY, 23, 59)->toArray()['time_of_day']);
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function invalidTimes(): iterable
    {
        yield 'hour too high' => [24, 0];
        yield 'hour negative' => [-1, 0];
        yield 'minute too high' => [8, 60];
        yield 'minute negative' => [8, -1];
    }

    #[DataProvider('invalidTimes')]
    public function testRejectsTimesOutsideTheDay(int $hour, int $minute): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Reminder(DayOfWeek::FRIDAY, $hour, $minute);
    }
}
