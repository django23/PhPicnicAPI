<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Replace the delivery reminders. The body is a bare list. Upstream notes they do not seem to work yet.
 */
final readonly class SaveReminders
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @param list<array{day_of_week: string, time_of_day: array{int, int}}> $reminders
     */
    public function execute(array $reminders): void
    {
        $this->api->put(ApiEndpoint::REMINDERS->path(), $reminders);
    }
}
