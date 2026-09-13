<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Standard;

use Stolt\CodingStandardMigrator\Exception\UnsupportedMigrationPath;
use Stolt\CodingStandardMigrator\Mapping\PhpCodeSnifferToMago\PhpCodeSnifferToMagoMapper;
use Stolt\CodingStandardMigrator\Mapping\PhpCsFixerToMago\PhpCsFixerToMagoMapper;
use Stolt\CodingStandardMigrator\Mapping\PintToMago\PintToMagoMapper;
use Stolt\CodingStandardMigrator\Mapping\RuleMapper;
use Stolt\CodingStandardMigrator\Reader\ConfigurationReader;
use Stolt\CodingStandardMigrator\Reader\PhpCodeSnifferConfigurationReader;
use Stolt\CodingStandardMigrator\Reader\PhpCsFixerConfigurationReader;
use Stolt\CodingStandardMigrator\Reader\PintConfigurationReader;
use Stolt\CodingStandardMigrator\Writer\ConfigurationWriter;
use Stolt\CodingStandardMigrator\Writer\MagoConfigurationWriter;

/**
 * Holds the readers, writers, and mappers which make up the supported migration paths.
 *
 * Supporting another standard means registering its reader, writer, or mapper here,
 * no other part of the engine needs to change.
 */
final class StandardRegistry
{
    /** @var array<string, ConfigurationReader> */
    private array $readers = [];

    /** @var array<string, ConfigurationWriter> */
    private array $writers = [];

    /** @var array<string, array<string, RuleMapper>> */
    private array $mappers = [];

    /**
     * The migration paths shipped with this package.
     */
    public function __construct()
    {
        $this
            ->registerReader(new PhpCsFixerConfigurationReader())
            ->registerReader(new PhpCodeSnifferConfigurationReader())
            ->registerReader(new PintConfigurationReader())
            ->registerWriter(new MagoConfigurationWriter())
            ->registerMapper(new PhpCsFixerToMagoMapper())
            ->registerMapper(new PhpCodeSnifferToMagoMapper())
            ->registerMapper(new PintToMagoMapper());
    }

    public static function empty(): self
    {
        $registry = new self();
        $registry->readers = [];
        $registry->writers = [];
        $registry->mappers = [];

        return $registry;
    }

    public function registerReader(ConfigurationReader $reader): self
    {
        $this->readers[$reader->standard()->value] = $reader;

        return $this;
    }

    public function registerWriter(ConfigurationWriter $writer): self
    {
        $this->writers[$writer->standard()->value] = $writer;

        return $this;
    }

    public function registerMapper(RuleMapper $mapper): self
    {
        $this->mappers[$mapper->from()->value][$mapper->to()->value] = $mapper;

        return $this;
    }

    /**
     * @throws UnsupportedMigrationPath
     */
    public function readerFor(Standard $standard): ConfigurationReader
    {
        return $this->readers[$standard->value] ?? throw UnsupportedMigrationPath::noReader($standard);
    }

    /**
     * @throws UnsupportedMigrationPath
     */
    public function writerFor(Standard $standard): ConfigurationWriter
    {
        return $this->writers[$standard->value] ?? throw UnsupportedMigrationPath::noWriter($standard);
    }

    /**
     * @throws UnsupportedMigrationPath
     */
    public function mapperFor(Standard $from, Standard $to): RuleMapper
    {
        return (
            $this->mappers[$from->value][$to->value] ?? throw UnsupportedMigrationPath::between(
                $from,
                $to,
                $this->migrationPaths(),
            )
        );
    }

    public function supports(Standard $from, Standard $to): bool
    {
        return isset(
            $this->readers[$from->value],
            $this->writers[$to->value],
            $this->mappers[$from->value][$to->value],
        );
    }

    /**
     * @return list<Standard>
     */
    public function sourceStandards(): array
    {
        return \array_values(\array_map(
            static fn(ConfigurationReader $reader): Standard => $reader->standard(),
            $this->readers,
        ));
    }

    /**
     * @return list<Standard>
     */
    public function targetStandards(): array
    {
        return \array_values(\array_map(
            static fn(ConfigurationWriter $writer): Standard => $writer->standard(),
            $this->writers,
        ));
    }

    /**
     * @return list<string> the supported paths as `source -> target`
     */
    public function migrationPaths(): array
    {
        $paths = [];

        foreach ($this->mappers as $from => $targets) {
            foreach (\array_keys($targets) as $to) {
                if (!$this->supports(Standard::from($from), Standard::from($to))) {
                    continue;
                }

                $paths[] = \sprintf('%s -> %s', $from, $to);
            }
        }

        \sort($paths);

        return $paths;
    }
}
