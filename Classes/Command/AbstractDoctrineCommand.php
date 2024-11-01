<?php

declare(strict_types=1);

namespace Visol\Migrations\Command;

use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
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
use Exception;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractDoctrineCommand extends Command
{
    protected array $defaultConfiguration = [
        'table_storage' => [
            'table_name' => 'tx_migrations',
            'version_column_name' => 'version',
            'version_column_length' => 1024,
            'executed_at_column_name' => 'executed_at',
            'execution_time_column_name' => 'execution_time',
        ],
        'migrations_paths' => [],
        'all_or_nothing' => true,
        'check_database_platform' => true,
        'organize_migrations' => 'none',
    ];

    protected function configure(): void
    {
        $this->addOption(
            'configuration',
            null,
            InputOption::VALUE_REQUIRED,
            'The path to a migrations configuration file. <comment>[default: any of migrations.{php,xml,json,yml,yaml}]</comment>'
        );
    }

    /**
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \Doctrine\DBAL\Exception
     */
    protected function runCli(): void
    {
        $connection = DriverManager::getConnection($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']);

        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('migrations');

        if (
            !isset($extConf['migrationsPaths'])
            || !is_array($extConf['migrationsPaths'])
        ) {
            throw new Exception(
                "Invalid configuration in $['TYPO3_CONF_VARS']['EXTENSIONS']['migrations']['migrationsPaths'].
Please configure your namespaces and paths in LocalConfiguration.php, e.g.

'EXTENSIONS' => [
    ...
    'migrations' => [
        'migrationsPaths' => [
            'MyProject\Migrations' => 'EXT:myprojct/Migrations',
        ],
    ],
    ...
],
                ",
                1612181286
            );
        }

        $configuration = array_merge(
            $this->defaultConfiguration,
            [
                'migrations_paths' => $extConf['migrationsPaths'],
            ],
        );

        $this->parseTYPO3ExtPathsAndEnsureFolderExists($configuration['migrations_paths']);

        $dependencyFactory = DependencyFactory::fromConnection(
            new ConfigurationArray($configuration),
            new ExistingConnection($connection)
        );

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

    protected function parseTYPO3ExtPathsAndEnsureFolderExists(array &$migrations_paths): void
    {
        array_walk(
            $migrations_paths,
            function (&$path) {
                $path = GeneralUtility::getFileAbsFileName($path);
                GeneralUtility::mkdir_deep($path);
            }
        );
    }

}
