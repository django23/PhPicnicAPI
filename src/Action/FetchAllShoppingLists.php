<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\AuthenticatedApi;

/**
 * All shopping lists (a UI tree).
 */
final readonly class FetchAllShoppingLists
{
    public function __construct(private AuthenticatedApi $api)
    {
    }

    /**
     * @return array<mixed>
     */
    public function execute(): array
    {
        return $this->api->get('/lists');
    }
}
