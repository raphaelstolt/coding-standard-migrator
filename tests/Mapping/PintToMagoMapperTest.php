<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Tests\Mapping;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Stolt\CodingStandardMigrator\Mapping\MappingOutcome;
use Stolt\CodingStandardMigrator\Mapping\PintToMago\PintToMagoMapper;
use Stolt\CodingStandardMigrator\Ruleset\Ruleset;
use Stolt\CodingStandardMigrator\Ruleset\SourceConfiguration;
use Stolt\CodingStandardMigrator\Standard\Standard;
use Stolt\CodingStandardMigrator\Tests\TestCase;

#[CoversClass(PintToMagoMapper::class)]
final class PintToMagoMapperTest extends TestCase
{
    private PintToMagoMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new PintToMagoMapper();
    }

    #[Test]
    public function migratesBetweenPintAndMago(): void
    {
        self::assertSame(Standard::Pint, $this->mapper->from());
        self::assertSame(Standard::Mago, $this->mapper->to());
    }

    #[Test]
    public function reusesThePhpCsFixerMappings(): void
    {
        $report = $this->mapper->map(new SourceConfiguration(
            standard: Standard::Pint,
            file: 'pint.json',
            ruleset: Ruleset::fromArray([
                '@Laravel' => true,
                'single_quote' => true,
                'yoda_style' => true,
                'native_function_invocation' => true,
            ]),
        ));

        self::assertSame(['preset' => 'laravel', 'single-quote' => true], $report->formatterOptions());
        self::assertSame(['yoda-conditions' => ['enabled' => true]], $report->linterRules());
        self::assertCount(1, $report->needingAttention());
    }

    #[Test]
    public function reportsAnUnknownPresetAsARuleSet(): void
    {
        $mapping = $this->mapper->map(new SourceConfiguration(
            standard: Standard::Pint,
            file: 'pint.json',
            ruleset: Ruleset::fromArray(['@Symfony' => true]),
        ))->all()[0];

        self::assertSame(MappingOutcome::Unknown, $mapping->outcome);
        self::assertStringContainsString('Rule set without a Mago preset', (string) $mapping->note);
    }
}
