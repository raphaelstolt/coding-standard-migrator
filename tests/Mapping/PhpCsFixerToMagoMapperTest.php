<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Tests\Mapping;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Stolt\CodingStandardMigrator\Mapping\Mapping;
use Stolt\CodingStandardMigrator\Mapping\MappingFidelity;
use Stolt\CodingStandardMigrator\Mapping\MappingOutcome;
use Stolt\CodingStandardMigrator\Mapping\MappingReport;
use Stolt\CodingStandardMigrator\Mapping\PhpCsFixerToMago\MappingTable;
use Stolt\CodingStandardMigrator\Mapping\PhpCsFixerToMago\PhpCsFixerToMagoMapper;
use Stolt\CodingStandardMigrator\Ruleset\Ruleset;
use Stolt\CodingStandardMigrator\Ruleset\SourceConfiguration;
use Stolt\CodingStandardMigrator\Standard\Standard;
use Stolt\CodingStandardMigrator\Tests\TestCase;

#[CoversClass(PhpCsFixerToMagoMapper::class)]
#[CoversClass(MappingTable::class)]
#[CoversClass(Mapping::class)]
#[CoversClass(MappingReport::class)]
#[CoversClass(MappingOutcome::class)]
final class PhpCsFixerToMagoMapperTest extends TestCase
{
    private PhpCsFixerToMagoMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new PhpCsFixerToMagoMapper();
    }

    #[Test]
    public function migratesBetweenPhpCsFixerAndMago(): void
    {
        self::assertSame(Standard::PhpCsFixer, $this->mapper->from());
        self::assertSame(Standard::Mago, $this->mapper->to());
    }

    /**
     * @param array<string, bool|array<string, mixed>> $rules
     * @param array<string, mixed>                     $expectedSettings
     */
    #[Test]
    #[DataProvider('ruleProvider')]
    public function mapsARule(array $rules, MappingOutcome $expectedOutcome, array $expectedSettings): void
    {
        $mapping = $this->mapper->map($this->sourceConfiguration($rules))->all()[0];

        self::assertSame($expectedOutcome, $mapping->outcome);
        self::assertSame($expectedSettings, $mapping->settings);
    }

    /**
     * @return iterable<string, array{0: array<string, bool|array<string, mixed>>, 1: MappingOutcome, 2: array<string, mixed>}>
     */
    public static function ruleProvider(): iterable
    {
        yield 'a rule set becomes a formatter preset' => [
            ['@PSR12' => true],
            MappingOutcome::FormatterOption,
            ['preset' => 'psr-12'],
        ];

        yield 'a formatter rule becomes a formatter option' => [
            ['single_quote' => true],
            MappingOutcome::FormatterOption,
            ['single-quote' => true],
        ];

        yield 'an option dependent rule honours its options' => [
            ['concat_space' => ['spacing' => 'one']],
            MappingOutcome::FormatterOption,
            ['space-around-concatenation-binary-operator' => true],
        ];

        yield 'a linter rule becomes a linter rule' => [
            ['yoda_style' => true],
            MappingOutcome::LinterRule,
            ['yoda-conditions' => ['enabled' => true]],
        ];

        yield 'a rule can become several linter rules' => [
            ['modernize_strpos' => true],
            MappingOutcome::LinterRule,
            ['str-contains' => ['enabled' => true], 'str-starts-with' => ['enabled' => true]],
        ];

        yield 'a rule the formatter always applies needs no setting' => [
            ['no_trailing_whitespace' => true],
            MappingOutcome::CoveredByFormatter,
            [],
        ];

        yield 'a rule without a counterpart is unsupported' => [
            ['header_comment' => ['header' => 'Anything']],
            MappingOutcome::Unsupported,
            [],
        ];

        yield 'an unmapped rule stays unknown' => [
            ['native_function_invocation' => true],
            MappingOutcome::Unknown,
            [],
        ];

        yield 'a PHP version rule set points at the php-version key' => [
            ['@PHP83Migration' => true],
            MappingOutcome::Unsupported,
            [],
        ];
    }

    #[Test]
    public function keepsDisabledLinterRulesDisabled(): void
    {
        $mapping = $this->mapper->map($this->sourceConfiguration(['yoda_style' => false]))->all()[0];

        self::assertSame(MappingOutcome::LinterRule, $mapping->outcome);
        self::assertSame(['yoda-conditions' => ['enabled' => false]], $mapping->settings);
        self::assertTrue(
            $mapping->hasFidelity(MappingFidelity::Equivalent),
            'Carrying an opt out over is a faithful translation, despite its note.',
        );
    }

    #[Test]
    public function marksTranslationsWithACaveatAsPartial(): void
    {
        $report = $this->mapper->map($this->sourceConfiguration([
            'single_quote' => true,
            'array_syntax' => ['syntax' => 'short'],
            'no_trailing_whitespace' => true,
        ]));

        [$faithful, $partial, $covered] = $report->all();

        self::assertTrue($faithful->hasFidelity(MappingFidelity::Equivalent));
        self::assertTrue($partial->hasFidelity(MappingFidelity::Partial));
        self::assertNull($covered->fidelity, 'Untranslated outcomes have no fidelity.');
    }

    #[Test]
    public function skipsDisabledRulesWithoutALinterCounterpart(): void
    {
        $mapping = $this->mapper->map($this->sourceConfiguration(['single_quote' => false]))->all()[0];

        self::assertSame(MappingOutcome::Skipped, $mapping->outcome);
        self::assertSame([], $mapping->settings);
    }

    #[Test]
    public function mapsIndentAndLineEndingOfTheConfigurationObject(): void
    {
        $report = $this->mapper->map(new SourceConfiguration(
            standard: Standard::PhpCsFixer,
            file: '.php-cs-fixer.php',
            ruleset: Ruleset::fromArray([]),
            indent: "\t",
            lineEnding: "\r\n",
        ));

        self::assertSame(['end-of-line' => 'crlf', 'use-tabs' => true], $report->formatterOptions());
    }

    #[Test]
    public function summarizesTheOutcomes(): void
    {
        $report = $this->mapper->map($this->sourceConfiguration([
            'single_quote' => true,
            'yoda_style' => true,
            'native_function_invocation' => true,
        ]));

        self::assertSame(['formatter option' => 1, 'linter rule' => 1, 'unknown' => 1], $report->summary());
        self::assertCount(1, $report->needingAttention());
        self::assertSame(['single-quote' => true], $report->formatterOptions());
        self::assertSame(['yoda-conditions' => ['enabled' => true]], $report->linterRules());
    }

    /**
     * @param array<string, bool|array<string, mixed>> $rules
     */
    private function sourceConfiguration(array $rules): SourceConfiguration
    {
        return new SourceConfiguration(
            standard: Standard::PhpCsFixer,
            file: '.php-cs-fixer.php',
            ruleset: Ruleset::fromArray($rules),
        );
    }
}
