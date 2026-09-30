<?php

declare(strict_types=1);

namespace PhPicnic\Enum;

/**
 * Ids of the server-side page tasks (POST /pages/task/{id}) behind recipes,
 * selling groups and the meal plan.
 */
enum PageTaskId: string
{
    case RECIPE_SAVING = 'recipe-saving';
    case ASSIGN_SELLING_GROUP_TO_BASKET = 'assign-selling-group-to-basket';
    case UPDATE_SELLING_GROUP_PORTIONS = 'update-selling-group-number-of-portions-task';
    case REMOVE_SELLING_GROUP_FROM_BASKET = 'remove-selling-group-from-basket';
    case CREATE_USER_DEFINED_RECIPE = 'create-user-defined-recipe';
    case RENAME_USER_DEFINED_RECIPE = 'update-name-user-defined-recipe';
    case UPDATE_USER_DEFINED_RECIPE_PORTIONS = 'update-portions-user-defined-recipe';
    case DELETE_USER_DEFINED_SELLABLE = 'delete-user-defined-sellable';
    case ADD_INGREDIENT = 'add-ingredient-task';
    case SAVE_SELLING_GROUP_EDIT = 'save-selling-group-edit-task';
    case ASSIGN_SELLABLE_COMPONENT_TO_DAY = 'assign-sellable-component-to-day';
    case DELETE_SELLING_GROUP_COMPONENT = 'delete-selling-group-component';
    case UPDATE_SELLING_GROUP_NOTE = 'update-selling-group-note';
    case DELETE_SELLING_GROUP_NOTE = 'delete-selling-group-note-task';
    case SELECT_SELLABLE_IMAGE = 'select-sellable-image';
}
