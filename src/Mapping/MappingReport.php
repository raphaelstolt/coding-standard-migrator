<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Mapping;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * All {@see Mapping} instances of a migration, in source rule order.
 *
 * @implements IteratorAggregate<int, Mapping>
 */
final readonly class MappingReport implements Countable, IteratorAggregate
{
    /**
     * @param list<Mapping> $mappings
     */
    public function __construct(
        private array $mappings = [],
    ) {}

    public function with(Mapping $mapping): self
    {
        return new self([...$this->mappings, $mapping]);
    }

    /**
     * @return list<Mapping>
     */
    public function all(): array
    {
        return $this->mappings;
    }

    /**
     * @return list<Mapping>
     */
    public function withOutcome(MappingOutcome $outcome): array
    {
        return \array_values(\array_filter(
            $this->mappings,
            static fn(Mapping $mapping): bool => $mapping->outcome === $outcome,
        ));
    }

    /**
     * @return list<Mapping>
     */
    public function needingAttention(): array
    {
        return \array_values(\array_filter(
            $this->mappings,
            static fn(Mapping $mapping): bool => $mapping->outcome->needsAttention(),
        ));
    }

    /**
     * @return array<string, int> the number of mappings per outcome, outcomes without
     *                            mappings omitted
     */
    public function summary(): array
    {
        $summary = [];

        foreach ($this->mappings as $mapping) {
            $label = $mapping->outcome->label();
            $summary[$label] = ($summary[$label] ?? 0) + 1;
        }

        return $summary;
    }

    /**
     * @return array<string, array<string, mixed>> target linter rules, keyed by rule name
     */
    public function linterRules(): array
    {
        return $this->mergedSettings(MappingOutcome::LinterRule);
    }

    /**
     * @return array<string, mixed> target formatter options, keyed by option name
     */
    public function formatterOptions(): array
    {
        return $this->mergedSettings(MappingOutcome::FormatterOption);
    }

    /**
     * Later mappings win, mirroring how the source tools resolve their own rule order.
     *
     * @return array<string, mixed>
     */
    private function mergedSettings(MappingOutcome $outcome): array
    {
        $settings = [];

        foreach ($this->withOutcome($outcome) as $mapping) {
            foreach ($mapping->settings as $name => $value) {
                $settings[$name] = $value;
            }
        }

        \ksort($settings);

        return $settings;
    }

    public function count(): int
    {
        return \count($this->mappings);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->mappings);
    }
}
