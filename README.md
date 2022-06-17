# Migrate with doctrine migrations

This is a TYPO3 extension that integrates the [doctrine migration](https://www.doctrine-project.org/projects/migrations.html) tool.

# Installation

Install the extension with composer. For the time being it is required to declare manually the git repository.

```json
"repositories": [
  {
    "type": "git",
    "url": "https://github.com/visol/ext-migrations.git"
  }
],
```

```shell
composer require visol/migrations
```

After installing the extension, you might consider overriding the path to the configuration file in the extension manager of TYPO3.

The default configuration file is to be found in `EXT:migrations/Configuration/migrations.php`

# Usage

````shell
 migrations
  migrations:dump                    Dump the schema for your database to a migration.
  migrations:generate                Generate a blank migration class.
  migrations:latest                  Outputs the latest version
  migrations:list                    Display a list of all available migrations and their status.
  migrations:migrate                 Execute one or more migration versions up or down manually.
  migrations:rollup                  Rollup migrations by deleting all tracked versions and insert the one version that exists.
  migrations:status                  View the status of a set of migrations.
  migrations:version                 Manually add and delete migration versions from the version table.
````

To get started, we can recommend executing the command `migrations:generate`. It will generate a default migration skeleton file.

# Credits

Source of inspiration and first implementation from Kai Strobach
https://git.kay-strobach.de/typo3/migrations
