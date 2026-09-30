<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Dto;

use PhPicnic\Dto\UiTree;
use PHPUnit\Framework\TestCase;

final class UiTreeTest extends TestCase
{
    public function testKeepsThePayloadInRaw(): void
    {
        $payload = ['id' => 'page', 'children' => [['type' => 'TEXT']]];

        self::assertSame($payload, UiTree::fromArray($payload)->raw);
    }

    public function testIdIsTheRootIdWhenItIsAString(): void
    {
        self::assertSame('page-root', UiTree::fromArray(['id' => 'page-root'])->id());
    }

    public function testIdIsNullWhenMissingOrNotAString(): void
    {
        self::assertNull(UiTree::fromArray([])->id());
        self::assertNull(UiTree::fromArray(['id' => 42])->id());
    }

    public function testFindsNestedNodesOfATypeInDocumentOrder(): void
    {
        $tree = UiTree::fromArray([
            'type' => 'PAGE',
            'body' => [
                ['type' => 'TILE', 'id' => 'a', 'content' => ['type' => 'TILE', 'id' => 'b']],
                ['type' => 'TEXT', 'id' => 'c'],
                ['type' => 'TILE', 'id' => 'd'],
            ],
        ]);

        $ids = array_map(static fn (array $node): mixed => $node['id'], $tree->findNodesOfType('TILE'));

        self::assertSame(['a', 'b', 'd'], $ids);
    }

    public function testIncludesTheRootWhenItMatches(): void
    {
        self::assertCount(1, UiTree::fromArray(['type' => 'PAGE'])->findNodesOfType('PAGE'));
    }

    public function testFindsNothingWhenNoNodeMatches(): void
    {
        self::assertSame([], UiTree::fromArray(['type' => 'PAGE', 'body' => [['type' => 'TEXT']]])->findNodesOfType('TILE'));
    }
}
