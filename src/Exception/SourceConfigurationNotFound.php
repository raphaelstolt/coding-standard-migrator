<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Exception;

use RuntimeException;
use Stolt\CodingStandardMigrator\Standard\Standard;

final class SourceConfigurationNotFound extends RuntimeException implements Exception
{
    public static function atPath(string $path): self
    {
        return new self(\sprintf('The configuration file %s does not exist.', $path));
    }

    /**
     * @param list<string> $candidates
     */
    public static function inDirectory(Standard $standard, string $directory, array $candidates): self
    {
        return new self(\sprintf(
            'No %s configuration found in %s. Looked for %s.',
            $standard->label(),
            $directory,
            \implode(', ', $candidates),
        ));
    }
}
