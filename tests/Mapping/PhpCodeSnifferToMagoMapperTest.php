<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Tests\Mapping;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Stolt\CodingStandardMigrator\Mapping\Mago\AbstractRuleMapper;
use Stolt\CodingStandardMigrator\Mapping\MappingOutcome;
use Stolt\CodingStandardMigrator\Mapping\PhpCodeSnifferToMago\MappingTable;
use Stolt\CodingStandardMigrator\Mapping\PhpCodeSnifferToMago\PhpCodeSnifferToMagoMapper;
use Stolt\CodingStandardMigrator\Ruleset\Ruleset;
use Stolt\CodingStandardMigrator\Ruleset\SourceConfiguration;
use Stolt\CodingStandardMigrator\Standard\Standard;
use Stolt\CodingStandardMigrator\Tests\TestCase;

#[CoversClass(PhpCodeSnifferToMagoMapper::class)]
#[CoversClass(MappingTable::class)]
#[CoversClass(AbstractRuleMapper::class)]
final class PhpCodeSnifferToMagoMapperTest extends TestCase
{
    private PhpCodeSnifferToMagoMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new PhpCodeSnifferToMagoMapper();
    }

    #[Test]
    public function migratesBetweenPhpCodeSnifferAndMago(): void
    {
        self::assertSame(Standard::PhpCodeSniffer, $this->mapper->from());
        self::assertSame(Standard::Mago, $this->mapper->to());
    }

    /**
     * @param array<string, bool|array<string, mixed>> $rules
     * @param array<string, mixed>                     $expectedSettings
     */
    #[Test]
    #[DataProvider('sniffProvider')]
    public function mapsASniff(array $rules, MappingOutcome $expectedOutcome, array $expectedSettings): void
    {
        $mapping = $this->mapper->map($this->sourceConfiguration($rules))->all()[0];

        self::assertSame($expectedOutcome, $mapping->outcome);
        self::assertSame($expectedSettings, $mapping->settings);
    }

    /**
     * @return iterable<string, array{0: array<string, bool|array<string, mixed>>, 1: MappingOutcome, 2: array<string, mixed>}>
     */
    public static function sniffProvider(): iterable
    {
        yield 'a referenced standard becomes a formatter preset' => [
            ['@PSR12' => true],
            MappingOutcome::FormatterOption,
            ['preset' => 'psr-12'],
        ];

        yield 'the Drupal standard becomes the Drupal preset' => [
            ['@Drupal' => true],
            MappingOutcome::FormatterOption,
            ['preset' => 'drupal'],
        ];

        yield 'a standard without a preset is unsupported' => [
            ['@Squiz' => true],
            MappingOutcome::Unsupported,
            [],
        ];

        yield 'a third party standard stays unknown' => [
            ['@SlevomatCodingStandard' => true],
            MappingOutcome::Unknown,
            [],
        ];

        yield 'a formatting sniff becomes a formatter option' => [
            ['Squiz.Strings.DoubleQuoteUsage' => true],
            MappingOutcome::FormatterOption,
            ['single-quote' => true],
        ];

        yield 'a property dependent sniff honours its properties' => [
            ['Generic.Files.LineLength' => ['lineLimit' => 100]],
            MappingOutcome::FormatterOption,
            ['print-width' => 100],
        ];

        yield 'a sniff without properties falls back to the PHP_CodeSniffer default' => [
            ['Generic.Files.LineLength' => true],
            MappingOutcome::FormatterOption,
            ['print-width' => 120],
        ];

        yield 'the scope indent sniff drives the indent options' => [
            ['Generic.WhiteSpace.ScopeIndent' => ['indent' => 2, 'tabIndent' => true]],
            MappingOutcome::FormatterOption,
            ['use-tabs' => true, 'tab-width' => 2],
        ];

        yield 'a sniff can become several linter rules' => [
            ['Squiz.NamingConventions.ValidVariableName' => true],
            MappingOutcome::LinterRule,
            ['variable-name' => ['enabled' => true], 'property-name' => ['enabled' => true]],
        ];

        yield 'a sniff the formatter always applies needs no setting' => [
            ['Squiz.WhiteSpace.SuperfluousWhitespace' => true],
            MappingOutcome::CoveredByFormatter,
            [],
        ];

        yield 'a sniff without a counterpart is unsupported' => [
            ['Squiz.Commenting.FileComment' => true],
            MappingOutcome::Unsupported,
            [],
        ];

        yield 'an unmapped sniff stays unknown' => [
            ['Generic.CodeAnalysis.UnusedFunctionParameter' => true],
            MappingOutcome::Unknown,
            [],
        ];
    }

    #[Test]
    public function looksUpSniffsByIgnoringTheirErrorCode(): void
    {
        $mapping = $this->mapper->map($this->sourceConfiguration([
            'Generic.Files.LineLength.TooLong' => ['lineLimit' => 80],
        ]))->all()[0];

        self::assertSame(MappingOutcome::FormatterOption, $mapping->outcome);
        self::assertSame(['print-width' => 80], $mapping->settings);
        self::assertSame('Generic.Files.LineLength.TooLong', $mapping->sourceRule);
    }

    #[Test]
    public function keepsExcludedSniffsDisabled(): void
    {
        $mapping = $this->mapper->map($this->sourceConfiguration([
            'Generic.PHP.LowerCaseKeyword' => false,
        ]))->all()[0];

        self::assertSame(MappingOutcome::LinterRule, $mapping->outcome);
        self::assertSame(['lowercase-keyword' => ['enabled' => false]], $mapping->settings);
    }

    #[Test]
    public function letsSniffsOverrideTheIndentOfTheRuleset(): void
    {
        $report = $this->mapper->map(new SourceConfiguration(
            standard: Standard::PhpCodeSniffer,
            file: 'phpcs.xml',
            ruleset: Ruleset::fromArray(['Generic.WhiteSpace.DisallowSpaceIndent' => true]),
            indent: '    ',
        ));

        self::assertSame('<arg name="tab-width"/>', $report->all()[0]->sourceRule);
        self::assertSame(['tab-width' => 4, 'use-tabs' => true], $report->formatterOptions());
    }

    /**
     * @param array<string, bool|array<string, mixed>> $rules
     */
    private function sourceConfiguration(array $rules): SourceConfiguration
    {
        return new SourceConfiguration(
            standard: Standard::PhpCodeSniffer,
            file: 'phpcs.xml',
            ruleset: Ruleset::fromArray($rules),
        );
    }
}
