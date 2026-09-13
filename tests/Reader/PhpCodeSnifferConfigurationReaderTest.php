<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Tests\Reader;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Stolt\CodingStandardMigrator\Exception\InvalidSourceConfiguration;
use Stolt\CodingStandardMigrator\Exception\SourceConfigurationNotFound;
use Stolt\CodingStandardMigrator\Reader\GuessesSourcePaths;
use Stolt\CodingStandardMigrator\Reader\PhpCodeSnifferConfigurationReader;
use Stolt\CodingStandardMigrator\Ruleset\Rule;
use Stolt\CodingStandardMigrator\Standard\Standard;
use Stolt\CodingStandardMigrator\Tests\TestCase;

#[CoversClass(PhpCodeSnifferConfigurationReader::class)]
#[CoversClass(GuessesSourcePaths::class)]
final class PhpCodeSnifferConfigurationReaderTest extends TestCase
{
    private PhpCodeSnifferConfigurationReader $reader;

    protected function setUp(): void
    {
        $this->reader = new PhpCodeSnifferConfigurationReader();
    }

    #[Test]
    public function readsARuleset(): void
    {
        $source = $this->reader->read($this->fixture('phpcs', 'phpcs.xml.dist'));

        self::assertSame(Standard::PhpCodeSniffer, $source->standard);
        self::assertSame(['src', 'tests'], $source->paths);
        self::assertSame('8.3', $source->phpVersion);
        self::assertSame('    ', $source->indent);
        self::assertSame(4, $source->indentWidth());
    }

    #[Test]
    public function normalizesReferencedStandardsIntoRuleSets(): void
    {
        $ruleset = $this->reader->read($this->fixture('phpcs', 'phpcs.xml.dist'))->ruleset;

        $standard = $ruleset->get('@PSR12');
        self::assertInstanceOf(Rule::class, $standard);
        self::assertTrue($standard->isRuleSet());

        $sniff = $ruleset->get('Squiz.Strings.DoubleQuoteUsage');
        self::assertInstanceOf(Rule::class, $sniff);
        self::assertFalse($sniff->isRuleSet());
    }

    #[Test]
    public function readsSniffPropertiesIncludingArrays(): void
    {
        $ruleset = $this->reader->read($this->fixture('phpcs', 'phpcs.xml.dist'))->ruleset;

        $lineLength = $ruleset->get('Generic.Files.LineLength');
        self::assertInstanceOf(Rule::class, $lineLength);
        self::assertSame(['lineLimit' => 120, 'absoluteLineLimit' => 0], $lineLength->options);
        self::assertSame(120, $lineLength->intOption('lineLimit'));

        $scopeIndent = $ruleset->get('Generic.WhiteSpace.ScopeIndent');
        self::assertInstanceOf(Rule::class, $scopeIndent);
        self::assertSame(
            [
                'indent' => 4,
                'tabIndent' => false,
                'ignoreIndentationTokens' => ['T_COMMENT'],
            ],
            $scopeIndent->options,
        );
        self::assertFalse($scopeIndent->boolOption('tabIndent'));
    }

    #[Test]
    public function treatsSilencedAndExcludedSniffsAsDisabled(): void
    {
        $ruleset = $this->reader->read($this->fixture('phpcs', 'phpcs.xml.dist'))->ruleset;

        $silenced = $ruleset->get('Generic.Commenting.Todo');
        self::assertInstanceOf(Rule::class, $silenced);
        self::assertFalse($silenced->enabled);

        $excluded = $ruleset->get('Generic.PHP.LowerCaseKeyword');
        self::assertInstanceOf(Rule::class, $excluded);
        self::assertFalse($excluded->enabled);

        $kept = $ruleset->get('Squiz.Commenting.FunctionComment.MissingParamComment');
        self::assertInstanceOf(Rule::class, $kept);
        self::assertTrue($kept->enabled);
    }

    #[Test]
    public function looksForTheKnownRulesetFileNames(): void
    {
        self::assertSame(
            ['phpcs.xml', 'phpcs.xml.dist', '.phpcs.xml', '.phpcs.xml.dist', 'ruleset.xml'],
            $this->reader->configurationFileNames(),
        );
    }

    #[Test]
    public function failsOnAMissingRuleset(): void
    {
        $this->expectException(SourceConfigurationNotFound::class);

        $this->reader->read($this->fixture('phpcs', 'does-not-exist.xml'));
    }

    #[Test]
    public function failsOnAnUnparsableRuleset(): void
    {
        $this->expectException(InvalidSourceConfiguration::class);
        $this->expectExceptionMessage('Failed to parse');

        $this->reader->read($this->fixture('phpcs-invalid', 'phpcs.xml'));
    }
}
