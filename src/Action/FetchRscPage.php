<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\ClientIdentity;
use PhPicnic\Dto\RscPage;
use PhPicnic\Enum\PageId;
use PhPicnic\Exception\UnexpectedResponseFormatException;
use PhPicnic\LazyLoginApi;
use PhPicnic\PageRequest;

/**
 * A page that Picnic serves as React Server Components (needs a newer app version, see AppProfile).
 */
final readonly class FetchRscPage
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @param array<string, string> $query
     *
     * @throws UnexpectedResponseFormatException
     */
    public function execute(PageId|string $pageId, array $query = [], ?ClientIdentity $identityOverride = null): RscPage
    {
        $path = PageRequest::pathFor($pageId, $query);
        $text = $this->api->getText($path, $identityOverride);
        $page = RscPage::fromText($text);

        if ($page->rows === [] && $page->modules === [] && trim($text) !== '') {
            throw new UnexpectedResponseFormatException(
                sprintf('"%s" did not answer with a React Server Components page. Use fetchPage(), or pick a newer AppProfile.', $path),
                $path,
                'unknown',
                $text,
            );
        }

        return $page;
    }
}
