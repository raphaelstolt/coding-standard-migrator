<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Ruleset;

/**
 * A single entry of a source configuration, e.g. `'array_syntax' => ['syntax' => 'short']`.
 */
final readonly class Rule
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string $name,
        public bool $enabled = true,
        public array $options = [],
    ) {}

    /**
     * Whether this entry references a rule set instead of a single rule, e.g. `@PSR12`.
     */
    public function isRuleSet(): bool
    {
        return \str_starts_with($this->name, '@');
    }

    public function hasOptions(): bool
    {
        return $this->options !== [];
    }

    /**
     * @param list<string> $names
     */
    public function optionAmong(string $option, array $names, ?string $default = null): ?string
    {
        $value = $this->options[$option] ?? null;

        if (\is_string($value) && \in_array($value, $names, true)) {
            return $value;
        }

        return $default;
    }

    public function intOption(string $option, ?int $default = null): ?int
    {
        $value = $this->options[$option] ?? null;

        return \is_int($value) ? $value : $default;
    }

    public function boolOption(string $option, ?bool $default = null): ?bool
    {
        $value = $this->options[$option] ?? null;

        return \is_bool($value) ? $value : $default;
    }
}
