<?php

declare(strict_types=1);

namespace PhPicnic\Resource;

use PhPicnic\Action\FetchAllShoppingLists;
use PhPicnic\Action\FetchShoppingListById;
use PhPicnic\Action\FetchShoppingListSublist;
use PhPicnic\LazyLoginApi;

/**
 * Shopping lists: `$picnic->shoppingLists()`.
 */
final readonly class ShoppingListResource
{
    private FetchAllShoppingLists $fetchAllShoppingLists;

    private FetchShoppingListById $fetchShoppingListById;

    private FetchShoppingListSublist $fetchShoppingListSublist;

    public function __construct(LazyLoginApi $api)
    {
        $this->fetchAllShoppingLists = new FetchAllShoppingLists($api);
        $this->fetchShoppingListById = new FetchShoppingListById($api);
        $this->fetchShoppingListSublist = new FetchShoppingListSublist($api);
    }

    /**
     * @return array<mixed>
     */
    public function fetchAll(): array
    {
        return $this->fetchAllShoppingLists->execute();
    }

    /**
     * @return array<mixed>
     */
    public function fetchById(string $shoppingListId): array
    {
        return $this->fetchShoppingListById->execute($shoppingListId);
    }

    /**
     * @return array<mixed>
     */
    public function fetchSublist(string $shoppingListId, string $sublistId): array
    {
        return $this->fetchShoppingListSublist->execute($shoppingListId, $sublistId);
    }
}
