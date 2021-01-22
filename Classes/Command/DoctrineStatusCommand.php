<?php

namespace FriendsOfTYPO3\Migrations\Command;

use FriendsOfTYPO3\Migrations\Service\DoctrineService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DoctrineStatusCommand extends AbstractDoctrineCommand
{

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        /** @var  DoctrineService doctrineService */
        $this->doctrineService = GeneralUtility::makeInstance(
            DoctrineService::class
        );
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this->setDescription('Show available migration.')
            ->setHelp('Prints a list of available migrations' . LF . 'If you want to get more detailed information, use the --verbose option.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = $this->doctrineService->getMigrationStatus();

        $this->log($output);

        return Command::SUCCESS;
    }
}
