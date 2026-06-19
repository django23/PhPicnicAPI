<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Dto;

use PhPicnic\Dto\Cart;
use PhPicnic\Dto\Delivery;
use PhPicnic\Dto\Product;
use PhPicnic\Dto\User;
use PHPUnit\Framework\TestCase;

final class DtoHydrationTest extends TestCase
{
    public function testUserMapsBothFieldNamingsAndKeepsRaw(): void
    {
        $user = User::fromArray(['user_id' => 'u-1', 'firstname' => 'Ada', 'contact_email' => 'a@b.nl', 'extra' => 1]);

        self::assertSame('u-1', $user->userId);
        self::assertSame('Ada', $user->firstName);
        self::assertSame('a@b.nl', $user->contactEmail);
        self::assertSame(1, $user->raw['extra']);
    }

    public function testProductAcceptsCamelAndSnakeCase(): void
    {
        $camel = Product::fromArray(['id' => '1', 'name' => 'X', 'displayPrice' => 599, 'unitQuantity' => '1L']);
        self::assertSame(599, $camel->displayPrice);
        self::assertSame('1L', $camel->unitQuantity);

        $snake = Product::fromArray(['id' => '1', 'display_price' => 599, 'unit_quantity' => '1L']);
        self::assertSame(599, $snake->displayPrice);
        self::assertSame('1L', $snake->unitQuantity);
    }

    public function testCartHydratesItems(): void
    {
        $cart = Cart::fromArray([
            'id' => 'shopping_cart',
            'total_price' => 250,
            'items' => [['id' => 'l1', 'count' => 1], 'not-an-array'],
        ]);

        self::assertSame('shopping_cart', $cart->id);
        self::assertSame(250, $cart->totalPrice);
        self::assertCount(1, $cart->items); // the non-array entry is skipped
        self::assertSame('l1', $cart->items[0]->id);
    }

    public function testDeliveryExtractsSlotAndOrders(): void
    {
        $delivery = Delivery::fromArray([
            'delivery_id' => 'd-1',
            'status' => 'CURRENT',
            'slot' => ['slot_id' => 's-9'],
            'eta2' => ['start' => '10:00', 'end' => '11:00'],
            'orders' => [['id' => 'o-1'], ['id' => 'o-2'], ['noid' => true]],
        ]);

        self::assertSame('d-1', $delivery->deliveryId);
        self::assertSame('s-9', $delivery->slotId);
        self::assertSame('10:00', $delivery->eta2Start);
        self::assertSame(['o-1', 'o-2'], $delivery->orderIds);
    }

    public function testDefaultsWhenFieldsMissing(): void
    {
        $user = User::fromArray([]);
        self::assertNull($user->userId);
        self::assertSame([], $user->raw);
    }
}
