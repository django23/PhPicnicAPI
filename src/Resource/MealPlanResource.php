<?php

declare(strict_types=1);

namespace PhPicnic\Resource;

use PhPicnic\Action\FetchPage;
use PhPicnic\Action\RunPageTask;
use PhPicnic\Dto\UiTree;
use PhPicnic\Enum\PageId;
use PhPicnic\Enum\PageTaskId;
use PhPicnic\LazyLoginApi;

/**
 * The meal plan and putting recipes into the basket: `$picnic->mealPlan()`. Backend calls can fail briefly right after a change.
 */
final readonly class MealPlanResource
{
    private FetchPage $fetchPage;

    private RunPageTask $runPageTask;

    public function __construct(LazyLoginApi $api)
    {
        $this->fetchPage = new FetchPage($api);
        $this->runPageTask = new RunPageTask($api);
    }

    public function fetchMealPlan(): UiTree
    {
        return $this->fetchPage->execute(PageId::MEALS);
    }

    /**
     * @return array<mixed>
     */
    public function assignToBasket(string $sellingGroupId, ?int $dayOffset = null, ?int $portions = null): array
    {
        $payload = ['selling_group_id' => $sellingGroupId];
        if ($dayOffset !== null) {
            $payload['day_offset'] = $dayOffset;
        }

        if ($portions !== null) {
            $payload['portions'] = $portions;
        }

        return $this->runPageTask->execute(PageTaskId::ASSIGN_SELLING_GROUP_TO_BASKET, $payload);
    }

    /**
     * @return array<mixed>
     */
    public function updatePortionsInBasket(string $sellingGroupId, int $dayOffset, int $portions): array
    {
        return $this->runPageTask->execute(PageTaskId::UPDATE_SELLING_GROUP_PORTIONS, [
            'selling_group_id' => $sellingGroupId,
            'day_offset' => $dayOffset,
            'portions' => $portions,
        ]);
    }

    /**
     * @return array<mixed>
     */
    public function removeFromBasket(string $sellingGroupId): array
    {
        return $this->runPageTask->execute(PageTaskId::REMOVE_SELLING_GROUP_FROM_BASKET, ['selling_group_id' => $sellingGroupId]);
    }
}
