<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Mapping\PintToMago;

use Stolt\CodingStandardMigrator\Mapping\Mago\AbstractRuleMapper;
use Stolt\CodingStandardMigrator\Mapping\PhpCsFixerToMago\MappingTable;
use Stolt\CodingStandardMigrator\Standard\Standard;

/**
 * Pint is a wrapper around PHP-CS-Fixer and configures its rules, so this migration
 * deliberately reuses the PHP-CS-Fixer mapping table. Only the presets of the two
 * tools differ, which the Pint reader normalises into rule sets while reading.
 */
final readonly class PintToMagoMapper extends AbstractRuleMapper
{
    public function __construct(MappingTable $table = new MappingTable())
    {
        parent::__construct($table);
    }

    public function from(): Standard
    {
        return Standard::Pint;
    }
}
