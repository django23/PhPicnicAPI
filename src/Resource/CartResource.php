<?php

declare(strict_types=1);

namespace PhPicnic\Resource;

use InvalidArgumentException;
use PhPicnic\Action\AddProductsToCart;
use PhPicnic\Action\AddProductToCart;
use PhPicnic\Action\EmptyShoppingCart;
use PhPicnic\Action\FetchMinimumOrderValue;
use PhPicnic\Action\FetchShoppingCart;
use PhPicnic\Action\RemoveGroupFromCart;
use PhPicnic\Action\RemoveProductFromCart;
use PhPicnic\Action\SelectDeliverySlotForCart;
use PhPicnic\Dto\Cart;
use PhPicnic\Dto\MinimumOrderValue;
use PhPicnic\LazyLoginApi;

/**
 * Shopping cart operations: `$picnic->cart()`.
 */
final readonly class CartResource
{
    private FetchShoppingCart $fetchShoppingCart;

    private AddProductToCart $addProductToCart;

    private AddProductsToCart $addProductsToCart;

    private RemoveProductFromCart $removeProductFromCart;

    private RemoveGroupFromCart $removeGroupFromCart;

    private EmptyShoppingCart $emptyShoppingCart;

    private SelectDeliverySlotForCart $selectDeliverySlotForCart;

    private FetchMinimumOrderValue $fetchMinimumOrderValue;

    public function __construct(LazyLoginApi $api)
    {
        $this->fetchShoppingCart = new FetchShoppingCart($api);
        $this->addProductToCart = new AddProductToCart($api);
        $this->addProductsToCart = new AddProductsToCart($api);
        $this->removeProductFromCart = new RemoveProductFromCart($api);
        $this->removeGroupFromCart = new RemoveGroupFromCart($api);
        $this->emptyShoppingCart = new EmptyShoppingCart($api);
        $this->selectDeliverySlotForCart = new SelectDeliverySlotForCart($api);
        $this->fetchMinimumOrderValue = new FetchMinimumOrderValue($api);
    }

    public function fetch(): Cart
    {
        return $this->fetchShoppingCart->execute();
    }

    /**
     * @param list<array<string, mixed>> $sellingUnitContexts where the product is added from (meal plan, recipe, selling group)
     */
    public function addProduct(string $productId, int $quantity = 1, array $sellingUnitContexts = []): Cart
    {
        return $this->addProductToCart->execute($productId, $quantity, $sellingUnitContexts);
    }

    /**
     * @param array<int|string, int> $quantitiesByProductId map of product id => quantity
     *
     * @throws InvalidArgumentException when given an empty map
     */
    public function addMultipleProducts(array $quantitiesByProductId): Cart
    {
        return $this->addProductsToCart->execute($quantitiesByProductId);
    }

    /**
     * @param list<array<string, mixed>> $sellingUnitContexts where the product is removed from (meal plan, recipe, selling group)
     */
    public function removeProduct(string $productId, int $quantity = 1, array $sellingUnitContexts = []): Cart
    {
        return $this->removeProductFromCart->execute($productId, $quantity, $sellingUnitContexts);
    }

    public function removeGroup(string $groupId): Cart
    {
        return $this->removeGroupFromCart->execute($groupId);
    }

    public function empty(): Cart
    {
        return $this->emptyShoppingCart->execute();
    }

    public function selectDeliverySlot(string $deliverySlotId): Cart
    {
        return $this->selectDeliverySlotForCart->execute($deliverySlotId);
    }

    public function fetchMinimumOrderValue(): MinimumOrderValue
    {
        return $this->fetchMinimumOrderValue->execute();
    }
}
