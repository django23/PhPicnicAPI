<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * In-app messages to show at the given positions (PROMPT, MESSAGE_BAR, ORDER_CONFIRMATION, STOREFRONT_DIALOG).
 */
final readonly class FetchMessages
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @param list<string> $displayPositions
     *
     * @return array<mixed>
     */
    public function execute(array $displayPositions = []): array
    {
        $query = implode('&', array_map(
            static fn (string $position): string => 'display_position=' . rawurlencode($position),
            $displayPositions,
        ));

        return $this->api->get(ApiEndpoint::MESSAGES->path() . ($query === '' ? '' : '?' . $query));
    }
}
