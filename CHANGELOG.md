## 6.0.0 (unreleased)

Supports TYPO3 12.4, 13.4 and 14.3, and PHP up to 8.5.

- `tx_migrations.version` is now `varchar(191)` with its primary key. The schema update shortens
  existing columns; version names longer than 191 characters would be cut.
- `all_or_nothing` is off: each migration runs in its own transaction, no longer all of them in
  one.
- The commands return the exit code of Doctrine Migrations instead of calling `exit()`.
- Integration tests (`tests/run.sh`), run in GitHub Actions.

## 4.0.0 (2022-06-17)

refactor: replace existing namespace by visol/migrations
task: streamline ext_emconf.php
Update ext_emconf.php
Run rector
fix: Wrong filename
Update composer.json
