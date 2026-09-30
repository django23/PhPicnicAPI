<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\Cart;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Remove a product from the cart.
 */
final readonly class RemoveProductFromCart
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @param list<array<string, mixed>> $sellingUnitContexts where the product is added from (meal plan, recipe, selling group)
     */
    public function execute(string $productId, int $quantity = 1, array $sellingUnitContexts = []): Cart
    {
        $payload = ['product_id' => $productId, 'count' => $quantity];
        if ($sellingUnitContexts !== []) {
            $payload['selling_unit_contexts'] = $sellingUnitContexts;
        }

        return Cart::fromArray($this->api->post(ApiEndpoint::CART_REMOVE_PRODUCT->path(), $payload));
    }
}
