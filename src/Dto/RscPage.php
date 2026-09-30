<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * A React Server Components page (Content-Type text/x-component). Kept as data:
 * "rows" holds the JSON rows by hex id (row "0" is the root), "modules" the
 * client component references. Interpretation is left to the caller.
 */
final readonly class RscPage
{
    /**
     * @param array<string, mixed> $rows
     * @param array<string, mixed> $modules
     */
    public function __construct(
        public array $rows,
        public array $modules,
        public string $raw,
    ) {
    }

    public static function fromText(string $text): self
    {
        $rows = [];
        $modules = [];

        foreach (explode("\n", $text) as $line) {
            if (preg_match('/^([0-9a-f]+):(I?)(.*)$/s', $line, $matches) !== 1) {
                continue;
            }

            $decodedRow = json_decode($matches[3], true);
            if ($decodedRow === null && $matches[3] !== 'null') {
                continue;
            }

            if ($matches[2] === 'I') {
                $modules[$matches[1]] = $decodedRow;
            } else {
                $rows[$matches[1]] = $decodedRow;
            }
        }

        return new self($rows, $modules, $text);
    }
}
