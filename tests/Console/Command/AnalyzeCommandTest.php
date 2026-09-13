<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Tests\Console\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Stolt\CodingStandardMigrator\Console\Application;
use Stolt\CodingStandardMigrator\Console\Command\AnalyzeCommand;
use Stolt\CodingStandardMigrator\Console\Command\LocatesSourceConfiguration;
use Stolt\CodingStandardMigrator\Migration\MigrationEngine;
use Stolt\CodingStandardMigrator\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(AnalyzeCommand::class)]
#[CoversClass(LocatesSourceConfiguration::class)]
final class AnalyzeCommandTest extends TestCase
{
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->tester = new CommandTester(new AnalyzeCommand(new MigrationEngine()));
    }

    #[Test]
    public function analyzesThePhpCsFixerConfiguration(): void
    {
        $exitCode = $this->tester->execute([
            '--working-dir' => $this->fixtureDirectory('php-cs-fixer'),
        ]);

        $display = $this->tester->getDisplay();

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('PHP-CS-Fixer configuration', $display);
        self::assertStringContainsString('Rules:                  10', $display);
        self::assertStringContainsString('Mappable:                6', $display);
        self::assertStringContainsString('Equivalent:              5', $display);
        self::assertStringContainsString('Partial:                 1', $display);
        self::assertStringContainsString('Unsupported:             2', $display);
        self::assertStringContainsString('Linter candidates:       3', $display);
        self::assertStringContainsString('Redundant with Mago:     1', $display);
        self::assertStringContainsString('Migration confidence: 65%', $display);
    }

    #[Test]
    public function analyzesAPhpCodeSnifferRuleset(): void
    {
        $exitCode = $this->tester->execute([
            '--from' => 'phpcs',
            '--working-dir' => $this->fixtureDirectory('phpcs'),
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('PHP_CodeSniffer configuration', $this->tester->getDisplay());
    }

    #[Test]
    public function analyzesAPintConfiguration(): void
    {
        $exitCode = $this->tester->execute([
            '--from' => 'pint',
            '--working-dir' => $this->fixtureDirectory('pint'),
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Laravel Pint configuration', $this->tester->getDisplay());
    }

    #[Test]
    public function listsTheRulesNeedingAttentionWhenVerbose(): void
    {
        $this->tester->execute(['--working-dir' => $this->fixtureDirectory('php-cs-fixer')], [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
        ]);

        $display = $this->tester->getDisplay();

        self::assertStringContainsString('Rules needing a manual decision', $display);
        self::assertStringContainsString('header_comment', $display);
    }

    #[Test]
    public function keepsQuietAboutTheRulesNeedingAttentionByDefault(): void
    {
        $this->tester->execute(['--working-dir' => $this->fixtureDirectory('php-cs-fixer')]);

        self::assertStringNotContainsString('Rules needing a manual decision', $this->tester->getDisplay());
    }

    #[Test]
    public function failsBelowTheRequiredConfidence(): void
    {
        $exitCode = $this->tester->execute([
            '--working-dir' => $this->fixtureDirectory('php-cs-fixer'),
            '--fail-under' => '80',
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString(
            'The migration confidence of 65% is below the required 80%',
            $this->tester->getDisplay(),
        );
    }

    #[Test]
    public function passesTheRequiredConfidence(): void
    {
        $exitCode = $this->tester->execute([
            '--working-dir' => $this->fixtureDirectory('php-cs-fixer'),
            '--fail-under' => '60',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    #[Test]
    public function reportsAMissingConfiguration(): void
    {
        $exitCode = $this->tester->execute([
            '--working-dir' => $this->fixtureDirectory('.'),
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('No PHP-CS-Fixer configuration found', $this->tester->getDisplay());
    }

    #[Test]
    public function reportsAnUnknownStandard(): void
    {
        $exitCode = $this->tester->execute([
            '--working-dir' => $this->fixtureDirectory('php-cs-fixer'),
            '--from' => 'phpstan',
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Unknown coding standard "phpstan"', $this->tester->getDisplay());
    }

    #[Test]
    public function isRegisteredUnderBothSpellings(): void
    {
        $application = new Application();

        self::assertSame('analyze', $application->find('analyze')->getName());
        self::assertSame('analyze', $application->find('analyse')->getName());
    }
}
