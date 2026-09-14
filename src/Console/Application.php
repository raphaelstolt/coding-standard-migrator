<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Console;

use Stolt\CodingStandardMigrator\Console\Command\AnalyzeCommand;
use Stolt\CodingStandardMigrator\Console\Command\MigrateCommand;
use Stolt\CodingStandardMigrator\Console\Command\StandardsCommand;
use Stolt\CodingStandardMigrator\Migration\MigrationEngine;
use Symfony\Component\Console\Application as BaseApplication;

final class Application extends BaseApplication
{
    public const string NAME = 'cs-migrator';

    public const string VERSION = '0.1.0';

    public function __construct(MigrationEngine $engine = new MigrationEngine())
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->addCommands([
            new AnalyzeCommand($engine),
            new MigrateCommand($engine),
            new StandardsCommand($engine->registry()),
        ]);
    }
}
