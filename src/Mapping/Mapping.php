<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Mapping;

/**
 * The outcome of mapping a single source rule onto the target standard.
 */
final readonly class Mapping
{
    /**
     * @param array<string, mixed>  $settings target settings, keyed by linter rule name
     *                                        or by formatter option name
     * @param MappingFidelity|null  $fidelity only set for translated outcomes
     */
    private function __construct(
        public string $sourceRule,
        public MappingOutcome $outcome,
        public array $settings = [],
        public ?string $note = null,
        public ?MappingFidelity $fidelity = null,
    ) {}

    /**
     * @param array<string, array<string, mixed>> $rules target linter rules and their options
     */
    public static function linterRules(
        string $sourceRule,
        array $rules,
        ?string $note = null,
        ?MappingFidelity $fidelity = null,
    ): self {
        return new self(
            $sourceRule,
            MappingOutcome::LinterRule,
            $rules,
            $note,
            $fidelity ?? MappingFidelity::ofNote($note),
        );
    }

    /**
     * @param array<string, mixed> $options target formatter options
     */
    public static function formatterOptions(
        string $sourceRule,
        array $options,
        ?string $note = null,
        ?MappingFidelity $fidelity = null,
    ): self {
        return new self(
            $sourceRule,
            MappingOutcome::FormatterOption,
            $options,
            $note,
            $fidelity ?? MappingFidelity::ofNote($note),
        );
    }

    public static function coveredByFormatter(string $sourceRule, ?string $note = null): self
    {
        return new self($sourceRule, MappingOutcome::CoveredByFormatter, [], $note);
    }

    public static function skipped(string $sourceRule, ?string $note = null): self
    {
        return new self($sourceRule, MappingOutcome::Skipped, [], $note);
    }

    public static function unsupported(string $sourceRule, ?string $note = null): self
    {
        return new self($sourceRule, MappingOutcome::Unsupported, [], $note);
    }

    public static function unknown(string $sourceRule, ?string $note = null): self
    {
        return new self($sourceRule, MappingOutcome::Unknown, [], $note);
    }

    public function hasFidelity(MappingFidelity $fidelity): bool
    {
        return $this->fidelity === $fidelity;
    }

    /**
     * @return list<string>
     */
    public function targets(): array
    {
        return \array_keys($this->settings);
    }
}
