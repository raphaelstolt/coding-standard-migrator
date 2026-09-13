<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Mapping\PhpCodeSnifferToMago;

use Stolt\CodingStandardMigrator\Mapping\Mago\AbstractRuleMapper;
use Stolt\CodingStandardMigrator\Standard\Standard;

final readonly class PhpCodeSnifferToMagoMapper extends AbstractRuleMapper
{
    public function __construct(MappingTable $table = new MappingTable())
    {
        parent::__construct($table);
    }

    public function from(): Standard
    {
        return Standard::PhpCodeSniffer;
    }

    protected function indentOrigin(): string
    {
        return '<arg name="tab-width"/>';
    }
}
