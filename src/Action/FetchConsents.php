<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\Enum\ConsentStrategy;
use PhPicnic\LazyLoginApi;

/**
 * Open consent requests for the given topics.
 */
final readonly class FetchConsents
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @param list<string> $topics
     *
     * @return array<mixed>
     */
    public function execute(array $topics, ConsentStrategy $strategy = ConsentStrategy::WIDE): array
    {
        $query = implode('&', array_map(
            static fn (string $topic): string => 'consent_topics=' . rawurlencode($topic),
            $topics,
        ));

        return $this->api->get(ApiEndpoint::CONSENTS->path() . '?' . $query . ($query === '' ? '' : '&') . 'strategy=' . $strategy->value);
    }
}
