<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Mapping;

/**
 * What happened to a source rule while mapping it onto the target standard.
 */
enum MappingOutcome: string
{
    /** The rule became one or more rules of the target linter. */
    case LinterRule = 'linter-rule';

    /** The rule became one or more options of the target formatter. */
    case FormatterOption = 'formatter-option';

    /** The target formatter does this unconditionally, no configuration needed. */
    case CoveredByFormatter = 'covered-by-formatter';

    /** The rule was disabled in the source configuration and needed no translation. */
    case Skipped = 'skipped';

    /** The target standard knowingly has no equivalent. */
    case Unsupported = 'unsupported';

    /** No mapping is known for the rule, it needs a manual decision. */
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::LinterRule => 'linter rule',
            self::FormatterOption => 'formatter option',
            self::CoveredByFormatter => 'covered by formatter',
            self::Skipped => 'skipped',
            self::Unsupported => 'unsupported',
            self::Unknown => 'unknown',
        };
    }

    /**
     * Whether the outcome ends up in the generated configuration.
     */
    public function isTranslated(): bool
    {
        return $this === self::LinterRule || $this === self::FormatterOption;
    }

    /**
     * Whether the outcome asks for a follow-up by a human.
     */
    public function needsAttention(): bool
    {
        return $this === self::Unsupported || $this === self::Unknown;
    }
}
