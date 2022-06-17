<?php

declare(strict_types=1);

namespace Visol\Migrations\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DoctrineListCommand extends AbstractDoctrineCommand
{
    protected function configure(): void
    {
        $this
            ->setAliases(['list-migrations'])
            ->setDescription('Display a list of all available migrations and their status.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command outputs a list of all available migrations and their status:

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
