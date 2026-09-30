<?php

declare(strict_types=1);

namespace PhPicnic\Resource;

use InvalidArgumentException;
use PhPicnic\Action\AddProductsToCart;
use PhPicnic\Action\AddProductToCart;
use PhPicnic\Action\EmptyShoppingCart;
use PhPicnic\Action\FetchShoppingCart;
use PhPicnic\Action\RemoveProductFromCart;
use PhPicnic\Action\SelectDeliverySlotForCart;
use PhPicnic\Dto\Cart;
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

    private EmptyShoppingCart $emptyShoppingCart;

    private SelectDeliverySlotForCart $selectDeliverySlotForCart;

    public function __construct(LazyLoginApi $api)
    {
        $this->fetchShoppingCart = new FetchShoppingCart($api);
        $this->addProductToCart = new AddProductToCart($api);
        $this->addProductsToCart = new AddProductsToCart($api);
        $this->removeProductFromCart = new RemoveProductFromCart($api);
        $this->emptyShoppingCart = new EmptyShoppingCart($api);
        $this->selectDeliverySlotForCart = new SelectDeliverySlotForCart($api);
    }

    public function fetch(): Cart
    {
        return $this->fetchShoppingCart->execute();
    }

    public function addProduct(string $productId, int $quantity = 1): Cart
    {
        return $this->addProductToCart->execute($productId, $quantity);
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

    public function removeProduct(string $productId, int $quantity = 1): Cart
    {
        return $this->removeProductFromCart->execute($productId, $quantity);
    }

    public function empty(): Cart
    {
        return $this->emptyShoppingCart->execute();
    }

    public function selectDeliverySlot(string $deliverySlotId): Cart
    {
        return $this->selectDeliverySlotForCart->execute($deliverySlotId);
    }
}
