<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Exception;

use RuntimeException;

final class InvalidSourceConfiguration extends RuntimeException implements Exception
{
    public static function unexpectedReturnValue(string $file, string $actualType): self
    {
        return new self(\sprintf(
            'Expected %s to return a configuration object exposing a getRules() method or an array of rules, got %s.',
            $file,
            $actualType,
        ));
    }

    public static function unexpectedRulesType(string $file, string $actualType): self
    {
        return new self(\sprintf('Expected the rules of %s to be an array, got %s.', $file, $actualType));
    }

    public static function unparsable(string $file, string $failure): self
    {
        return new self(\sprintf('Failed to parse %s: %s.', $file, $failure));
    }

    public static function nonStringRuleName(): self
    {
        return new self('Encountered a rule without a name, expected rules to be keyed by their name.');
    }

    public static function unexpectedRuleConfiguration(string $rule, mixed $configuration): self
    {
        return new self(\sprintf(
            'Expected the configuration of rule "%s" to be a boolean or an array of options, got %s.',
            $rule,
            \get_debug_type($configuration),
        ));
    }
}
