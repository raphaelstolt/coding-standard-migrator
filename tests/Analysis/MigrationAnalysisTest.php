<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Tests\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Stolt\CodingStandardMigrator\Analysis\MigrationAnalysis;
use Stolt\CodingStandardMigrator\Mapping\Mapping;
use Stolt\CodingStandardMigrator\Mapping\MappingFidelity;
use Stolt\CodingStandardMigrator\Mapping\MappingReport;
use Stolt\CodingStandardMigrator\Migration\MigrationEngine;
use Stolt\CodingStandardMigrator\Migration\MigrationRequest;
use Stolt\CodingStandardMigrator\Ruleset\Ruleset;
use Stolt\CodingStandardMigrator\Ruleset\SourceConfiguration;
use Stolt\CodingStandardMigrator\Standard\Standard;
use Stolt\CodingStandardMigrator\Tests\TestCase;

#[CoversClass(MigrationAnalysis::class)]
#[CoversClass(MappingFidelity::class)]
final class MigrationAnalysisTest extends TestCase
{
    #[Test]
    public function countsTheOutcomesOfTheMappedRules(): void
    {
        $analysis = MigrationAnalysis::of(
            Standard::Mago,
            $this->sourceConfiguration([
                'single_quote' => true,
                'yoda_style' => true,
                'array_syntax' => true,
                'no_trailing_whitespace' => true,
                'header_comment' => true,
                'native_function_invocation' => true,
            ]),
            (new MappingReport())
                ->with(Mapping::formatterOptions('Config::setIndent()', ['use-tabs' => false]))
                ->with(Mapping::formatterOptions('single_quote', ['single-quote' => true]))
                ->with(Mapping::linterRules('yoda_style', ['yoda-conditions' => ['enabled' => true]]))
                ->with(Mapping::linterRules(
                    'array_syntax',
                    ['array-style' => ['enabled' => true]],
                    'Verify the style.',
                ))
                ->with(Mapping::coveredByFormatter('no_trailing_whitespace'))
                ->with(Mapping::unsupported('header_comment', 'No counterpart.'))
                ->with(Mapping::unknown('native_function_invocation', 'Not mapped yet.')),
        );

        self::assertSame(Standard::PhpCsFixer, $analysis->from);
        self::assertSame(Standard::Mago, $analysis->to);

        // The derived indent mapping is not a rule of the source configuration.
        self::assertSame(6, $analysis->rules);
        self::assertSame(3, $analysis->mappable());
        self::assertSame(2, $analysis->equivalent);
        self::assertSame(1, $analysis->partial);
        self::assertSame(2, $analysis->linterCandidates);
        self::assertSame(1, $analysis->redundant);
        self::assertSame(0, $analysis->disabled);
        self::assertSame(1, $analysis->unsupported);
        self::assertSame(1, $analysis->unknown);
        self::assertSame(['header_comment', 'native_function_invocation'], $analysis->needsAttention);
        self::assertFalse($analysis->isComplete());
    }

    #[Test]
    public function weighsPartialMappingsHalf(): void
    {
        // 2 equivalent + 1 redundant + 1 disabled + 0.5 partial of 6 rules.
        $analysis = MigrationAnalysis::of(
            Standard::Mago,
            $this->sourceConfiguration([
                'single_quote' => true,
                'yoda_style' => true,
                'array_syntax' => true,
                'no_trailing_whitespace' => true,
                'php_unit_strict' => false,
                'header_comment' => true,
            ]),
            (new MappingReport())
                ->with(Mapping::formatterOptions('single_quote', ['single-quote' => true]))
                ->with(Mapping::linterRules('yoda_style', ['yoda-conditions' => ['enabled' => true]]))
                ->with(Mapping::linterRules('array_syntax', ['array-style' => ['enabled' => true]], 'Verify.'))
                ->with(Mapping::coveredByFormatter('no_trailing_whitespace'))
                ->with(Mapping::skipped('php_unit_strict', 'Disabled in the source configuration.'))
                ->with(Mapping::unsupported('header_comment', 'No counterpart.')),
        );

        self::assertSame(75, $analysis->confidence());
    }

    #[Test]
    public function hasNoConfidenceWithoutRules(): void
    {
        $analysis = MigrationAnalysis::of(Standard::Mago, $this->sourceConfiguration([]), new MappingReport());

        self::assertSame(0, $analysis->rules);
        self::assertNull($analysis->confidence());
        self::assertTrue($analysis->isComplete());
    }

    #[Test]
    public function labelsTheCountsForReporting(): void
    {
        $analysis = MigrationAnalysis::of(
            Standard::Mago,
            $this->sourceConfiguration(['single_quote' => true]),
            (new MappingReport())->with(Mapping::formatterOptions('single_quote', ['single-quote' => true])),
        );

        self::assertSame(
            [
                'Rules' => 1,
                'Mappable' => 1,
                'Equivalent' => 1,
                'Partial' => 0,
                'Unsupported' => 0,
                'No mapping known' => 0,
                'Disabled in source' => 0,
                'Linter candidates' => 0,
                'Redundant with Mago' => 0,
            ],
            $analysis->counts(),
        );
    }

    #[Test]
    public function analyzesAConfigurationThroughTheEngine(): void
    {
        $engine = new MigrationEngine();

        $analysis = $engine->analyze(new MigrationRequest(
            from: Standard::PhpCsFixer,
            to: Standard::Mago,
            sourceFile: $this->fixture('php-cs-fixer', '.php-cs-fixer.dist.php'),
        ));

        self::assertSame(10, $analysis->rules);
        self::assertSame($this->fixture('php-cs-fixer', '.php-cs-fixer.dist.php'), $analysis->file);
        self::assertSame(65, $analysis->confidence());
        self::assertSame(['header_comment', 'phpdoc_align', 'native_function_invocation'], $analysis->needsAttention);
    }

    #[Test]
    public function isAlsoAvailableOnAMigrationResult(): void
    {
        $engine = new MigrationEngine();
        $request = new MigrationRequest(
            from: Standard::Pint,
            to: Standard::Mago,
            sourceFile: $this->fixture('pint', 'pint.json'),
        );

        self::assertSame(
            $engine->analyze($request)->confidence(),
            $engine->migrate($request)->analysis()->confidence(),
        );
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
