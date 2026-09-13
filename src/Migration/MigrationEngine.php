<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Migration;

use Stolt\CodingStandardMigrator\Analysis\MigrationAnalysis;
use Stolt\CodingStandardMigrator\Exception\SourceConfigurationNotFound;
use Stolt\CodingStandardMigrator\Exception\UnsupportedMigrationPath;
use Stolt\CodingStandardMigrator\Mapping\MappingReport;
use Stolt\CodingStandardMigrator\Ruleset\SourceConfiguration;
use Stolt\CodingStandardMigrator\Standard\Standard;
use Stolt\CodingStandardMigrator\Standard\StandardRegistry;

/**
 * Drives a migration: read the source configuration, map its rules, render the target
 * configuration. Persisting the result is left to the caller.
 */
final readonly class MigrationEngine
{
    public function __construct(
        private StandardRegistry $registry = new StandardRegistry(),
    ) {}

    public function registry(): StandardRegistry
    {
        return $this->registry;
    }

    /**
     * @throws SourceConfigurationNotFound
     * @throws UnsupportedMigrationPath
     */
    public function locateSourceConfiguration(Standard $from, string $directory): string
    {
        $candidates = $this->registry->readerFor($from)->configurationFileNames();

        foreach ($candidates as $candidate) {
            $file = $directory . \DIRECTORY_SEPARATOR . $candidate;

            if (\is_file($file)) {
                return $file;
            }
        }

        throw SourceConfigurationNotFound::inDirectory($from, $directory, $candidates);
    }

    /**
     * @throws UnsupportedMigrationPath
     * @throws SourceConfigurationNotFound
     */
    public function migrate(MigrationRequest $request): MigrationResult
    {
        [$source, $report] = $this->plan($request);
        $writer = $this->registry->writerFor($request->to);

        return new MigrationResult(
            request: $request,
            source: $source,
            report: $report,
            targetFileName: $writer->configurationFileName(),
            configuration: $writer->render($request, $source, $report),
        );
    }

    /**
     * Reads and maps the source configuration without rendering the target one, to tell
     * upfront how much of it would survive the migration.
     *
     * @throws UnsupportedMigrationPath
     * @throws SourceConfigurationNotFound
     */
    public function analyze(MigrationRequest $request): MigrationAnalysis
    {
        [$source, $report] = $this->plan($request);

        return MigrationAnalysis::of($request->to, $source, $report);
    }

    /**
     * @return array{0: SourceConfiguration, 1: MappingReport}
     *
     * @throws UnsupportedMigrationPath
     * @throws SourceConfigurationNotFound
     */
    private function plan(MigrationRequest $request): array
    {
        if (!$this->registry->supports($request->from, $request->to)) {
            throw UnsupportedMigrationPath::between($request->from, $request->to, $this->registry->migrationPaths());
        }

        $source = $this->registry->readerFor($request->from)->read($request->sourceFile);

        if ($request->paths !== []) {
            $source = $source->withPaths($request->paths);
        }

        return [$source, $this->registry->mapperFor($request->from, $request->to)->map($source)];
    }
}
