<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;
use PhPicnic\QueryString;

/**
 * In-app messages to show at the given positions (PROMPT, MESSAGE_BAR, ORDER_CONFIRMATION, STOREFRONT_DIALOG).
 */
final readonly class FetchMessages
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return array<mixed>
     */
    public function execute(string ...$displayPositions): array
    {
        return $this->api->get(QueryString::appendTo(
            ApiEndpoint::MESSAGES->path(),
            QueryString::repeated('display_position', ...$displayPositions),
        ));
    }
}
