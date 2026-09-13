<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Ruleset;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Stolt\CodingStandardMigrator\Exception\InvalidSourceConfiguration;
use Traversable;

/**
 * A tool-agnostic, ordered collection of {@see Rule} instances.
 *
 * @implements IteratorAggregate<int, Rule>
 */
final readonly class Ruleset implements Countable, IteratorAggregate
{
    /**
     * @param list<Rule> $rules
     */
    private function __construct(
        private array $rules,
    ) {}

    /**
     * @param list<Rule> $rules
     */
    public static function fromRules(array $rules): self
    {
        return new self($rules);
    }

    /**
     * Normalises the `rule name => true|false|options` shape which PHP-CS-Fixer,
     * PHP_CodeSniffer, and Pint all use in one variation or another.
     *
     * The keys are deliberately not narrowed to strings, as the array stems from an
     * included configuration file and is therefore unvalidated input.
     *
     * @param array<array-key, mixed> $rules
     *
     * @throws InvalidSourceConfiguration
     */
    public static function fromArray(array $rules): self
    {
        $normalized = [];

        foreach ($rules as $name => $configuration) {
            if (!\is_string($name) || $name === '') {
                throw InvalidSourceConfiguration::nonStringRuleName();
            }

            $normalized[] = match (true) {
                \is_bool($configuration) => new Rule($name, $configuration),
                \is_array($configuration) => new Rule($name, true, self::normalizeOptions($name, $configuration)),
                default => throw InvalidSourceConfiguration::unexpectedRuleConfiguration($name, $configuration),
            };
        }

        return new self($normalized);
    }

    /**
     * @param array<array-key, mixed> $options
     *
     * @return array<string, mixed>
     */
    private static function normalizeOptions(string $rule, array $options): array
    {
        $normalized = [];

        foreach ($options as $option => $value) {
            if (!\is_string($option)) {
                throw InvalidSourceConfiguration::unexpectedRuleConfiguration($rule, $options);
            }

            $normalized[$option] = $value;
        }

        return $normalized;
    }

    public function has(string $name): bool
    {
        return $this->get($name) !== null;
    }

    public function get(string $name): ?Rule
    {
        foreach ($this->rules as $rule) {
            if ($rule->name === $name) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return \array_map(static fn(Rule $rule): string => $rule->name, $this->rules);
    }

    public function isEmpty(): bool
    {
        return $this->rules === [];
    }

    public function count(): int
    {
        return \count($this->rules);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->rules);
    }
}
