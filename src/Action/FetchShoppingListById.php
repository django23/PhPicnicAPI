<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * A single shopping list (a UI tree).
 */
final readonly class FetchShoppingListById
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return array<mixed>
     */
    public function execute(string $shoppingListId): array
    {
        return $this->api->get(ApiEndpoint::SHOPPING_LIST->path($shoppingListId));
    }
}
