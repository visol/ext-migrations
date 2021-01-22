<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Migrations\Command;

use FriendsOfTYPO3\Migrations\Service\DoctrineService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DoctrineMigrateCommand extends AbstractDoctrineCommand
{
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
        $this->setDescription('Adjusts the database structure by applying the pending ' . LF
            . ' migrations provided by currently active packages.')
            ->addOption(
                'version',
                '',
                InputOption::VALUE_OPTIONAL,
                'The version to migrate to',
                null
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_OPTIONAL,
                'A file to write SQL to, instead of executing it',
                null
            )
            ->addOption(
                'dryRun',
                '',
                InputOption::VALUE_OPTIONAL,
                'Whether to do a dry run or not',
                false
            )
            ->setHelp('Usage: ./vendor/bin/typo3 migrations:migrate [--version=VERSION] [--quiet] [--dryRun] [--output]');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $version = $input->getOption('version');
        $output = $input->getOption('output');
        $dryRun = $input->getOption('dryRun');
        $quiet = $input->getOption('quiet');

        $result = $this->doctrineService->executeMigrations($version, $output, $dryRun, $quiet);
        if ($result === '') {
            if (!$quiet) {
                $this->log('No migration was necessary.');
            }
        } elseif ($output === null) {
            $this->log($result);
        } else {
            if (!$quiet) {
                $this->log('Wrote migration SQL to file "' . $output . '".');
            }
        }

        return Command::SUCCESS;
    }
}
