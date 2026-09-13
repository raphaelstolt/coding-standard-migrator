<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Tests\Reader;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Stolt\CodingStandardMigrator\Exception\InvalidSourceConfiguration;
use Stolt\CodingStandardMigrator\Exception\SourceConfigurationNotFound;
use Stolt\CodingStandardMigrator\Reader\PintConfigurationReader;
use Stolt\CodingStandardMigrator\Ruleset\Rule;
use Stolt\CodingStandardMigrator\Standard\Standard;
use Stolt\CodingStandardMigrator\Tests\TestCase;

#[CoversClass(PintConfigurationReader::class)]
final class PintConfigurationReaderTest extends TestCase
{
    private PintConfigurationReader $reader;

    protected function setUp(): void
    {
        $this->reader = new PintConfigurationReader();
    }

    #[Test]
    public function readsAPintConfiguration(): void
    {
        $source = $this->reader->read($this->fixture('pint', 'pint.json'));

        self::assertSame(Standard::Pint, $source->standard);
        self::assertCount(8, $source->ruleset);
    }

    #[Test]
    public function normalizesThePresetIntoAPhpCsFixerRuleSet(): void
    {
        $ruleset = $this->reader->read($this->fixture('pint', 'pint.json'))->ruleset;

        $preset = $ruleset->get('@PSR12');
        self::assertInstanceOf(Rule::class, $preset);
        self::assertTrue($preset->isRuleSet());
        self::assertSame('@PSR12', $ruleset->names()[0], 'The preset has to come before the rules overriding it.');
    }

    #[Test]
    public function readsTheRulesAsPhpCsFixerRules(): void
    {
        $ruleset = $this->reader->read($this->fixture('pint', 'pint.json'))->ruleset;

        $configured = $ruleset->get('concat_space');
        self::assertInstanceOf(Rule::class, $configured);
        self::assertSame(['spacing' => 'one'], $configured->options);

        $disabled = $ruleset->get('php_unit_strict');
        self::assertInstanceOf(Rule::class, $disabled);
        self::assertFalse($disabled->enabled);
    }

    #[Test]
    public function looksForTheKnownConfigurationFileNames(): void
    {
        self::assertSame(['pint.json', '.pint.json'], $this->reader->configurationFileNames());
    }

    #[Test]
    public function failsOnAMissingConfiguration(): void
    {
        $this->expectException(SourceConfigurationNotFound::class);

        $this->reader->read($this->fixture('pint', 'does-not-exist.json'));
    }

    #[Test]
    public function failsOnInvalidJson(): void
    {
        $this->expectException(InvalidSourceConfiguration::class);
        $this->expectExceptionMessage('Failed to parse');

        $this->reader->read($this->fixture('pint-invalid', 'pint.json'));
    }
}
