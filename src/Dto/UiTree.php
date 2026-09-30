<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * A UI tree is Picnic's server-driven page or content structure (Fusion pages, PML content, live tracking), kept as data in {@see $raw}.
 */
final readonly class UiTree
{
    /**
     * @param array<mixed> $raw the decoded tree, untouched
     */
    public function __construct(public array $raw)
    {
    }

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self($payload);
    }

    public function id(): ?string
    {
        $id = $this->raw['id'] ?? null;

        return is_string($id) ? $id : null;
    }

    /**
     * Every node whose `type` equals $type, in document order, nested ones included.
     *
     * @return list<array<mixed>>
     */
    public function findNodesOfType(string $type): array
    {
        return self::collectNodesOfType($this->raw, $type);
    }

    /**
     * @param array<mixed> $node
     *
     * @return list<array<mixed>>
     */
    private static function collectNodesOfType(array $node, string $type): array
    {
        $found = [];

        if (($node['type'] ?? null) === $type) {
            $found[] = $node;
        }

        foreach ($node as $value) {
            if (is_array($value)) {
                foreach (self::collectNodesOfType($value, $type) as $child) {
                    $found[] = $child;
                }
            }
        }

        return $found;
    }
}
