<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Console\Command;

use Stolt\CodingStandardMigrator\Standard\Standard;
use Stolt\CodingStandardMigrator\Standard\StandardRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'standards', description: 'Shows the supported coding standards and migration paths')]
final class StandardsCommand extends Command
{
    public function __construct(
        private readonly StandardRegistry $registry,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Supported coding standards');

        $sources = $this->registry->sourceStandards();
        $targets = $this->registry->targetStandards();

        $rows = [];

        foreach (Standard::cases() as $standard) {
            $rows[] = [
                $standard->value,
                $standard->label(),
                \in_array($standard, $sources, true) ? 'yes' : 'not yet',
                \in_array($standard, $targets, true) ? 'yes' : 'not yet',
            ];
        }

        $io->table(['Name', 'Standard', 'As source', 'As target'], $rows);

        $io->section('Migration paths');
        $io->listing($this->registry->migrationPaths());

        return self::SUCCESS;
    }
}
