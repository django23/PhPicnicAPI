<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\ClientIdentity;
use PhPicnic\Dto\UiTree;
use PhPicnic\Enum\PageId;
use PhPicnic\Exception\UnexpectedResponseFormatException;
use PhPicnic\LazyLoginApi;
use PhPicnic\PageRequest;

/**
 * Any Fusion page as a JSON UI tree. Throws an UnexpectedResponseFormatException when Picnic answers with React Server Components instead: use FetchRscPage.
 */
final readonly class FetchPage
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @param array<string, string> $query
     *
     * @throws UnexpectedResponseFormatException
     */
    public function execute(PageId|string $pageId, array $query = [], ?ClientIdentity $identityOverride = null): UiTree
    {
        return UiTree::fromArray($this->api->get(PageRequest::pathFor($pageId, $query), $identityOverride));
    }
}
