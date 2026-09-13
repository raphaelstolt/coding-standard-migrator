<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Reader;

use Stolt\CodingStandardMigrator\Exception\InvalidSourceConfiguration;
use Stolt\CodingStandardMigrator\Exception\SourceConfigurationNotFound;
use Stolt\CodingStandardMigrator\Ruleset\SourceConfiguration;
use Stolt\CodingStandardMigrator\Standard\Standard;

/**
 * Turns the configuration file of a coding standard into a {@see SourceConfiguration}.
 *
 * One implementation per standard which can act as a migration source.
 */
interface ConfigurationReader
{
    public function standard(): Standard;

    /**
     * The configuration file names to look for, in descending precedence.
     *
     * @return non-empty-list<string>
     */
    public function configurationFileNames(): array;

    /**
     * @throws SourceConfigurationNotFound
     * @throws InvalidSourceConfiguration
     */
    public function read(string $file): SourceConfiguration;
}
