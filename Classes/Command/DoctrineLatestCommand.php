<?php

declare(strict_types=1);

namespace Visol\Migrations\Command;

class DoctrineLatestCommand extends AbstractDoctrineCommand
{
    protected function configure(): void
    {
        $this
            ->setAliases(['latest'])
            ->setDescription('Outputs the latest version');

        parent::configure();
    }
}
