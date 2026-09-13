<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Tests\Console\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Stolt\CodingStandardMigrator\Console\Command\MigrateCommand;
use Stolt\CodingStandardMigrator\Migration\MigrationEngine;
use Stolt\CodingStandardMigrator\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(MigrateCommand::class)]
final class MigrateCommandTest extends TestCase
{
    private string $workingDirectory;

    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->workingDirectory = $this->createTemporaryDirectory();
        \copy(
            $this->fixture('php-cs-fixer', '.php-cs-fixer.dist.php'),
            $this->workingDirectory . '/.php-cs-fixer.dist.php',
        );

        $this->tester = new CommandTester(new MigrateCommand(new MigrationEngine()));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->workingDirectory);
    }

    #[Test]
    public function showsTheMigratedConfigurationOnADryRun(): void
    {
        $exitCode = $this->tester->execute([
            '--working-dir' => $this->workingDirectory,
            '--dry-run' => true,
        ]);

        $display = $this->tester->getDisplay();

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Migrating PHP-CS-Fixer to Mago', $display);
        self::assertStringContainsString('[formatter]', $display);
        self::assertStringContainsString('Nothing was written', $display);
        self::assertFileDoesNotExist($this->workingDirectory . '/mago.toml');
    }

    #[Test]
    public function writesTheMigratedConfiguration(): void
    {
        $exitCode = $this->tester->execute(['--working-dir' => $this->workingDirectory]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertFileExists($this->workingDirectory . '/mago.toml');
        self::assertStringContainsString(
            'single-quote = true',
            (string) \file_get_contents($this->workingDirectory . '/mago.toml'),
        );
    }

    #[Test]
    public function refusesToOverwriteAnExistingConfiguration(): void
    {
        \file_put_contents($this->workingDirectory . '/mago.toml', '# keep me');

        $exitCode = $this->tester->execute(['--working-dir' => $this->workingDirectory]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('pass --force', $this->tester->getDisplay());
        self::assertSame('# keep me', \file_get_contents($this->workingDirectory . '/mago.toml'));
    }

    #[Test]
    public function overwritesAnExistingConfigurationWhenForced(): void
    {
        \file_put_contents($this->workingDirectory . '/mago.toml', '# overwrite me');

        $exitCode = $this->tester->execute([
            '--working-dir' => $this->workingDirectory,
            '--force' => true,
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringNotContainsString(
            '# overwrite me',
            (string) \file_get_contents($this->workingDirectory . '/mago.toml'),
        );
    }

    #[Test]
    public function failsOnUnmappedRulesWhenAsked(): void
    {
        $exitCode = $this->tester->execute([
            '--working-dir' => $this->workingDirectory,
            '--dry-run' => true,
            '--fail-on-unmapped' => true,
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('could not be migrated', $this->tester->getDisplay());
    }

    #[Test]
    public function reportsAnUnknownStandard(): void
    {
        $exitCode = $this->tester->execute([
            '--working-dir' => $this->workingDirectory,
            '--to' => 'phpstan',
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Unknown coding standard "phpstan"', $this->tester->getDisplay());
    }

    #[Test]
    public function reportsAMissingSourceConfiguration(): void
    {
        $exitCode = $this->tester->execute([
            '--working-dir' => $this->workingDirectory,
            '--config' => 'nope.php',
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('does not exist', $this->tester->getDisplay());
    }

    #[Test]
    public function migratesToAnExplicitTargetFileWithExplicitPaths(): void
    {
        \mkdir($this->workingDirectory . '/config');

        $exitCode = $this->tester->execute([
            '--working-dir' => $this->workingDirectory,
            '--target' => $this->workingDirectory . '/config/mago.toml',
            '--path' => ['src', 'tests'],
            '--php-version' => '8.4',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertFileDoesNotExist($this->workingDirectory . '/mago.toml');

        $configuration = (string) \file_get_contents($this->workingDirectory . '/config/mago.toml');

        self::assertStringContainsString('php-version = "8.4"', $configuration);
        self::assertStringContainsString('paths = ["src", "tests"]', $configuration);
    }
}
