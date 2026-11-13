## 6.0.0 (unreleased)

Supports TYPO3 12.4, 13.4 and 14.3, and PHP up to 8.5.

- `tx_migrations.version` is now `varchar(191)` and carries its primary key in `ext_tables.sql`.
  With `varchar(1024)`, Doctrine could not add the primary key on TYPO3 13 and 14, which create
  tables as utf8mb4 ("Specified key was too long"). The schema update shortens existing columns;
  version names longer than 191 characters would be cut, which namespace and class name hardly
  reach.
- `all_or_nothing` is now off. MySQL commits implicitly on every schema change, and with
  Doctrine DBAL 4 a transaction around all migrations fails with "SAVEPOINT ... does not exist".
  Each migration still runs in its own transaction.
- The commands return the exit code of Doctrine Migrations instead of calling `exit()`.
- Integration tests for TYPO3 12, 13 and 14 on PHP 8.4 and 8.5, MariaDB and MySQL
  (`tests/run.sh`), run in GitHub Actions.

## 4.0.0 (2022-06-17)

refactor: replace existing namespace by visol/migrations
task: streamline ext_emconf.php
Update ext_emconf.php
Run rector
fix: Wrong filename
Update composer.json
