<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

use PhPicnic\Exception\MalformedResponseException;

/**
 * Defensive readers for hydrating DTOs from loosely-typed API payloads. Picnic
 * field shapes drift between API versions, so optional fields yield null when
 * absent. Fields a DTO cannot exist without are read with the "required"
 * readers and throw. The full payload is always kept in the DTO's $raw.
 */
final class PayloadReader
{
    /**
     * @param array<mixed> $payload
     */
    public static function readString(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }

    /**
     * The first key (in order) that holds a string, for fields Picnic names
     * differently across API versions (camelCase vs snake_case).
     *
     * @param array<mixed> $payload
     */
    public static function readFirstString(array $payload, string ...$keys): ?string
    {
        foreach ($keys as $key) {
            $value = self::readString($payload, $key);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<mixed> $payload
     *
     * @throws MalformedResponseException when none of the keys holds a value
     */
    public static function readRequiredString(array $payload, string ...$keys): string
    {
        return self::readFirstString($payload, ...$keys)
            ?? throw new MalformedResponseException(
                sprintf('Picnic response is missing "%s".', implode('" or "', $keys)),
                $payload,
            );
    }

    /**
     * @param array<mixed> $payload
     */
    public static function readInt(array $payload, string $key): ?int
    {
        $value = $payload[$key] ?? null;

        if (is_int($value)) {
            return $value;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param array<mixed> $payload
     */
    public static function readFirstInt(array $payload, string ...$keys): ?int
    {
        foreach ($keys as $key) {
            $value = self::readInt($payload, $key);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<mixed> $payload
     */
    public static function readBool(array $payload, string $key): ?bool
    {
        $value = $payload[$key] ?? null;

        return is_bool($value) ? $value : null;
    }

    /**
     * @param array<mixed> $payload
     *
     * @return array<mixed>
     */
    public static function readArray(array $payload, string $key): array
    {
        $value = $payload[$key] ?? null;

        return is_array($value) ? $value : [];
    }

    /**
     * Hydrate every array entry of a list payload, skipping non-array entries.
     *
     * @template T
     *
     * @param array<mixed>              $items
     * @param callable(array<mixed>): T $hydrate
     *
     * @return list<T>
     */
    public static function hydrateList(array $items, callable $hydrate): array
    {
        $hydratedItems = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $hydratedItems[] = $hydrate($item);
            }
        }

        return $hydratedItems;
    }
}
