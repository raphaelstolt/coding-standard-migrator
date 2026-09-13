<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Mapping;

use Stolt\CodingStandardMigrator\Ruleset\SourceConfiguration;
use Stolt\CodingStandardMigrator\Standard\Standard;

/**
 * Translates the rules of one standard into the rules and options of another.
 *
 * One implementation per migration path, e.g. PHP-CS-Fixer to Mago.
 */
interface RuleMapper
{
    public function from(): Standard;

    public function to(): Standard;

    public function map(SourceConfiguration $source): MappingReport;
}
