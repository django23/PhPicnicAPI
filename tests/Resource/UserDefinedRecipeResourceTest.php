<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Resource;

use PhPicnic\Enum\ComponentSwapType;
use PhPicnic\Recipe\DayAssignment;
use PhPicnic\Recipe\IngredientEdit;
use PhPicnic\Recipe\NewIngredient;
use PhPicnic\Recipe\SellingUnitQuantities;
use PhPicnic\Tests\Support\AbstractPicnicTestCase;

final class UserDefinedRecipeResourceTest extends AbstractPicnicTestCase
{
    private const string BASE = 'https://storefront-prod.nl.picnicinternational.com/api/15';

    public function testCreate(): void
    {
        $this->queueJson(['sellingGroupId' => 'g-9']);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->userDefinedRecipes()->create('Pasta', ['s1' => 2, 's2' => 1]);

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/task/create-user-defined-recipe', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['payload' => ['name' => 'Pasta', 'portions' => 4, 'selling_unit_quantities_by_id' => ['s1' => 2, 's2' => 1], 'selling_unit_sources' => ['s1' => 'search', 's2' => 'search'], 'selling_units' => ['s1', 's2']]], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame('g-9', $result['sellingGroupId']);
    }

    public function testRename(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->userDefinedRecipes()->rename('g-1', 'Lasagne');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/task/update-name-user-defined-recipe', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['payload' => ['name' => 'Lasagne', 'selling_group_id' => 'g-1']], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testUpdatePortions(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->userDefinedRecipes()->updatePortions('g-1', 6);

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/task/update-portions-user-defined-recipe', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['payload' => ['portions' => 6, 'sellable_id' => 'g-1']], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testDelete(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->userDefinedRecipes()->delete('g-1');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/task/delete-user-defined-sellable', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['payload' => ['sellable_id' => 'g-1']], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testAddIngredient(): void
    {
        $this->queueJson(['newComponentId' => 'c-3']);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->userDefinedRecipes()->addIngredient('g-1', new NewIngredient('s5', 2));

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/task/add-ingredient-task', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['payload' => ['order' => '0', 'quantity' => 2, 'requested_portions' => '4', 'selling_group_id' => 'g-1', 'selling_unit_id' => 's5']], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame('c-3', $result['newComponentId']);
    }

    public function testUpdateIngredient(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->userDefinedRecipes()->updateIngredient('g-1', new IngredientEdit('c-3', new SellingUnitQuantities(['s5' => 3]), 4), ComponentSwapType::SEARCH_SELECTION);

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/task/save-selling-group-edit-task', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['payload' => ['requested_sellable_portions' => '4', 'selling_group_component_id' => 'c-3', 'selling_group_id' => 'g-1', 'selling_unit_quantity_by_id' => ['s5' => 3], 'swapType' => 'SEARCH_SELECTION']], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testAssignComponentToDay(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->userDefinedRecipes()->assignComponentToDay('g-1', new DayAssignment('c-3', new SellingUnitQuantities(['s5' => 1]), 2, ComponentSwapType::POPULAR_SELECTION));

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/task/assign-sellable-component-to-day', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['payload' => ['component_swap_type' => 'POPULAR_SELECTION', 'portions' => '2', 'required_amount_by_selling_unit_id' => ['s5' => 1], 'selected_component_id' => 'c-3', 'selling_group_id' => 'g-1']], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testRemoveIngredient(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->userDefinedRecipes()->removeIngredient('g-1', 'c-3');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/task/delete-selling-group-component', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['payload' => ['selling_group_component_id' => 'c-3', 'selling_group_id' => 'g-1']], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testSaveNote(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->userDefinedRecipes()->saveNote('g-1', '<p>Salt</p>');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/task/update-selling-group-note', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['payload' => ['note' => '<p>Salt</p>', 'selling_group_id' => 'g-1']], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testDeleteNote(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->userDefinedRecipes()->deleteNote('g-1');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/task/delete-selling-group-note-task', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['payload' => ['selling_group_id' => 'g-1']], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testSelectImage(): void
    {
        $this->queueJson([]);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $client->userDefinedRecipes()->selectImage('g-1', 'recipes/i1');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/pages/task/select-sellable-image', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['payload' => ['sellable_id' => 'g-1', 'selected_image_id' => 'recipes/i1']], $this->sentJsonBody(0));
        $this->assertCarriesPicnicHeaders(0);
    }

    public function testUploadImage(): void
    {
        $this->queueJson(['image_id' => 'i-9']);
        $client = $this->makeClient(cachedAuthToken: 'tok');

        $result = $client->userDefinedRecipes()->uploadImage('g-1', 'BYTES', 'image/jpg');

        self::assertCount(1, $this->http->getRequests());
        self::assertSame('POST', $this->sentRequest(0)->getMethod());
        self::assertSame(self::BASE . '/user-defined-sellable/g-1', (string) $this->sentRequest(0)->getUri());
        self::assertSame('BYTES', (string) $this->sentRequest(0)->getBody());
        $this->assertCarriesPicnicHeaders(0);
        self::assertSame('i-9', $result['image_id']);
        self::assertSame('image/jpeg', $this->sentRequest(0)->getHeaderLine('Content-Type'));
    }
}
