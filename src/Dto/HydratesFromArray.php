<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * Defensive readers for hydrating DTOs from loosely-typed API payloads. Picnic
 * field shapes drift between API versions, so unknown/missing keys yield null
 * rather than errors, and the full payload is always kept in {@see $raw}.
 */
trait HydratesFromArray
{
    /**
     * @param array<mixed> $data
     */
    private static function str(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : (is_int($value) || is_float($value) ? (string) $value : null);
    }

    /**
     * @param array<mixed> $data
     */
    private static function int(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        return is_int($value) ? $value : (is_numeric($value) ? (int) $value : null);
    }

    /**
     * @param array<mixed> $data
     */
    private static function bool(array $data, string $key): ?bool
    {
        $value = $data[$key] ?? null;

        return is_bool($value) ? $value : null;
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    private static function arr(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        return is_array($value) ? $value : [];
    }
}
