## 6.0.0 (unreleased)

Supports TYPO3 12.4, 13.4 and 14.3, and PHP up to 8.5.

- Drops TYPO3 11.
- `tx_migrations.version` is now `varchar(191)` with its primary key. The schema update shortens
  existing columns and fails if a version name is longer than 191 characters; check with
  `SELECT MAX(CHAR_LENGTH(version)) FROM tx_migrations` before updating. Where the table has no
  primary key yet, the first schema update reports "Specified key was too long" and a second one
  completes it.
- On MySQL and MariaDB, `all_or_nothing` is off: a failing migration no longer rolls back the
  ones before it in the same run. Schema changes on MySQL commit immediately anyway, even inside a
  migration. Other platforms keep `all_or_nothing`.
- The commands return the exit code of Doctrine Migrations instead of calling `exit()`.
- Integration tests (`tests/run.sh`), run in GitHub Actions.

## 4.0.0 (2022-06-17)

refactor: replace existing namespace by visol/migrations
task: streamline ext_emconf.php
Update ext_emconf.php
Run rector
fix: Wrong filename
Update composer.json
