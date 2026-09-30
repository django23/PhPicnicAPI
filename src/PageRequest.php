<?php

declare(strict_types=1);

namespace PhPicnic;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\Enum\PageId;

/**
 * Builds the path of a /pages/{id} request, with its query string.
 */
final class PageRequest
{
    /**
     * @param array<string, string> $query
     */
    public static function pathFor(PageId|string $pageId, array $query = []): string
    {
        $pageIdValue = $pageId instanceof PageId ? $pageId->value : $pageId;
        $path = ApiEndpoint::PAGE->path($pageIdValue);

        if ($query === []) {
            return $path;
        }

        return $path . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}
