<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Console\Command;

use Stolt\CodingStandardMigrator\Exception\Exception as MigratorException;
use Stolt\CodingStandardMigrator\Mapping\Mapping;
use Stolt\CodingStandardMigrator\Mapping\MappingOutcome;
use Stolt\CodingStandardMigrator\Migration\MigrationEngine;
use Stolt\CodingStandardMigrator\Migration\MigrationRequest;
use Stolt\CodingStandardMigrator\Migration\MigrationResult;
use Stolt\CodingStandardMigrator\Standard\Standard;
use Stolt\CodingStandardMigrator\Writer\TomlEncoder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'migrate', description: 'Migrates a coding-standard configuration to another coding standard')]
final class MigrateCommand extends Command
{
    use LocatesSourceConfiguration;

    public function __construct(
        private readonly MigrationEngine $engine,
        private readonly TomlEncoder $encoder = new TomlEncoder(),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'from',
                null,
                InputOption::VALUE_REQUIRED,
                'The coding standard to migrate from',
                Standard::PhpCsFixer->value,
            )
            ->addOption(
                'to',
                null,
                InputOption::VALUE_REQUIRED,
                'The coding standard to migrate to',
                Standard::Mago->value,
            )
            ->addOption('working-dir', 'd', InputOption::VALUE_REQUIRED, 'The directory to run in', '.')
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_REQUIRED,
                'The source configuration file, autodetected when omitted',
            )
            ->addOption('target', 't', InputOption::VALUE_REQUIRED, 'The file to write the migrated configuration to')
            ->addOption(
                'php-version',
                null,
                InputOption::VALUE_REQUIRED,
                'The PHP version to pin in the migrated configuration',
            )
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'A path the coding standard applies to, repeatable',
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Shows the migrated configuration instead of writing it',
            )
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrites an existing target configuration')
            ->addOption('fail-on-unmapped', null, InputOption::VALUE_NONE, 'Fails when a rule could not be migrated')
            ->setHelp(<<<'HELP'
                Migrates the coding-standard configuration found in the working directory:

                  <info>%command.full_name%</info>

                Preview the migration of a specific configuration file without writing it:

                  <info>%command.full_name% --config .php-cs-fixer.dist.php --dry-run</info>
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $workingDirectory = $this->workingDirectory($input);
            $from = Standard::fromName($this->stringOption($input, 'from') ?? '');
            $to = Standard::fromName($this->stringOption($input, 'to') ?? '');

            $result = $this->engine->migrate(new MigrationRequest(
                from: $from,
                to: $to,
                sourceFile: $this->sourceFile($input, $this->engine, $from, $workingDirectory),
                phpVersion: $this->stringOption($input, 'php-version'),
                paths: $this->pathsOption($input),
            ));
        } catch (MigratorException $failure) {
            $io->error($failure->getMessage());

            return self::FAILURE;
        }

        $io->title(\sprintf('Migrating %s to %s', $result->request->from->label(), $result->request->to->label()));
        $io->text(\sprintf('Source: <info>%s</info>', $result->source->file));

        $this->renderReport($io, $result);

        $targetFile = $this->stringOption($input, 'target') ?? $this->resolve(
            $workingDirectory,
            $result->targetFileName,
        );

        if ($input->getOption('dry-run') === true) {
            $io->section(\sprintf('Migrated configuration (%s)', \basename($targetFile)));
            $io->writeln($result->configuration);
            $io->note('Nothing was written, drop --dry-run to persist the migrated configuration.');

            return $this->exitCode($input, $io, $result);
        }

        if (\is_file($targetFile) && $input->getOption('force') !== true) {
            $io->error(\sprintf('%s already exists, pass --force to overwrite it.', $targetFile));

            return self::FAILURE;
        }

        if (\file_put_contents($targetFile, $result->configuration) === false) {
            $io->error(\sprintf('Failed to write %s.', $targetFile));

            return self::FAILURE;
        }

        $io->success(\sprintf('Wrote %s.', $targetFile));

        return $this->exitCode($input, $io, $result);
    }

    private function renderReport(SymfonyStyle $io, MigrationResult $result): void
    {
        $io->section('Mapped rules');

        if ($result->report->count() === 0) {
            $io->warning('The source configuration does not hold any rules.');

            return;
        }

        $io->table(['Rule', 'Outcome', $result->request->to->label()], \array_map(fn(Mapping $mapping): array => [
            $mapping->sourceRule,
            $mapping->outcome->label(),
            $this->renderTargets($mapping),
        ], $result->report->all()));

        $summary = [];

        foreach ($result->report->summary() as $outcome => $count) {
            $summary[] = \sprintf('%d %s', $count, $outcome);
        }

        $io->text(\sprintf('%d rules: %s.', $result->report->count(), \implode(', ', $summary)));

        $notes = [];

        foreach ($result->report as $mapping) {
            if ($mapping->note === null || $mapping->outcome === MappingOutcome::Skipped) {
                continue;
            }

            $notes[] = \sprintf('<comment>%s</comment>: %s', $mapping->sourceRule, $mapping->note);
        }

        if ($notes !== []) {
            $io->section('Notes');
            $io->listing($notes);
        }
    }

    private function renderTargets(Mapping $mapping): string
    {
        if ($mapping->settings === []) {
            return '-';
        }

        if ($mapping->outcome === MappingOutcome::LinterRule) {
            return \implode(', ', $mapping->targets());
        }

        $targets = [];

        foreach ($mapping->settings as $option => $value) {
            $targets[] = $this->encoder->keyValue($option, $value);
        }

        return \implode(', ', $targets);
    }

    private function exitCode(InputInterface $input, SymfonyStyle $io, MigrationResult $result): int
    {
        if ($result->isComplete() || $input->getOption('fail-on-unmapped') !== true) {
            return self::SUCCESS;
        }

        $io->error(\sprintf('%d rules could not be migrated.', \count($result->report->needingAttention())));

        return self::FAILURE;
    }

    /**
     * @return list<string>
     */
    private function pathsOption(InputInterface $input): array
    {
        $paths = $input->getOption('path');

        if (!\is_array($paths)) {
            return [];
        }

        return \array_values(\array_filter($paths, \is_string(...)));
    }
}
