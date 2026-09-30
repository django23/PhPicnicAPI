<?php

declare(strict_types=1);

namespace PhPicnic\Resource;

use DateTimeImmutable;
use DateTimeZone;
use PhPicnic\Action\FetchPage;
use PhPicnic\Action\RunPageTask;
use PhPicnic\Action\UploadRecipeImage;
use PhPicnic\Enum\ComponentSwapType;
use PhPicnic\Enum\PageId;
use PhPicnic\Enum\PageTaskId;
use PhPicnic\LazyLoginApi;

/**
 * Recipes ("selling groups"), the cookbook and the meal plan: `$picnic->recipes()`. A recipe id equals its selling group id. Backend calls can fail briefly right after a change.
 */
final readonly class RecipeResource
{
    private FetchPage $fetchPage;

    private RunPageTask $runPageTask;

    private UploadRecipeImage $uploadRecipeImage;

    public function __construct(LazyLoginApi $api)
    {
        $this->fetchPage = new FetchPage($api);
        $this->runPageTask = new RunPageTask($api);
        $this->uploadRecipeImage = new UploadRecipeImage($api);
    }

    /**
     * @return array<mixed>
     */
    public function fetchCookbook(): array
    {
        return $this->fetchPage->execute(PageId::COOKBOOK);
    }

    /**
     * @return array<mixed>
     */
    public function fetchMealPlan(): array
    {
        return $this->fetchPage->execute(PageId::MEALS);
    }

    /**
     * @return array<mixed>
     */
    public function fetchDetailsPage(string $sellingGroupId, ?int $portions = null): array
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

    /**
     * @param array<string, int> $quantitiesBySellingUnitId map of selling unit id => quantity
     *
     * @return array<mixed> with the new "sellingGroupId"
     */
    public function createUserDefined(string $name, array $quantitiesBySellingUnitId, int $portions = 4): array
    {
        $sellingUnitIds = array_map(strval(...), array_keys($quantitiesBySellingUnitId));

        return $this->runPageTask->execute(PageTaskId::CREATE_USER_DEFINED_RECIPE, [
            'name' => $name,
            'portions' => $portions,
            'selling_unit_quantities_by_id' => $quantitiesBySellingUnitId,
            'selling_unit_sources' => array_fill_keys($sellingUnitIds, 'search'),
            'selling_units' => $sellingUnitIds,
        ]);
    }

    /**
     * @return array<mixed>
     */
    public function renameUserDefined(string $sellingGroupId, string $name): array
    {
        return $this->runPageTask->execute(PageTaskId::RENAME_USER_DEFINED_RECIPE, ['name' => $name, 'selling_group_id' => $sellingGroupId]);
    }

    /**
     * @return array<mixed>
     */
    public function updateUserDefinedPortions(string $sellingGroupId, int $portions): array
    {
        return $this->runPageTask->execute(PageTaskId::UPDATE_USER_DEFINED_RECIPE_PORTIONS, ['portions' => $portions, 'sellable_id' => $sellingGroupId]);
    }

    /**
     * @return array<mixed>
     */
    public function deleteUserDefined(string $sellingGroupId): array
    {
        return $this->runPageTask->execute(PageTaskId::DELETE_USER_DEFINED_SELLABLE, ['sellable_id' => $sellingGroupId]);
    }

    /**
     * @return array<mixed> with the new "newComponentId"
     */
    public function addIngredient(string $sellingGroupId, string $sellingUnitId, int $quantity = 1, int $portions = 4, int $order = 0): array
    {
        return $this->runPageTask->execute(PageTaskId::ADD_INGREDIENT, [
            'order' => (string) $order,
            'quantity' => $quantity,
            'requested_portions' => (string) $portions,
            'selling_group_id' => $sellingGroupId,
            'selling_unit_id' => $sellingUnitId,
        ]);
    }

    /**
     * @param array<string, int> $quantitiesBySellingUnitId
     *
     * @return array<mixed>
     */
    public function updateIngredient(string $sellingGroupId, string $componentId, array $quantitiesBySellingUnitId, int $portions = 4, ?ComponentSwapType $swapType = null): array
    {
        $payload = [
            'requested_sellable_portions' => (string) $portions,
            'selling_group_component_id' => $componentId,
            'selling_group_id' => $sellingGroupId,
            'selling_unit_quantity_by_id' => $quantitiesBySellingUnitId,
        ];
        if ($swapType instanceof ComponentSwapType) {
            $payload['swapType'] = $swapType->value;
        }

        return $this->runPageTask->execute(PageTaskId::SAVE_SELLING_GROUP_EDIT, $payload);
    }

    /**
     * @param array<string, int> $quantitiesBySellingUnitId only the selected selling units
     *
     * @return array<mixed>
     */
    public function assignComponentToDay(string $sellingGroupId, string $componentId, array $quantitiesBySellingUnitId, int $portions, ComponentSwapType $swapType): array
    {
        return $this->runPageTask->execute(PageTaskId::ASSIGN_SELLABLE_COMPONENT_TO_DAY, [
            'component_swap_type' => $swapType->value,
            'portions' => (string) $portions,
            'required_amount_by_selling_unit_id' => $quantitiesBySellingUnitId,
            'selected_component_id' => $componentId,
            'selling_group_id' => $sellingGroupId,
        ]);
    }

    /**
     * @return array<mixed>
     */
    public function removeIngredient(string $sellingGroupId, string $componentId): array
    {
        return $this->runPageTask->execute(PageTaskId::DELETE_SELLING_GROUP_COMPONENT, ['selling_group_component_id' => $componentId, 'selling_group_id' => $sellingGroupId]);
    }

    /**
     * @param string $noteHtml HTML such as "<p>text</p>", at most 5000 characters of text
     *
     * @return array<mixed>
     */
    public function updateNote(string $sellingGroupId, string $noteHtml): array
    {
        return $this->runPageTask->execute(PageTaskId::UPDATE_SELLING_GROUP_NOTE, ['note' => $noteHtml, 'selling_group_id' => $sellingGroupId]);
    }

    /**
     * @return array<mixed>
     */
    public function deleteNote(string $sellingGroupId): array
    {
        return $this->runPageTask->execute(PageTaskId::DELETE_SELLING_GROUP_NOTE, ['selling_group_id' => $sellingGroupId]);
    }

    /**
     * @param array<string, mixed>|null $referenceImage
     *
     * @return array<mixed>
     */
    public function selectImage(string $sellingGroupId, string $imageId, ?array $referenceImage = null): array
    {
        $payload = ['sellable_id' => $sellingGroupId, 'selected_image_id' => $imageId];
        if ($referenceImage !== null) {
            $payload['reference_image'] = $referenceImage;
        }

        return $this->runPageTask->execute(PageTaskId::SELECT_SELLABLE_IMAGE, $payload);
    }

    /**
     * @return array<mixed>
     */
    public function uploadImage(string $sellingGroupId, string $imageBytes, string $contentType = 'image/jpeg'): array
    {
        return $this->uploadRecipeImage->execute($sellingGroupId, $imageBytes, $contentType);
    }
}
