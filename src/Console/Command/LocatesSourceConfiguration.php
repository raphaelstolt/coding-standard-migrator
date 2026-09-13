<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Console\Command;

use Stolt\CodingStandardMigrator\Exception\SourceConfigurationNotFound;
use Stolt\CodingStandardMigrator\Exception\UnsupportedMigrationPath;
use Stolt\CodingStandardMigrator\Migration\MigrationEngine;
use Stolt\CodingStandardMigrator\Standard\Standard;
use Symfony\Component\Console\Input\InputInterface;

/**
 * The `--working-dir` and `--config` handling shared by the commands which act on a
 * source configuration.
 */
trait LocatesSourceConfiguration
{
    private function workingDirectory(InputInterface $input): string
    {
        $workingDirectory = $this->stringOption($input, 'working-dir') ?? '.';
        $resolved = \realpath($workingDirectory);

        return $resolved !== false ? $resolved : $workingDirectory;
    }

    /**
     * @throws SourceConfigurationNotFound
     * @throws UnsupportedMigrationPath
     */
    private function sourceFile(
        InputInterface $input,
        MigrationEngine $engine,
        Standard $from,
        string $workingDirectory,
    ): string {
        $sourceFile = $this->stringOption($input, 'config');

        return $sourceFile !== null
            ? $this->resolve($workingDirectory, $sourceFile)
            : $engine->locateSourceConfiguration($from, $workingDirectory);
    }

    private function resolve(string $directory, string $path): string
    {
        if (\str_starts_with($path, \DIRECTORY_SEPARATOR) || \preg_match('/^[A-Za-z]:/', $path) === 1) {
            return $path;
        }

        return $directory . \DIRECTORY_SEPARATOR . $path;
    }

    private function stringOption(InputInterface $input, string $name): ?string
    {
        $value = $input->getOption($name);

        return \is_string($value) && $value !== '' ? $value : null;
    }
}
