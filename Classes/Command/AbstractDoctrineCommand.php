<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Migrations\Command;

use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\DumpSchemaCommand;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\Migrations\Tools\Console\Command\GenerateCommand;
use Doctrine\Migrations\Tools\Console\Command\LatestCommand;
use Doctrine\Migrations\Tools\Console\Command\ListCommand;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\Tools\Console\Command\RollupCommand;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\Migrations\Tools\Console\Command\VersionCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use const DIRECTORY_SEPARATOR;

abstract class AbstractDoctrineCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption(
            'configuration',
            null,
            InputOption::VALUE_REQUIRED,
            'The path to a migrations configuration file. <comment>[default: any of migrations.{php,xml,json,yml,yaml}]</comment>'
        );
    }

    protected function runCli(): void
    {
        $connection = DriverManager::getConnection(
            [
                'dbname' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'],
                'user' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['user'],
                'password' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['password'],
                'host' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host'],
                'port' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['port'],
                'driver' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['driver'],
            ]
        );

        $config = new PhpFile($this->getConfigurationFilePath());

        $dependencyFactory = DependencyFactory::fromConnection($config, new ExistingConnection($connection));

        $cli = new Application('Doctrine Migrations');
        $cli->setCatchExceptions(true);

        $cli->addCommands([
            new DumpSchemaCommand($dependencyFactory),
            new ExecuteCommand($dependencyFactory),
            new GenerateCommand($dependencyFactory),
            new LatestCommand($dependencyFactory),
            new ListCommand($dependencyFactory),
            new MigrateCommand($dependencyFactory),
            new RollupCommand($dependencyFactory),
            new StatusCommand($dependencyFactory),
            //new SyncMetadataCommand($dependencyFactory),
            new VersionCommand($dependencyFactory),
        ]);

        $cli->run();
    }

    private function getConfigurationFilePath(): string
    {
        $configuration = GeneralUtility::makeInstance(
            ExtensionConfiguration::class
        )->get('migrations');

        return str_starts_with($configuration['path_to_configuration_file'], 'EXT:')
            ? GeneralUtility::getFileAbsFileName($configuration['path_to_configuration_file'])
            : Environment::getProjectPath()
                . DIRECTORY_SEPARATOR
                . ltrim($configuration['path_to_configuration_file'], DIRECTORY_SEPARATOR);
    }
}
