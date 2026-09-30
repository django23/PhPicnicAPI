<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

use InvalidArgumentException;
use PhPicnic\Enum\DayOfWeek;

/**
 * A weekly delivery reminder at a local time of day.
 */
final readonly class Reminder
{
    public function __construct(
        public DayOfWeek $dayOfWeek,
        public int $hour,
        public int $minute = 0,
    ) {
        if ($hour < 0 || $hour > 23) {
            throw new InvalidArgumentException(sprintf('Reminder hour must be between 0 and 23, got %d.', $hour));
        }

        if ($minute < 0 || $minute > 59) {
            throw new InvalidArgumentException(sprintf('Reminder minute must be between 0 and 59, got %d.', $minute));
        }
    }

    /**
     * @return array{day_of_week: string, time_of_day: array{int, int}}
     */
    public function toArray(): array
    {
        return [
            'day_of_week' => $this->dayOfWeek->value,
            'time_of_day' => [$this->hour, $this->minute],
        ];
    }
}
