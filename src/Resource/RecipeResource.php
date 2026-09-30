<?php

declare(strict_types=1);

namespace PhPicnic\Resource;

use DateTimeImmutable;
use DateTimeZone;
use PhPicnic\Action\FetchPage;
use PhPicnic\Action\RunPageTask;
use PhPicnic\Dto\UiTree;
use PhPicnic\Enum\PageId;
use PhPicnic\Enum\PageTaskId;
use PhPicnic\LazyLoginApi;

/**
 * The cookbook and saved recipes ("selling groups"): `$picnic->recipes()`. A recipe id equals its selling group id.
 */
final readonly class RecipeResource
{
    private FetchPage $fetchPage;

    private RunPageTask $runPageTask;

    public function __construct(LazyLoginApi $api)
    {
        $this->fetchPage = new FetchPage($api);
        $this->runPageTask = new RunPageTask($api);
    }

    public function fetchCookbook(): UiTree
    {
        return $this->fetchPage->execute(PageId::COOKBOOK);
    }

    public function fetchDetailsPage(string $sellingGroupId, ?int $portions = null): UiTree
    {
        $query = ['selling_group_id' => $sellingGroupId];
        if ($portions !== null) {
            $query['portions'] = (string) $portions;
        }

        return $this->fetchPage->execute(PageId::SELLING_GROUP_DETAILS, $query);
    }

    /**
     * @return array<mixed>
     */
    public function save(string $recipeId, ?DateTimeImmutable $savedAt = null): array
    {
        $savedAtUtc = ($savedAt ?? new DateTimeImmutable())->setTimezone(new DateTimeZone('UTC'));

        return $this->runPageTask->execute(PageTaskId::RECIPE_SAVING, [
            'recipe_id' => $recipeId,
            'saved_at' => $savedAtUtc->format('Y-m-d\\TH:i:s.v\\Z'),
        ]);
    }

    /**
     * @return array<mixed>
     */
    public function unsave(string $recipeId): array
    {
        return $this->runPageTask->execute(PageTaskId::RECIPE_SAVING, ['recipe_id' => $recipeId, 'saved_at' => null]);
    }
}
