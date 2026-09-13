<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Tests\Console\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Stolt\CodingStandardMigrator\Console\Application;
use Stolt\CodingStandardMigrator\Console\Command\StandardsCommand;
use Stolt\CodingStandardMigrator\Standard\Standard;
use Stolt\CodingStandardMigrator\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(StandardsCommand::class)]
#[CoversClass(Application::class)]
#[CoversClass(Standard::class)]
final class StandardsCommandTest extends TestCase
{
    #[Test]
    public function listsTheSupportedStandardsAndMigrationPaths(): void
    {
        $tester = new CommandTester((new Application())->find('standards'));

        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('php-cs-fixer -> mago', $display);
        self::assertStringContainsString('PHP_CodeSniffer', $display);
        self::assertStringContainsString('not yet', $display);
    }

    #[Test]
    public function resolvesStandardsByName(): void
    {
        self::assertSame(Standard::Mago, Standard::fromName(' MAGO '));
        self::assertSame(['php-cs-fixer', 'mago', 'phpcs', 'pint'], Standard::names());
    }
}
