<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Search;

use PhPicnic\Search\SearchResultParser;
use PHPUnit\Framework\TestCase;

final class SearchResultParserTest extends TestCase
{
    public function testExtractsSellingUnitTilesFromNestedTree(): void
    {
        $tree = [
            'body' => ['child' => ['children' => [
                ['type' => 'HEADER', 'text' => 'Results'],
                ['type' => 'SELLING_UNIT_TILE', 'sole_article_id' => 'a1', 'sellingUnit' => ['id' => '1', 'name' => 'One']],
                ['type' => 'CONTAINER', 'children' => [
                    ['type' => 'SELLING_UNIT_TILE', 'sole_article_id' => 'a2', 'sellingUnit' => ['id' => '2', 'name' => 'Two']],
                ]],
            ]]],
        ];

        $products = SearchResultParser::parse($tree);

        self::assertCount(2, $products);
        self::assertSame(['1', '2'], array_map(static fn ($p) => $p->id, $products));
        self::assertSame('a1', $products[0]->soleArticleId);
        self::assertSame('a2', $products[1]->soleArticleId);
    }

    public function testIgnoresTilesWithoutSellingUnit(): void
    {
        $tree = [['type' => 'SELLING_UNIT_TILE', 'sellingUnit' => 'not-an-object']];

        self::assertSame([], SearchResultParser::parse($tree));
    }

    public function testEmptyTreeYieldsNoProducts(): void
    {
        self::assertSame([], SearchResultParser::parse([]));
    }
}
