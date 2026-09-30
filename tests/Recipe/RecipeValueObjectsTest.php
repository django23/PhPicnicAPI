<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Recipe;

use PhPicnic\Enum\ComponentSwapType;
use PhPicnic\Exception\InvalidConfigurationException;
use PhPicnic\Recipe\DayAssignment;
use PhPicnic\Recipe\IngredientEdit;
use PhPicnic\Recipe\NewIngredient;
use PhPicnic\Recipe\SellingUnitQuantities;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RecipeValueObjectsTest extends TestCase
{
    public function testNewIngredientPayloadSendsOrderAndPortionsAsStrings(): void
    {
        $payload = new NewIngredient('s5', 2, 3)->toPayload('g-1', 6);

        self::assertSame(['order' => '3', 'quantity' => 2, 'requested_portions' => '6', 'selling_group_id' => 'g-1', 'selling_unit_id' => 's5'], $payload);
    }

    public function testNewIngredientDefaultsToOneUnitAtTheStart(): void
    {
        $ingredient = new NewIngredient('s5');

        self::assertSame(1, $ingredient->quantity);
        self::assertSame(0, $ingredient->order);
    }

    public function testIngredientEditPayloadOmitsSwapTypeWhenAbsent(): void
    {
        $payload = new IngredientEdit('c-3', new SellingUnitQuantities(['s5' => 3]))->toPayload('g-1');

        self::assertSame(['requested_sellable_portions' => '4', 'selling_group_component_id' => 'c-3', 'selling_group_id' => 'g-1', 'selling_unit_quantity_by_id' => ['s5' => 3]], $payload);
    }

    public function testIngredientEditPayloadIncludesSwapType(): void
    {
        $payload = new IngredientEdit('c-3', new SellingUnitQuantities(['s5' => 3]), 2)->toPayload('g-1', ComponentSwapType::SEARCH_SELECTION);

        self::assertSame('SEARCH_SELECTION', $payload['swapType']);
        self::assertSame('2', $payload['requested_sellable_portions']);
    }

    public function testDayAssignmentPayloadSendsPortionsAsString(): void
    {
        $payload = new DayAssignment('c-3', new SellingUnitQuantities(['s5' => 1]), 2, ComponentSwapType::POPULAR_SELECTION)->toPayload('g-1');

        self::assertSame(['component_swap_type' => 'POPULAR_SELECTION', 'portions' => '2', 'required_amount_by_selling_unit_id' => ['s5' => 1], 'selected_component_id' => 'c-3', 'selling_group_id' => 'g-1'], $payload);
    }

    public function testQuantitiesKeepTheGivenMap(): void
    {
        self::assertSame(['s1' => 2, 's2' => 1], (new SellingUnitQuantities(['s1' => 2, 's2' => 1]))->byId);
    }

    /**
     * @param callable(): object $build
     */
    #[DataProvider('invalidBuilders')]
    public function testRejectsInvalidValues(callable $build): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $build();
    }

    /**
     * @return iterable<string, array{callable(): object}>
     */
    public static function invalidBuilders(): iterable
    {
        $quantities = new SellingUnitQuantities(['s5' => 1]);

        yield 'no quantities' => [static fn (): SellingUnitQuantities => new SellingUnitQuantities([])];
        yield 'zero quantity' => [static fn (): SellingUnitQuantities => new SellingUnitQuantities(['s5' => 0])];
        yield 'empty selling unit id' => [static fn (): NewIngredient => new NewIngredient('')];
        yield 'zero ingredient quantity' => [static fn (): NewIngredient => new NewIngredient('s5', 0)];
        yield 'negative order' => [static fn (): NewIngredient => new NewIngredient('s5', 1, -1)];
        yield 'edit without component' => [static fn (): IngredientEdit => new IngredientEdit('', $quantities)];
        yield 'edit without portions' => [static fn (): IngredientEdit => new IngredientEdit('c-3', $quantities, 0)];
        yield 'assignment without component' => [static fn (): DayAssignment => new DayAssignment('', $quantities, 2, ComponentSwapType::SEARCH_SELECTION)];
        yield 'assignment without portions' => [static fn (): DayAssignment => new DayAssignment('c-3', $quantities, 0, ComponentSwapType::SEARCH_SELECTION)];
    }
}
