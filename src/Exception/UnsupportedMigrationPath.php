<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Exception;

use RuntimeException;
use Stolt\CodingStandardMigrator\Standard\Standard;

final class UnsupportedMigrationPath extends RuntimeException implements Exception
{
    /**
     * @param list<string> $supportedPaths
     */
    public static function between(Standard $from, Standard $to, array $supportedPaths): self
    {
        return new self(\sprintf(
            'Migrating from %s to %s is not supported yet. Supported migration paths are %s.',
            $from->label(),
            $to->label(),
            $supportedPaths === [] ? 'none' : \implode(', ', $supportedPaths),
        ));
    }

    public static function noReader(Standard $standard): self
    {
        return new self(\sprintf('%s is not supported as a migration source yet.', $standard->label()));
    }

    public static function noWriter(Standard $standard): self
    {
        return new self(\sprintf('%s is not supported as a migration target yet.', $standard->label()));
    }
}
