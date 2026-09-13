<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Tests\Writer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Stolt\CodingStandardMigrator\Mapping\Mapping;
use Stolt\CodingStandardMigrator\Mapping\MappingReport;
use Stolt\CodingStandardMigrator\Migration\MigrationRequest;
use Stolt\CodingStandardMigrator\Ruleset\Ruleset;
use Stolt\CodingStandardMigrator\Ruleset\SourceConfiguration;
use Stolt\CodingStandardMigrator\Standard\Standard;
use Stolt\CodingStandardMigrator\Tests\TestCase;
use Stolt\CodingStandardMigrator\Writer\MagoConfigurationWriter;

#[CoversClass(MagoConfigurationWriter::class)]
final class MagoConfigurationWriterTest extends TestCase
{
    private MagoConfigurationWriter $writer;

    protected function setUp(): void
    {
        $this->writer = new MagoConfigurationWriter();
    }

    #[Test]
    public function writesMagoToml(): void
    {
        self::assertSame(Standard::Mago, $this->writer->standard());
        self::assertSame('mago.toml', $this->writer->configurationFileName());
    }

    #[Test]
    public function rendersAllSections(): void
    {
        $report = (new MappingReport())
            ->with(Mapping::formatterOptions('@PSR12', ['preset' => 'psr-12']))
            ->with(Mapping::formatterOptions('single_quote', ['single-quote' => true]))
            ->with(Mapping::linterRules('yoda_style', ['yoda-conditions' => ['enabled' => true]]))
            ->with(Mapping::coveredByFormatter('no_trailing_whitespace'))
            ->with(Mapping::unsupported('header_comment', 'Mago has no header comments.'));

        $configuration = $this->writer->render(
            new MigrationRequest(
                from: Standard::PhpCsFixer,
                to: Standard::Mago,
                sourceFile: '/project/.php-cs-fixer.dist.php',
                phpVersion: '8.3',
            ),
            new SourceConfiguration(
                standard: Standard::PhpCsFixer,
                file: '/project/.php-cs-fixer.dist.php',
                ruleset: Ruleset::fromArray([]),
                riskyAllowed: true,
                paths: ['src', 'tests'],
            ),
            $report,
        );

        $expected = <<<'TOML'
            # Migrated from the PHP-CS-Fixer configuration .php-cs-fixer.dist.php by stolt/coding-standard-migrator.
            # Review the settings below before committing them.
            # The source configuration allowed risky rules, verify the Mago counterparts.

            php-version = "8.3"

            [source]
            paths = ["src", "tests"]

            [formatter]
            preset = "psr-12"
            single-quote = true

            [linter.rules]
            yoda-conditions = { enabled = true }

            # Rules without a Mago counterpart in this migration:
            # - header_comment: Mago has no header comments.

            TOML;

        self::assertSame($expected, $configuration);
    }

    #[Test]
    public function omitsEmptySections(): void
    {
        $configuration = $this->writer->render(
            new MigrationRequest(from: Standard::PhpCsFixer, to: Standard::Mago, sourceFile: '.php-cs-fixer.php'),
            new SourceConfiguration(
                standard: Standard::PhpCsFixer,
                file: '.php-cs-fixer.php',
                ruleset: Ruleset::fromArray([]),
            ),
            new MappingReport(),
        );

        self::assertStringNotContainsString('[source]', $configuration);
        self::assertStringNotContainsString('[formatter]', $configuration);
        self::assertStringNotContainsString('[linter.rules]', $configuration);
        self::assertStringNotContainsString('php-version', $configuration);
        self::assertStringEndsWith("\n", $configuration);
    }
}
