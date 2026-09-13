<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Writer;

use Stolt\CodingStandardMigrator\Mapping\MappingReport;
use Stolt\CodingStandardMigrator\Migration\MigrationRequest;
use Stolt\CodingStandardMigrator\Ruleset\SourceConfiguration;
use Stolt\CodingStandardMigrator\Standard\Standard;

/**
 * Renders a mago.toml configuration.
 *
 * @see https://mago.carthage.software/main/en/guide/configuration/
 */
final readonly class MagoConfigurationWriter implements ConfigurationWriter
{
    public function __construct(
        private TomlEncoder $encoder = new TomlEncoder(),
    ) {}

    public function standard(): Standard
    {
        return Standard::Mago;
    }

    public function configurationFileName(): string
    {
        return 'mago.toml';
    }

    public function render(MigrationRequest $request, SourceConfiguration $source, MappingReport $report): string
    {
        $lines = $this->header($source);
        $phpVersion = $request->phpVersion ?? $source->phpVersion;

        if ($phpVersion !== null) {
            $lines[] = '';
            $lines[] = $this->encoder->keyValue('php-version', $phpVersion);
        }

        $lines = [
            ...$lines,
            ...$this->sourceSection($source),
            ...$this->formatterSection($report),
            ...$this->linterSection($report),
            ...$this->openQuestions($report),
        ];

        return \implode("\n", $lines) . "\n";
    }

    /**
     * @return list<string>
     */
    private function header(SourceConfiguration $source): array
    {
        $lines = [
            \sprintf(
                '# Migrated from the %s configuration %s by stolt/coding-standard-migrator.',
                $source->standard->label(),
                \basename($source->file),
            ),
            '# Review the settings below before committing them.',
        ];

        if ($source->riskyAllowed) {
            $lines[] = '# The source configuration allowed risky rules, verify the Mago counterparts.';
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function sourceSection(SourceConfiguration $source): array
    {
        if ($source->paths === []) {
            return [];
        }

        return [
            '',
            '[source]',
            $this->encoder->keyValue('paths', $source->paths),
        ];
    }

    /**
     * @return list<string>
     */
    private function formatterSection(MappingReport $report): array
    {
        $options = $report->formatterOptions();

        if ($options === []) {
            return [];
        }

        $lines = ['', '[formatter]'];

        foreach ($options as $option => $value) {
            $lines[] = $this->encoder->keyValue($option, $value);
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function linterSection(MappingReport $report): array
    {
        $rules = $report->linterRules();

        if ($rules === []) {
            return [];
        }

        $lines = ['', '[linter.rules]'];

        foreach ($rules as $rule => $options) {
            $lines[] = $this->encoder->keyValue($rule, $options === [] ? ['enabled' => true] : $options);
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function openQuestions(MappingReport $report): array
    {
        $mappings = $report->needingAttention();

        if ($mappings === []) {
            return [];
        }

        $lines = ['', '# Rules without a Mago counterpart in this migration:'];

        foreach ($mappings as $mapping) {
            $lines[] = \sprintf('# - %s%s', $mapping->sourceRule, $mapping->note !== null ? ': ' . $mapping->note : '');
        }

        return $lines;
    }
}
