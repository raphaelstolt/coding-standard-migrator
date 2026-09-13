<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Mapping\Mago;

use Stolt\CodingStandardMigrator\Mapping\Mapping;
use Stolt\CodingStandardMigrator\Ruleset\Rule;

/**
 * The knowledge of which rule of a source standard corresponds to which Mago setting.
 *
 * One implementation per rule vocabulary, not per source standard. PHP-CS-Fixer and
 * Pint share a table, as Pint configures PHP-CS-Fixer rules.
 */
interface RuleTable
{
    public function lookup(Rule $rule): Mapping;
}
