<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\Enum\ConsentStrategy;
use PhPicnic\LazyLoginApi;
use PhPicnic\QueryString;

/**
 * Open consent requests for the given topics.
 */
final readonly class FetchConsents
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return array<mixed>
     */
    public function execute(ConsentStrategy $strategy, string ...$topics): array
    {
        $query = QueryString::join(
            QueryString::repeated('consent_topics', ...$topics),
            QueryString::fromPairs(['strategy' => $strategy->value]),
        );

        return $this->api->get(QueryString::appendTo(ApiEndpoint::CONSENTS->path(), $query));
    }
}
