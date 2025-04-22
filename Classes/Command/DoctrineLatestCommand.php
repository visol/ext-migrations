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

    /**
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \Doctrine\DBAL\Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->runCli();
    }
}
