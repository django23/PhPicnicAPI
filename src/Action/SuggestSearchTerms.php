<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\PayloadReader;
use PhPicnic\Dto\SearchSuggestion;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Autocomplete suggestions for a search term.
 */
final readonly class SuggestSearchTerms
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @return list<SearchSuggestion>
     */
    public function execute(string $searchTerm): array
    {
        return PayloadReader::hydrateList(
            $this->api->get(ApiEndpoint::SUGGEST->path($searchTerm)),
            SearchSuggestion::fromArray(...),
        );
    }
}
