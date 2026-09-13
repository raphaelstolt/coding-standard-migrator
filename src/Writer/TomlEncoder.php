<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Writer;

use UnexpectedValueException;

/**
 * A minimal TOML encoder covering the value shapes a coding-standard configuration needs.
 */
final class TomlEncoder
{
    public function keyValue(string $key, mixed $value): string
    {
        return \sprintf('%s = %s', $key, $this->value($value));
    }

    /**
     * @param array<string, mixed> $table
     */
    public function inlineTable(array $table): string
    {
        if ($table === []) {
            return '{}';
        }

        $entries = [];

        foreach ($table as $key => $value) {
            $entries[] = $this->keyValue($key, $value);
        }

        return \sprintf('{ %s }', \implode(', ', $entries));
    }

    public function value(mixed $value): string
    {
        return match (true) {
            \is_bool($value) => $value ? 'true' : 'false',
            \is_int($value) => (string) $value,
            \is_float($value) => $this->float($value),
            \is_string($value) => $this->string($value),
            \is_array($value) => $this->array($value),
            default => throw new UnexpectedValueException(\sprintf(
                'Cannot encode a value of type %s as TOML.',
                \get_debug_type($value),
            )),
        };
    }

    private function string(string $value): string
    {
        return '"' . \str_replace(['\\', '"', "\n", "\r", "\t"], ['\\\\', '\"', '\n', '\r', '\t'], $value) . '"';
    }

    private function float(float $value): string
    {
        $encoded = (string) $value;

        return \str_contains($encoded, '.') || \str_contains($encoded, 'e') ? $encoded : $encoded . '.0';
    }

    /**
     * @param array<array-key, mixed> $value
     */
    private function array(array $value): string
    {
        if (!\array_is_list($value)) {
            /** @var array<string, mixed> $value */
            return $this->inlineTable($value);
        }

        return \sprintf('[%s]', \implode(', ', \array_map($this->value(...), $value)));
    }
}
