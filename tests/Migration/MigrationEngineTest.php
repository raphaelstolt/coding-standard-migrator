<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Tests\Migration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Stolt\CodingStandardMigrator\Exception\SourceConfigurationNotFound;
use Stolt\CodingStandardMigrator\Exception\UnsupportedMigrationPath;
use Stolt\CodingStandardMigrator\Migration\MigrationEngine;
use Stolt\CodingStandardMigrator\Migration\MigrationRequest;
use Stolt\CodingStandardMigrator\Migration\MigrationResult;
use Stolt\CodingStandardMigrator\Standard\Standard;
use Stolt\CodingStandardMigrator\Standard\StandardRegistry;
use Stolt\CodingStandardMigrator\Tests\TestCase;

#[CoversClass(MigrationEngine::class)]
#[CoversClass(MigrationRequest::class)]
#[CoversClass(MigrationResult::class)]
#[CoversClass(StandardRegistry::class)]
final class MigrationEngineTest extends TestCase
{
    private MigrationEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new MigrationEngine();
    }

    #[Test]
    public function locatesTheSourceConfiguration(): void
    {
        self::assertSame(
            $this->fixture('php-cs-fixer', '.php-cs-fixer.dist.php'),
            $this->engine->locateSourceConfiguration(Standard::PhpCsFixer, $this->fixtureDirectory('php-cs-fixer')),
        );
    }

    #[Test]
    public function failsWhenNoSourceConfigurationIsFound(): void
    {
        $this->expectException(SourceConfigurationNotFound::class);
        $this->expectExceptionMessage('No PHP-CS-Fixer configuration found');

        $this->engine->locateSourceConfiguration(Standard::PhpCsFixer, $this->fixtureDirectory('.'));
    }

    #[Test]
    public function migratesAPhpCsFixerConfigurationToMago(): void
    {
        $result = $this->engine->migrate(new MigrationRequest(
            from: Standard::PhpCsFixer,
            to: Standard::Mago,
            sourceFile: $this->fixture('php-cs-fixer', '.php-cs-fixer.dist.php'),
            phpVersion: '8.3',
            paths: ['src'],
        ));

        self::assertSame('mago.toml', $result->targetFileName);
        self::assertSame(['src'], $result->source->paths);
        self::assertFalse($result->isComplete());
        self::assertStringContainsString('php-version = "8.3"', $result->configuration);
        self::assertStringContainsString('paths = ["src"]', $result->configuration);
        self::assertStringContainsString('preset = "psr-12"', $result->configuration);
        self::assertStringContainsString('yoda-conditions = { enabled = true }', $result->configuration);
        self::assertStringContainsString('# - native_function_invocation', $result->configuration);
    }

    #[Test]
    public function migratesAPhpCodeSnifferRulesetToMago(): void
    {
        $result = $this->engine->migrate(new MigrationRequest(
            from: Standard::PhpCodeSniffer,
            to: Standard::Mago,
            sourceFile: $this->engine->locateSourceConfiguration(
                Standard::PhpCodeSniffer,
                $this->fixtureDirectory('phpcs'),
            ),
        ));

        self::assertSame(['src', 'tests'], $result->source->paths);
        self::assertStringContainsString('php-version = "8.3"', $result->configuration);
        self::assertStringContainsString('preset = "psr-12"', $result->configuration);
        self::assertStringContainsString('print-width = 120', $result->configuration);
        self::assertStringContainsString('single-quote = true', $result->configuration);
        self::assertStringContainsString('lowercase-keyword = { enabled = false }', $result->configuration);
        self::assertStringContainsString('# - Generic.CodeAnalysis.UnusedFunctionParameter', $result->configuration);
    }

    #[Test]
    public function migratesAPintConfigurationToMago(): void
    {
        $result = $this->engine->migrate(new MigrationRequest(
            from: Standard::Pint,
            to: Standard::Mago,
            sourceFile: $this->engine->locateSourceConfiguration(Standard::Pint, $this->fixtureDirectory('pint')),
        ));

        self::assertStringContainsString('preset = "psr-12"', $result->configuration);
        self::assertStringContainsString('space-around-concatenation-binary-operator = true', $result->configuration);
        self::assertStringContainsString('yoda-conditions = { enabled = true }', $result->configuration);
        self::assertStringNotContainsString('php-version', $result->configuration);
    }

    #[Test]
    public function failsOnAnUnsupportedMigrationPath(): void
    {
        $this->expectException(UnsupportedMigrationPath::class);
        $this->expectExceptionMessage('php-cs-fixer -> mago');

        $this->engine->migrate(new MigrationRequest(
            from: Standard::Mago,
            to: Standard::PhpCsFixer,
            sourceFile: $this->fixture('php-cs-fixer', '.php-cs-fixer.dist.php'),
        ));
    }

    #[Test]
    public function exposesTheSupportedStandards(): void
    {
        $registry = $this->engine->registry();

        self::assertSame(
            [Standard::PhpCsFixer, Standard::PhpCodeSniffer, Standard::Pint],
            $registry->sourceStandards(),
        );
        self::assertSame([Standard::Mago], $registry->targetStandards());
        self::assertSame(['php-cs-fixer -> mago', 'phpcs -> mago', 'pint -> mago'], $registry->migrationPaths());
        self::assertTrue($registry->supports(Standard::PhpCodeSniffer, Standard::Mago));
        self::assertTrue($registry->supports(Standard::Pint, Standard::Mago));
        self::assertFalse($registry->supports(Standard::Mago, Standard::PhpCsFixer));
    }

    #[Test]
    public function reportsMissingReadersAndWriters(): void
    {
        $registry = StandardRegistry::empty();

        self::assertSame([], $registry->migrationPaths());

        $this->expectException(UnsupportedMigrationPath::class);
        $this->expectExceptionMessage('PHP_CodeSniffer is not supported as a migration source yet.');

        $registry->readerFor(Standard::PhpCodeSniffer);
    }
}
