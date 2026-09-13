<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Tests\Ruleset;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Stolt\CodingStandardMigrator\Exception\InvalidSourceConfiguration;
use Stolt\CodingStandardMigrator\Ruleset\Rule;
use Stolt\CodingStandardMigrator\Ruleset\Ruleset;
use Stolt\CodingStandardMigrator\Tests\TestCase;

#[CoversClass(Ruleset::class)]
#[CoversClass(Rule::class)]
final class RulesetTest extends TestCase
{
    #[Test]
    public function normalizesRuleConfigurations(): void
    {
        $ruleset = Ruleset::fromArray([
            '@PSR12' => true,
            'array_syntax' => ['syntax' => 'short'],
            'php_unit_strict' => false,
        ]);

        self::assertCount(3, $ruleset);
        self::assertSame(['@PSR12', 'array_syntax', 'php_unit_strict'], $ruleset->names());

        $ruleSet = $ruleset->get('@PSR12');
        self::assertInstanceOf(Rule::class, $ruleSet);
        self::assertTrue($ruleSet->isRuleSet());
        self::assertTrue($ruleSet->enabled);
        self::assertFalse($ruleSet->hasOptions());

        $configured = $ruleset->get('array_syntax');
        self::assertInstanceOf(Rule::class, $configured);
        self::assertFalse($configured->isRuleSet());
        self::assertSame(['syntax' => 'short'], $configured->options);
        self::assertSame('short', $configured->optionAmong('syntax', ['short', 'long']));
        self::assertNull($configured->optionAmong('syntax', ['long']));

        $disabled = $ruleset->get('php_unit_strict');
        self::assertInstanceOf(Rule::class, $disabled);
        self::assertFalse($disabled->enabled);
    }

    #[Test]
    public function isEmptyWithoutRules(): void
    {
        self::assertTrue(Ruleset::fromArray([])->isEmpty());
        self::assertFalse(Ruleset::fromArray(['single_quote' => true])->isEmpty());
        self::assertFalse(Ruleset::fromArray(['single_quote' => true])->has('array_syntax'));
        self::assertNull(Ruleset::fromArray([])->get('single_quote'));
    }

    #[Test]
    public function rejectsUnexpectedRuleConfigurations(): void
    {
        $this->expectException(InvalidSourceConfiguration::class);
        $this->expectExceptionMessage('Expected the configuration of rule "array_syntax"');

        Ruleset::fromArray(['array_syntax' => 'short']);
    }

    #[Test]
    public function rejectsNonStringRuleNames(): void
    {
        $this->expectException(InvalidSourceConfiguration::class);

        Ruleset::fromArray([0 => true]);
    }
}
