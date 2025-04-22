<?php

declare(strict_types=1);

namespace Visol\Migrations\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DoctrineRollupCommand extends AbstractDoctrineCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setAliases(['rollup'])
            ->setDescription('Rollup migrations by deleting all tracked versions and insert the one version that exists.')
            ->setHelp(
                <<<EOT
The <info>%command.name%</info> command rolls up migrations by deleting all tracked versions and
inserts the one version that exists that was created with the <info>migrations:dump-schema</info> command.

    <info>%command.full_name%</info>

To dump your schema to a migration version you can use the <info>migrations:dump-schema</info> command.
EOT
            );
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->runCli();
    }
}
