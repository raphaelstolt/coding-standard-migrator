<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Analysis;

use Stolt\CodingStandardMigrator\Mapping\Mapping;
use Stolt\CodingStandardMigrator\Mapping\MappingFidelity;
use Stolt\CodingStandardMigrator\Mapping\MappingOutcome;
use Stolt\CodingStandardMigrator\Mapping\MappingReport;
use Stolt\CodingStandardMigrator\Ruleset\SourceConfiguration;
use Stolt\CodingStandardMigrator\Standard\Standard;

/**
 * What a migration would do to a source configuration, in numbers.
 *
 * Made to be looked at before a migration, to tell whether the target standard is a
 * fit at all and how much manual work is left afterwards.
 */
final readonly class MigrationAnalysis
{
    /**
     * The weight each outcome contributes to the migration confidence.
     *
     * A rule which the target standard enforces anyway, or which was disabled in the
     * source in the first place, is migrated just as completely as a translated one,
     * hence the full weight. A partial translation counts half, as its scope or its
     * options still need a review.
     */
    private const WEIGHTS = [
        'equivalent' => 1.0,
        'partial' => 0.5,
        'redundant' => 1.0,
        'disabled' => 1.0,
        'unsupported' => 0.0,
        'unknown' => 0.0,
    ];

    /**
     * @param list<string> $needsAttention the rules without a counterpart in the target
     */
    private function __construct(
        public Standard $from,
        public Standard $to,
        public string $file,
        public int $rules,
        public int $equivalent,
        public int $partial,
        public int $linterCandidates,
        public int $redundant,
        public int $disabled,
        public int $unsupported,
        public int $unknown,
        public array $needsAttention,
    ) {}

    /**
     * Counts the mappings which stem from a rule of the source configuration. The
     * mappings a mapper derives from settings outside of the rules, such as the indent,
     * are left out, so that the rule count matches the source configuration.
     */
    public static function of(Standard $to, SourceConfiguration $source, MappingReport $report): self
    {
        $mappings = \array_values(\array_filter($report->all(), static fn(Mapping $mapping): bool => $source->ruleset->has($mapping->sourceRule)));

        $countOf = static fn(callable $matches): int => \count(\array_filter($mappings, $matches));

        return new self(
            from: $source->standard,
            to: $to,
            file: $source->file,
            rules: \count($mappings),
            equivalent: $countOf(
                static fn(Mapping $mapping): bool => $mapping->hasFidelity(MappingFidelity::Equivalent),
            ),
            partial: $countOf(static fn(Mapping $mapping): bool => $mapping->hasFidelity(MappingFidelity::Partial)),
            linterCandidates: $countOf(
                static fn(Mapping $mapping): bool => $mapping->outcome === MappingOutcome::LinterRule,
            ),
            redundant: $countOf(
                static fn(Mapping $mapping): bool => $mapping->outcome === MappingOutcome::CoveredByFormatter,
            ),
            disabled: $countOf(static fn(Mapping $mapping): bool => $mapping->outcome === MappingOutcome::Skipped),
            unsupported: $countOf(
                static fn(Mapping $mapping): bool => $mapping->outcome === MappingOutcome::Unsupported,
            ),
            unknown: $countOf(static fn(Mapping $mapping): bool => $mapping->outcome === MappingOutcome::Unknown),
            needsAttention: \array_values(\array_map(
                static fn(Mapping $mapping): string => $mapping->sourceRule,
                \array_filter($mappings, static fn(Mapping $mapping): bool => $mapping->outcome->needsAttention()),
            )),
        );
    }

    /**
     * The rules which end up in the target configuration, faithfully or partially.
     */
    public function mappable(): int
    {
        return $this->equivalent + $this->partial;
    }

    /**
     * The share of the source configuration which survives the migration, in percent,
     * or null when there is nothing to migrate.
     */
    public function confidence(): ?int
    {
        if ($this->rules === 0) {
            return null;
        }

        $weighted =
            (self::WEIGHTS['equivalent'] * $this->equivalent)
            + (self::WEIGHTS['partial'] * $this->partial)
            + (self::WEIGHTS['redundant'] * $this->redundant)
            + (self::WEIGHTS['disabled'] * $this->disabled);

        return (int) \round(($weighted / $this->rules) * 100);
    }

    public function isComplete(): bool
    {
        return $this->needsAttention === [];
    }

    /**
     * The counts to report, in reading order.
     *
     * @return array<string, int>
     */
    public function counts(): array
    {
        return [
            'Rules' => $this->rules,
            'Mappable' => $this->mappable(),
            'Equivalent' => $this->equivalent,
            'Partial' => $this->partial,
            'Unsupported' => $this->unsupported,
            'No mapping known' => $this->unknown,
            'Disabled in source' => $this->disabled,
            'Linter candidates' => $this->linterCandidates,
            'Redundant with ' . $this->to->label() => $this->redundant,
        ];
    }
}
