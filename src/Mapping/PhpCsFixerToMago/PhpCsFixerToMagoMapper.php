<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Mapping\PhpCsFixerToMago;

use Stolt\CodingStandardMigrator\Mapping\Mago\AbstractRuleMapper;
use Stolt\CodingStandardMigrator\Standard\Standard;

final readonly class PhpCsFixerToMagoMapper extends AbstractRuleMapper
{
    public function __construct(MappingTable $table = new MappingTable())
    {
        parent::__construct($table);
    }

    public function from(): Standard
    {
        return Standard::PhpCsFixer;
    }

    protected function indentOrigin(): string
    {
        return 'Config::setIndent()';
    }

    protected function lineEndingOrigin(): string
    {
        return 'Config::setLineEnding()';
    }
}
