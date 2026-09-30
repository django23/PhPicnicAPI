<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\AuthenticatedApi;

/**
 * A sublist of a shopping list (a UI tree).
 */
final readonly class FetchShoppingListSublist
{
    public function __construct(private AuthenticatedApi $api)
    {
    }

    /**
     * @return array<mixed>
     */
    public function execute(string $shoppingListId, string $sublistId): array
    {
        return $this->api->get('/lists/' . $shoppingListId . '?sublist=' . rawurlencode($sublistId));
    }
}
