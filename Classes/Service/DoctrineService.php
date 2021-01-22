<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Migrations\Service;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\Migrations\Configuration\AbstractFileConfiguration;
use Doctrine\Migrations\Migrator;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\Version;
use FriendsOfTYPO3\Migrations\Utility\Files;
use ReflectionClass;
use Symfony\Component\Console\Formatter\OutputFormatter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

class DoctrineService
{
    protected array $output;
    protected PackageManager $packageManager;
    protected Configuration $config;

    public function __construct()
    {
        $this->packageManager = GeneralUtility::makeInstance(PackageManager::class);
        $this->config = new Configuration();
    }

    /**
     * Return the configuration needed for Migrations.
     *
     * @throws Exception
     */
    protected function getMigrationConfiguration(): \Doctrine\Migrations\Configuration\Configuration
    {
        $this->output = [];
        $that = $this;
        $outputWriter = new OutputWriter(
            function ($message) use ($that) {
                $outputFormatter = new OutputFormatter(true);
                echo $outputFormatter->format($message);
                $that->output[] = $message;
            }
        );

        // todo improve me! Most common  types, Sqllite, PostgreSQL,
        // todo improve me! implement migrationexecute --direction, migrationversion get it from Flow as inspiration!
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['driver'];
        $connection = DriverManager::getConnection(
            [
                'dbname' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'],
                'user' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['user'],
                'password' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['password'],
                'host' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host'],
                'port' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['port'],
                'driver' => 'pdo_mysql',
            ]
        );

        $configuration = new \Doctrine\Migrations\Configuration\Configuration($connection, $outputWriter);
        $configuration->setMigrationsNamespace('FriendsOfTYPO3\Migrations\Persistence\Doctrine\Migrations');
        // todo improve me!
        $directoryAndPath = Environment::getConfigPath() . '/Migrations';
        GeneralUtility::mkdir_deep($directoryAndPath);
        $configuration->setMigrationsDirectory($directoryAndPath);
        $configuration->setMigrationsTableName('migrations_doctrine_migrationstatus');

        $configuration->createMigrationTable();

        $databasePlatformName = 'MySql';
        foreach ($this->packageManager->getActivePackages() as $package) {
            $path = implode(
                DIRECTORY_SEPARATOR,
                [
                    rtrim($package->getPackagePath(), DIRECTORY_SEPARATOR),
                    'Migrations',
                    $databasePlatformName,
                ]
            );

            if (is_dir($path)) {
                $configuration->registerMigrationsFromDirectory($path);
            }
        }
        return $configuration;
    }

    /**
     * Returns the current migration status formatted as plain text.
     */
    public function getMigrationStatus(): string
    {
        $configuration = $this->getMigrationConfiguration();

        $currentVersion = $configuration->getCurrentVersion();

        if ($currentVersion) {
            $currentVersionFormatted = $configuration->getDateTime($currentVersion) . ' (' . $currentVersion . ')';
        } else {
            $currentVersionFormatted = 0;
        }
        $latestVersion = $configuration->getLatestVersion();
        if ($latestVersion) {
            $latestVersionFormatted = $configuration->getDateTime($latestVersion) . ' (' . $latestVersion . ')';
        } else {
            $latestVersionFormatted = 0;
        }

        $executedMigrations = $configuration->getNumberOfExecutedMigrations();
        $availableMigrations = $configuration->getNumberOfAvailableMigrations();
        $newMigrations = $availableMigrations - $executedMigrations;

        $output = "\n == Configuration\n";

        $info = [
            'Name' => $configuration->getName() ? $configuration->getName() : 'Doctrine Database Migrations',
            'Database Driver' => $configuration->getConnection()->getDriver()->getName(),
            'Database Name' => $configuration->getConnection()->getDatabase(),
            'Configuration Source' => $configuration instanceof AbstractFileConfiguration ? $configuration->getFile() : 'manually configured',
            'Version Table Name' => $configuration->getMigrationsTableName(),
            'Migrations Namespace' => $configuration->getMigrationsNamespace(),
            'Migrations Target Directory' => $configuration->getMigrationsDirectory(),
            'Current Version' => $currentVersionFormatted,
            'Latest Version' => $latestVersionFormatted,
            'Available Migrations' => $availableMigrations,
            'Executed Migrations' => $executedMigrations,
            'New Migrations' => $newMigrations,
        ];
        foreach ($info as $name => $value) {
            $output .= '    >> ' . $name . ': ' . str_repeat(' ', 50 - strlen($name)) . $value . PHP_EOL;
        }

        if ($migrations = $configuration->getMigrations()) {
            $output .= "\n == Migration Versions\n";
            foreach ($migrations as $version) {
                $packageKey = $this->getPackageKeyFromMigrationVersion($version);
                $croppedPackageKey = strlen($packageKey) < 24 ? $packageKey : substr($packageKey, 0, 23) . '~';
                $packageKeyColumn = ' ' . str_pad($croppedPackageKey, 24, ' ');
                $status = $version->isMigrated() ? 'migrated' : 'not migrated';
                $output .= '    >> ' . $configuration->getDateTime($version->getVersion()) . ' (' . $version->getVersion() . ')' . $packageKeyColumn . str_repeat(' ', 4) . $status . PHP_EOL;
                if ($version->getMigration()->getDescription() !== '') {
                    $output .= '       ' . $version->getMigration()->getDescription() . PHP_EOL;
                }
            }
        }

        return $output;
    }

    /**
     * Tries to find out a package key which the Version belongs to. If no
     * package could be found, an empty string is returned.
     */
    protected function getPackageKeyFromMigrationVersion(Version $version): string
    {
        $sortedAvailablePackages = $this->packageManager->getAvailablePackages();
        usort($sortedAvailablePackages, function (PackageInterface $packageOne, PackageInterface $packageTwo) {
            return strlen($packageTwo->getPackagePath()) - strlen($packageOne->getPackagePath());
        });

        $reflectedClass = new ReflectionClass($version->getMigration());
        $classPathAndFilename = Files::getUnixStylePath($reflectedClass->getFileName());

        /** @var PackageInterface $package */
        foreach ($sortedAvailablePackages as $package) {
            $packagePath = Files::getUnixStylePath($package->getPackagePath());
            if (strpos($classPathAndFilename, $packagePath) === 0) {
                return $package->getPackageKey();
            }
        }

        return '';
    }

    /**
     * Execute all new migrations, up to $version if given.
     *
     * If $outputPathAndFilename is given, the SQL statements will be written to the given file instead of executed.
     */
    public function executeMigrations(?string $version, ?string $outputPathAndFilename, bool $dryRun = false, bool $quiet = false): string
    {
//        $migratorConfiguration = new MigratorConfiguration();
//        $migratorConfiguration->setDryRun($dryRun || $outputPathAndFilename !== null);

        $configuration = $this->getMigrationConfiguration();
        $migration = new Migrator($configuration);

        if ($outputPathAndFilename !== null) {
            $migration->writeSqlFile($outputPathAndFilename, $version);
        } else {
            $migration->migrate($version, $dryRun);
        }

        if ($quiet === true) {
            $output = '';
            foreach ($this->output as $line) {
                $line = strip_tags($line);
                if (strpos($line, '  ++ migrating ') !== false || strpos($line, '  -- reverting ') !== false) {
                    $output .= substr($line, -15);
                }
            }
            return $output;
        } else {
            return strip_tags(implode(PHP_EOL, $this->output));
        }
    }
}
