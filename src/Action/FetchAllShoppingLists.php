<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * All shopping lists (a UI tree).
 */
final readonly class FetchAllShoppingLists
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return array<mixed>
     */
    public function execute(): array
    {
        return $this->api->get(ApiEndpoint::SHOPPING_LISTS->path());
    }
}
