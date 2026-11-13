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
            // Doctrine's default. Must match ext_tables.sql; 1024 characters do not fit the primary
            // key into an InnoDB index with utf8mb4, the default charset of TYPO3 13 and 14.
            'version_column_length' => 191,
            'executed_at_column_name' => 'executed_at',
            'execution_time_column_name' => 'execution_time',
        ],
        'migrations_paths' => [],
        // MySQL commits implicitly on every DDL statement. With Doctrine DBAL 4 (TYPO3 13 and 14) a
        // transaction around all migrations therefore fails with "SAVEPOINT ... does not exist"
        // as soon as one of them changes the schema. Each migration still runs in its own
        // transaction.
        'all_or_nothing' => false,
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
    protected function runCli(): int
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
        // Return the exit code to the TYPO3 console instead of calling exit() in between
        $cli->setAutoExit(false);

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

        return $cli->run();
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
