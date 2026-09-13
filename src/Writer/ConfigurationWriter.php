<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Writer;

use Stolt\CodingStandardMigrator\Mapping\MappingReport;
use Stolt\CodingStandardMigrator\Migration\MigrationRequest;
use Stolt\CodingStandardMigrator\Ruleset\SourceConfiguration;
use Stolt\CodingStandardMigrator\Standard\Standard;

/**
 * Renders a mapping report as the configuration file of the target standard.
 *
 * Writers only render, persisting the rendered configuration is up to the caller.
 * One implementation per standard which can act as a migration target.
 */
interface ConfigurationWriter
{
    public function standard(): Standard;

    public function configurationFileName(): string;

    public function render(MigrationRequest $request, SourceConfiguration $source, MappingReport $report): string;
}
