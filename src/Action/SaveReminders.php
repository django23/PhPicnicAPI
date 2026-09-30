<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\Reminder;
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

    public function execute(Reminder ...$reminders): void
    {
        $this->api->put(ApiEndpoint::REMINDERS->path(), array_map(static fn (Reminder $reminder): array => $reminder->toArray(), array_values($reminders)));
    }
}
