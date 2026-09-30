<?php

declare(strict_types=1);

namespace PhPicnic;

/**
 * Pure helpers to build RFC 3986 query strings. Picnic repeats a key instead of using `key[]=`.
 */
final class QueryString
{
    /**
     * `key=a&key=b` for every value, or an empty string without values.
     */
    public static function repeated(string $key, string ...$values): string
    {
        return implode('&', array_map(
            static fn (string $value): string => rawurlencode($key) . '=' . rawurlencode($value),
            $values,
        ));
    }

    /**
     * @param array<string, string> $pairs
     */
    public static function fromPairs(array $pairs): string
    {
        return http_build_query($pairs, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Joins query fragments with `&`, skipping empty ones.
     */
    public static function join(string ...$fragments): string
    {
        return implode('&', array_filter($fragments, static fn (string $fragment): bool => $fragment !== ''));
    }

    /**
     * Appends the query to a path, or returns the path untouched when the query is empty.
     */
    public static function appendTo(string $path, string $query): string
    {
        return $query === '' ? $path : $path . '?' . $query;
    }
}
