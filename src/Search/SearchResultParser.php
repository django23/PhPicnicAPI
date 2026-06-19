<?php

declare(strict_types=1);

namespace PhPicnic\Search;

use PhPicnic\Dto\Product;

/**
 * Parses the nested "PML" UI tree returned by /pages/search-page-results into a
 * flat list of {@see Product}s.
 *
 * Picnic no longer exposes a flat search endpoint; results are embedded as
 * "SELLING_UNIT_TILE" nodes carrying a "sellingUnit" payload. This walks the
 * whole tree, collects those nodes, and attaches the sole_article_id found in
 * the surrounding node (mirrors the reference Python implementation).
 */
final class SearchResultParser
{
    /**
     * @param array<mixed> $response
     *
     * @return list<Product>
     */
    public static function parse(array $response): array
    {
        $products = [];

        foreach (self::findSellingUnitTiles($response) as $node) {
            /** @var array<mixed> $sellingUnit */
            $sellingUnit = $node['sellingUnit'];
            $sellingUnit['sole_article_id'] ??= self::findSoleArticleId($node);
            $products[] = Product::fromArray($sellingUnit);
        }

        return $products;
    }

    /**
     * @param array<mixed> $node
     *
     * @return list<array<mixed>>
     */
    private static function findSellingUnitTiles(array $node): array
    {
        $found = [];

        if (($node['type'] ?? null) === 'SELLING_UNIT_TILE' && isset($node['sellingUnit']) && is_array($node['sellingUnit'])) {
            $found[] = $node;
        }

        foreach ($node as $value) {
            if (is_array($value)) {
                foreach (self::findSellingUnitTiles($value) as $child) {
                    $found[] = $child;
                }
            }
        }

        return $found;
    }

    /**
     * @param array<mixed> $node
     */
    private static function findSoleArticleId(array $node): ?string
    {
        $json = json_encode($node);
        if ($json !== false && preg_match('/"sole_article_id":"(\w+)"/', $json, $m) === 1) {
            return $m[1];
        }

        return null;
    }
}
