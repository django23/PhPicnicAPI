<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * Defensive readers for hydrating DTOs from loosely-typed API payloads. Picnic
 * field shapes drift between API versions, so unknown/missing keys yield null
 * rather than errors, and the full payload is always kept in {@see $raw}.
 */
final class PayloadReader
{
    /**
     * @param array<mixed> $payload
     */
    public static function readString(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        return is_string($value) ? $value : (is_int($value) || is_float($value) ? (string) $value : null);
    }

    /**
     * @param array<mixed> $payload
     */
    public static function readInt(array $payload, string $key): ?int
    {
        $value = $payload[$key] ?? null;

        return is_int($value) ? $value : (is_numeric($value) ? (int) $value : null);
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
}
