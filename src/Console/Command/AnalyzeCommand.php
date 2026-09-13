<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Console\Command;

use Stolt\CodingStandardMigrator\Analysis\MigrationAnalysis;
use Stolt\CodingStandardMigrator\Exception\Exception as MigratorException;
use Stolt\CodingStandardMigrator\Migration\MigrationEngine;
use Stolt\CodingStandardMigrator\Migration\MigrationRequest;
use Stolt\CodingStandardMigrator\Standard\Standard;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'analyze',
    description: 'Shows how much of a coding-standard configuration a migration would carry over',
    aliases: ['analyse'],
)]
final class AnalyzeCommand extends Command
{
    use LocatesSourceConfiguration;

    /**
     * The column the counts are aligned at.
     */
    private const LABEL_WIDTH = 22;

    public function __construct(
        private readonly MigrationEngine $engine,
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
                'The coding standard to analyze',
                Standard::PhpCsFixer->value,
            )
            ->addOption(
                'to',
                null,
                InputOption::VALUE_REQUIRED,
                'The coding standard to analyze the migration to',
                Standard::Mago->value,
            )
            ->addOption('working-dir', 'd', InputOption::VALUE_REQUIRED, 'The directory to run in', '.')
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_REQUIRED,
                'The configuration file to analyze, autodetected when omitted',
            )
            ->addOption(
                'fail-under',
                null,
                InputOption::VALUE_REQUIRED,
                'Fails when the migration confidence is below this percentage',
            )
            ->setHelp(<<<'HELP'
                Analyzes the coding-standard configuration found in the working directory:

                  <info>%command.full_name%</info>

                Run it before <info>migrate</info> to see how much of a configuration survives the
                migration. Add <info>--verbose</info> to list the rules which need a manual decision,
                or <info>--fail-under</info> to turn the migration confidence into a CI gate:

                  <info>%command.full_name% --from phpcs --fail-under 80</info>
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $workingDirectory = $this->workingDirectory($input);
            $from = Standard::fromName($this->stringOption($input, 'from') ?? '');
            $to = Standard::fromName($this->stringOption($input, 'to') ?? '');

            $analysis = $this->engine->analyze(new MigrationRequest(
                from: $from,
                to: $to,
                sourceFile: $this->sourceFile($input, $this->engine, $from, $workingDirectory),
            ));
        } catch (MigratorException $failure) {
            $io->error($failure->getMessage());

            return self::FAILURE;
        }

        $this->renderAnalysis($io, $analysis);

        if ($output->isVerbose() && !$analysis->isComplete()) {
            $io->section('Rules needing a manual decision');
            $io->listing($analysis->needsAttention);
        }

        return $this->exitCode($input, $io, $analysis);
    }

    private function renderAnalysis(SymfonyStyle $io, MigrationAnalysis $analysis): void
    {
        $heading = \sprintf('%s configuration', $analysis->from->label());

        $io->newLine();
        $io->writeln(' <options=bold>' . $heading . '</>');
        $io->writeln(' ' . \str_repeat('─', Helper::width($heading)));
        $io->newLine();

        foreach ($analysis->counts() as $label => $count) {
            $io->writeln(\sprintf(
                '%s%s',
                \str_pad($label . ':', self::LABEL_WIDTH),
                \str_pad((string) $count, 4, ' ', \STR_PAD_LEFT),
            ));
        }

        $io->newLine();

        $confidence = $analysis->confidence();

        if ($confidence === null) {
            $io->writeln('Migration confidence: <comment>n/a</comment>, the configuration holds no rules.');
            $io->newLine();

            return;
        }

        $io->writeln(\sprintf(
            'Migration confidence: <%1$s>%2$d%%</%1$s>',
            $this->confidenceStyle($confidence),
            $confidence,
        ));
        $io->newLine();
    }

    private function confidenceStyle(int $confidence): string
    {
        return match (true) {
            $confidence >= 80 => 'info',
            $confidence >= 50 => 'comment',
            default => 'error',
        };
    }

    private function exitCode(InputInterface $input, SymfonyStyle $io, MigrationAnalysis $analysis): int
    {
        $threshold = $this->stringOption($input, 'fail-under');

        if ($threshold === null) {
            return self::SUCCESS;
        }

        $confidence = $analysis->confidence() ?? 0;

        if ($confidence >= (int) $threshold) {
            return self::SUCCESS;
        }

        $io->error(\sprintf(
            'The migration confidence of %d%% is below the required %d%%.',
            $confidence,
            (int) $threshold,
        ));

        return self::FAILURE;
    }
}
