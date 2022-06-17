<?php

declare(strict_types=1);

namespace Visol\Migrations\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DoctrineLatestCommand extends AbstractDoctrineCommand
{
    protected function configure(): void
    {
        $this
            ->setAliases(['latest'])
            ->setDescription('Outputs the latest version');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->runCli();
    }
}
