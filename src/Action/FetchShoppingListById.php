<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\AuthenticatedApi;

/**
 * A single shopping list (a UI tree).
 */
final readonly class FetchShoppingListById
{
    public function __construct(private AuthenticatedApi $api)
    {
    }

    /**
     * @return array<mixed>
     */
    public function execute(string $shoppingListId): array
    {
        return $this->api->get('/lists/' . $shoppingListId);
    }
}
