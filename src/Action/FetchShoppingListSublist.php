<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * A sublist of a shopping list (a UI tree).
 */
final readonly class FetchShoppingListSublist
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return array<mixed>
     */
    public function execute(string $shoppingListId, string $sublistId): array
    {
        return $this->api->get(ApiEndpoint::SHOPPING_LIST_SUBLIST->path($shoppingListId, $sublistId));
    }
}
