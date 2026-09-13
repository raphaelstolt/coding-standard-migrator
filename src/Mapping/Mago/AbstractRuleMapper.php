<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Mapping\Mago;

use Stolt\CodingStandardMigrator\Mapping\Mapping;
use Stolt\CodingStandardMigrator\Mapping\MappingFidelity;
use Stolt\CodingStandardMigrator\Mapping\MappingOutcome;
use Stolt\CodingStandardMigrator\Mapping\MappingReport;
use Stolt\CodingStandardMigrator\Mapping\RuleMapper;
use Stolt\CodingStandardMigrator\Ruleset\Rule;
use Stolt\CodingStandardMigrator\Ruleset\SourceConfiguration;
use Stolt\CodingStandardMigrator\Standard\Standard;

/**
 * The shared pipeline of all migrations targeting Mago.
 *
 * Subclasses only state which standard they migrate from and which {@see RuleTable}
 * translates its rule vocabulary.
 */
abstract readonly class AbstractRuleMapper implements RuleMapper
{
    public function __construct(
        protected RuleTable $table,
    ) {}

    final public function to(): Standard
    {
        return Standard::Mago;
    }

    final public function map(SourceConfiguration $source): MappingReport
    {
        $report = new MappingReport();

        foreach ($this->whitespaceMappings($source) as $mapping) {
            $report = $report->with($mapping);
        }

        foreach ($source->ruleset as $rule) {
            $report = $report->with($this->mapRule($rule));
        }

        return $report;
    }

    private function mapRule(Rule $rule): Mapping
    {
        $mapping = $this->table->lookup($rule);

        if ($rule->enabled) {
            return $mapping;
        }

        // A rule which is turned off in the source configuration only needs to be
        // carried over when the target can express the very same opt out.
        if ($mapping->outcome === MappingOutcome::LinterRule) {
            return Mapping::linterRules(
                $rule->name,
                \array_map(static fn(): array => ['enabled' => false], $mapping->settings),
                'Disabled in the source configuration.',
                // Carrying an opt out over is a faithful translation, the note only
                // states where the opt out comes from.
                MappingFidelity::Equivalent,
            );
        }

        return Mapping::skipped($rule->name, 'Disabled in the source configuration.');
    }

    /**
     * The indent and the line ending are configured outside of the rules of all
     * supported source standards, so they are mapped separately.
     *
     * @return list<Mapping>
     */
    private function whitespaceMappings(SourceConfiguration $source): array
    {
        $mappings = [];

        if ($source->indent !== null) {
            $options = ['use-tabs' => $source->usesTabs()];
            $indentWidth = $source->indentWidth();

            if ($indentWidth !== null) {
                $options['tab-width'] = $indentWidth;
            }

            $mappings[] = Mapping::formatterOptions($this->indentOrigin(), $options);
        }

        if ($source->lineEnding !== null) {
            $mappings[] = Mapping::formatterOptions($this->lineEndingOrigin(), [
                'end-of-line' => $source->lineEnding === "\r\n" ? 'crlf' : 'lf',
            ]);
        }

        return $mappings;
    }

    /**
     * How the source standard spells the setting the indent is taken from.
     */
    protected function indentOrigin(): string
    {
        return $this->from()->label() . ' indent';
    }

    /**
     * How the source standard spells the setting the line ending is taken from.
     */
    protected function lineEndingOrigin(): string
    {
        return $this->from()->label() . ' line ending';
    }
}
