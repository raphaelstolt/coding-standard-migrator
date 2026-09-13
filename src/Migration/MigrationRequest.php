<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Migration;

use Stolt\CodingStandardMigrator\Standard\Standard;

final readonly class MigrationRequest
{
    /**
     * @param string       $sourceFile the configuration file to migrate
     * @param string|null  $phpVersion the PHP version to pin in the target configuration
     * @param list<string> $paths      the paths to apply the target standard to, overriding
     *                                 whatever the reader was able to determine
     */
    public function __construct(
        public Standard $from,
        public Standard $to,
        public string $sourceFile,
        public ?string $phpVersion = null,
        public array $paths = [],
    ) {}
}
