<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Migration;

use Stolt\CodingStandardMigrator\Analysis\MigrationAnalysis;
use Stolt\CodingStandardMigrator\Mapping\MappingReport;
use Stolt\CodingStandardMigrator\Ruleset\SourceConfiguration;

/**
 * The rendered target configuration plus the report of how it came to be.
 */
final readonly class MigrationResult
{
    public function __construct(
        public MigrationRequest $request,
        public SourceConfiguration $source,
        public MappingReport $report,
        public string $targetFileName,
        public string $configuration,
    ) {}

    public function isComplete(): bool
    {
        return $this->report->needingAttention() === [];
    }

    public function analysis(): MigrationAnalysis
    {
        return MigrationAnalysis::of($this->request->to, $this->source, $this->report);
    }
}
