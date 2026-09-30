<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\Enum\PageTaskId;
use PhPicnic\LazyLoginApi;

/**
 * Run a server-side page task (POST /pages/task/{id}), the mechanism behind recipes, selling groups and the meal plan.
 */
final readonly class RunPageTask
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<mixed>
     */
    public function execute(PageTaskId $task, array $payload): array
    {
        return $this->api->post(ApiEndpoint::PAGE_TASK->path($task->value), ['payload' => $payload]);
    }
}
