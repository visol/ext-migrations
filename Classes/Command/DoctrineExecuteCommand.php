<?php

declare(strict_types=1);

namespace Visol\Migrations\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DoctrineExecuteCommand extends AbstractDoctrineCommand
{
    protected function configure(): void
    {
        $this
            ->setAliases(['execute'])
            ->setDescription(
                'Execute one or more migration versions up or down manually.'
            )
            ->addArgument(
                'versions',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'The versions to execute.'
            )
            ->addOption(
                'write-sql',
                null,
                InputOption::VALUE_OPTIONAL,
                'The path to output the migration SQL file instead of executing it. Defaults to current working directory.',
                false
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Execute the migration as a dry run.'
            )
            ->addOption(
                'up',
                null,
                InputOption::VALUE_NONE,
                'Execute the migration up.'
            )
            ->addOption(
                'down',
                null,
                InputOption::VALUE_NONE,
                'Execute the migration down.'
            )
            ->addOption(
                'query-time',
                null,
                InputOption::VALUE_NONE,
                'Time all the queries individually.'
            )
            ->setHelp(
                <<<EOT
The <info>%command.name%</info> command executes migration versions up or down manually:

    <info>%command.full_name% FQCN</info>

If no <comment>--up</comment> or <comment>--down</comment> option is specified it defaults to up:

    <info>%command.full_name% FQCN --down</info>

You can also execute the migration as a <comment>--dry-run</comment>:

    <info>%command.full_name% FQCN --dry-run</info>

You can output the would be executed SQL statements to a file with <comment>--write-sql</comment>:

    <info>%command.full_name% FQCN --write-sql</info>

Or you can also execute the migration without a warning message which you need to interact with:

    <info>%command.full_name% FQCN --no-interaction</info>

All the previous commands accept multiple migration versions, allowing you run execute more than
one migration at once:
    <info>%command.full_name% FQCN-1 FQCN-2 ...FQCN-n </info>

EOT
            );

        parent::configure();
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->runCli();
    }
}
