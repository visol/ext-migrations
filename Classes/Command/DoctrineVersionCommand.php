<?php

declare(strict_types=1);

namespace Visol\Migrations\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DoctrineVersionCommand extends AbstractDoctrineCommand
{
    protected function configure(): void
    {
        $this
            ->setAliases(['version'])
            ->setDescription('Manually add and delete migration versions from the version table.')
            ->addArgument(
                'version',
                InputArgument::OPTIONAL,
                'The version to add or delete.'
            )
            ->addOption(
                'add',
                null,
                InputOption::VALUE_NONE,
                'Add the specified version.'
            )
            ->addOption(
                'delete',
                null,
                InputOption::VALUE_NONE,
                'Delete the specified version.'
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Apply to all the versions.'
            )
            ->addOption(
                'range-from',
                null,
                InputOption::VALUE_OPTIONAL,
                'Apply from specified version.'
            )
            ->addOption(
                'range-to',
                null,
                InputOption::VALUE_OPTIONAL,
                'Apply to specified version.'
            )
            ->setHelp(
                <<<EOT
The <info>%command.name%</info> command allows you to manually add, delete or synchronize migration versions from the version table:

    <info>%command.full_name% MIGRATION-FQCN --add</info>

If you want to delete a version you can use the <comment>--delete</comment> option:

    <info>%command.full_name% MIGRATION-FQCN --delete</info>

If you want to synchronize by adding or deleting all migration versions available in the version table you can use the <comment>--all</comment> option:

    <info>%command.full_name% --add --all</info>
    <info>%command.full_name% --delete --all</info>

If you want to synchronize by adding or deleting some range of migration versions available in the version table you can use the <comment>--range-from/--range-to</comment> option:

    <info>%command.full_name% --add --range-from=MIGRATION-FQCN --range-to=MIGRATION-FQCN</info>
    <info>%command.full_name% --delete --range-from=MIGRATION-FQCN --range-to=MIGRATION-FQCN</info>

You can also execute this command without a warning message which you need to interact with:

    <info>%command.full_name% --no-interaction</info>
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
