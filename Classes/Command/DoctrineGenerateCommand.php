<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Migrations\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DoctrineGenerateCommand extends AbstractDoctrineCommand
{
    protected function configure(): void
    {
        $this
            ->setAliases(['generate'])
            ->setDescription('Generate a blank migration class.')
            ->addOption(
                'namespace',
                null,
                InputOption::VALUE_REQUIRED,
                'The namespace to use for the migration (must be in the list of configured namespaces)'
            )
            ->setHelp(<<<EOT
The <info>%command.name%</info> command generates a blank migration class:

    <info>%command.full_name%</info>

EOT
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->runCli();
    }
}
