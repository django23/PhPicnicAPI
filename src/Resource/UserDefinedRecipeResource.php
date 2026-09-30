<?php

declare(strict_types=1);

namespace PhPicnic\Resource;

use PhPicnic\Action\RunPageTask;
use PhPicnic\Action\UploadRecipeImage;
use PhPicnic\Enum\ComponentSwapType;
use PhPicnic\Enum\PageTaskId;
use PhPicnic\LazyLoginApi;
use PhPicnic\Recipe\DayAssignment;
use PhPicnic\Recipe\IngredientEdit;
use PhPicnic\Recipe\NewIngredient;

/**
 * Recipes the user made themselves: `$picnic->userDefinedRecipes()`. A recipe id equals its selling group id. Backend calls can fail briefly right after a change.
 */
final readonly class UserDefinedRecipeResource
{
    private RunPageTask $runPageTask;

    private UploadRecipeImage $uploadRecipeImage;

    public function __construct(LazyLoginApi $api)
    {
        $this->runPageTask = new RunPageTask($api);
        $this->uploadRecipeImage = new UploadRecipeImage($api);
    }

    /**
     * @param array<string, int> $quantitiesBySellingUnitId map of selling unit id => quantity
     *
     * @return array<mixed> with the new "sellingGroupId"
     */
    public function create(string $name, array $quantitiesBySellingUnitId, int $portions = 4): array
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
    public function rename(string $sellingGroupId, string $name): array
    {
        return $this->runPageTask->execute(PageTaskId::RENAME_USER_DEFINED_RECIPE, ['name' => $name, 'selling_group_id' => $sellingGroupId]);
    }

    /**
     * @return array<mixed>
     */
    public function updatePortions(string $sellingGroupId, int $portions): array
    {
        return $this->runPageTask->execute(PageTaskId::UPDATE_USER_DEFINED_RECIPE_PORTIONS, ['portions' => $portions, 'sellable_id' => $sellingGroupId]);
    }

    /**
     * @return array<mixed>
     */
    public function delete(string $sellingGroupId): array
    {
        return $this->runPageTask->execute(PageTaskId::DELETE_USER_DEFINED_SELLABLE, ['sellable_id' => $sellingGroupId]);
    }

    /**
     * @return array<mixed> with the new "newComponentId"
     */
    public function addIngredient(string $sellingGroupId, NewIngredient $ingredient, int $portions = 4): array
    {
        return $this->runPageTask->execute(PageTaskId::ADD_INGREDIENT, $ingredient->toPayload($sellingGroupId, $portions));
    }

    /**
     * @return array<mixed>
     */
    public function updateIngredient(string $sellingGroupId, IngredientEdit $edit, ?ComponentSwapType $swapType = null): array
    {
        return $this->runPageTask->execute(PageTaskId::SAVE_SELLING_GROUP_EDIT, $edit->toPayload($sellingGroupId, $swapType));
    }

    /**
     * @return array<mixed>
     */
    public function assignComponentToDay(string $sellingGroupId, DayAssignment $assignment): array
    {
        return $this->runPageTask->execute(PageTaskId::ASSIGN_SELLABLE_COMPONENT_TO_DAY, $assignment->toPayload($sellingGroupId));
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
    public function saveNote(string $sellingGroupId, string $noteHtml): array
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
