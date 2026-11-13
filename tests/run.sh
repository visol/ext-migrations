#!/usr/bin/env bash
#
# Installs the extension into a fresh TYPO3 project and runs the migrations commands against a
# real database.
#
# Usage: tests/run.sh <typo3-major>     e.g. tests/run.sh 14
#
# Database, from the environment:
#   DB_HOST (db), DB_PORT (3306), DB_NAME (typo3), DB_USER (root), DB_PASSWORD (root)
# The database must exist and be empty.
#
# Scenarios:
#   1. Fresh installation: tx_migrations comes from ext_tables.sql, then generate, migrate,
#      list, status and execute --down, including the exit code of a failing migration.
#   2. Tables from older versions of this extension, after the schema update executed migrations
#      are still known and new ones run:
#      a. version varchar(1024) DEFAULT '' without primary key, as the old ext_tables.sql made it;
#         this one needs two schema updates, see upgrade_from()
#      b. the same with the primary key Doctrine added on utf8mb3 (e.g. on TYPO3 12)
#
# The project is removed afterwards; set KEEP_PROJECT=1 to keep it for debugging.

set -euo pipefail

TYPO3_MAJOR="${1:?Usage: tests/run.sh <typo3-major, e.g. 12, 13 or 14>}"
case "${TYPO3_MAJOR}" in
    12) TYPO3_CONSTRAINT='^12.4' ;;
    13) TYPO3_CONSTRAINT='^13.4' ;;
    14) TYPO3_CONSTRAINT='^14.3' ;;
    *) echo "Unsupported TYPO3 major version: ${TYPO3_MAJOR}" >&2; exit 2 ;;
esac

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-typo3}"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD:-root}"

EXTENSION_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PROJECT_PATH="$(mktemp -d)"
[ "${KEEP_PROJECT:-0}" = "1" ] || trap 'rm -rf "${PROJECT_PATH}"' EXIT
MIGRATIONS_PATH="${PROJECT_PATH}/migrations"
NAMESPACE='MigTest\Migrations'

FAILURES=0

pass() { echo "  ok   $1"; }
fail() { echo "  FAIL $1"; FAILURES=$((FAILURES + 1)); }

# Runs one SQL statement and prints the first column of every row
sql() {
    php -d display_errors=stderr -r '
        $db = new mysqli($argv[1], $argv[2], $argv[3], $argv[4], (int)$argv[5]);
        $result = $db->query($argv[6]);
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_row()) { echo $row[0], "\n"; }
        }
    ' "${DB_HOST}" "${DB_USER}" "${DB_PASSWORD}" "${DB_NAME}" "${DB_PORT}" "$1"
}

typo3() {
    "${PROJECT_PATH}/vendor/bin/typo3" "$@"
}

# Prints one field of information_schema.COLUMNS: column <table> <column> <field>
column() {
    sql "SELECT $3 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$1' AND COLUMN_NAME = '$2'"
}

# expect_exit <expected code, or !0 for any failure> <description> <command...>
expect_exit() {
    local expected="$1" description="$2" code=0
    shift 2
    "$@" > "${PROJECT_PATH}/last-output.txt" 2>&1 || code=$?
    if [ "${code}" = "${expected}" ] || { [ "${expected}" = '!0' ] && [ "${code}" != 0 ]; }; then
        pass "${description} (exit ${code})"
    else
        fail "${description}: expected exit ${expected}, got ${code}"
        sed 's/^/       /' "${PROJECT_PATH}/last-output.txt" | tail -20
    fi
}

# expect_equals <expected> <actual> <description>
expect_equals() {
    if [ "$1" = "$2" ]; then
        pass "$3"
    else
        fail "$3: expected '$1', got '$2'"
    fi
}

write_migration() {
    local version="$1" up="$2" down="$3"
    cat > "${MIGRATIONS_PATH}/${version}.php" <<EOF
<?php

declare(strict_types=1);

namespace MigTest\\Migrations;

use Doctrine\\DBAL\\Schema\\Schema;
use Doctrine\\Migrations\\AbstractMigration;

final class ${version} extends AbstractMigration
{
    public function up(Schema \$schema): void
    {
        ${up}
    }

    public function down(Schema \$schema): void
    {
        ${down}
    }
}
EOF
}

echo "TYPO3 ${TYPO3_CONSTRAINT}, project in ${PROJECT_PATH}"

php -r 'exit(extension_loaded("mysqli") ? 0 : 1);' || { echo "The PHP extension mysqli is missing" >&2; exit 1; }

# A database service in CI may still be starting when the job begins
for attempt in $(seq 1 30); do
    sql 'SELECT 1' > /dev/null 2>&1 && break
    [ "${attempt}" = "30" ] && { echo "Database ${DB_HOST}:${DB_PORT}/${DB_NAME} not reachable" >&2; exit 1; }
    sleep 2
done
if [ "$(sql 'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()')" != "0" ]; then
    echo "Database ${DB_NAME} is not empty; the tests need an empty one" >&2
    exit 1
fi

cat > "${PROJECT_PATH}/composer.json" <<EOF
{
    "name": "visol/migrations-test-project",
    "type": "project",
    "repositories": [{"type": "path", "url": "${EXTENSION_PATH}", "options": {"symlink": false}}],
    "require": {
        "typo3/cms-backend": "${TYPO3_CONSTRAINT}",
        "typo3/cms-core": "${TYPO3_CONSTRAINT}",
        "typo3/cms-extbase": "${TYPO3_CONSTRAINT}",
        "typo3/cms-fluid": "${TYPO3_CONSTRAINT}",
        "typo3/cms-frontend": "${TYPO3_CONSTRAINT}",
        "typo3/cms-install": "${TYPO3_CONSTRAINT}",
        "helhum/typo3-console": "*",
        "visol/migrations": "@dev"
    },
    "config": {
        "allow-plugins": true,
        "policy": {"advisories": {"block": false}},
        "audit": {"block-insecure": false}
    }
}
EOF

cd "${PROJECT_PATH}"
# The two advisory settings above (Composer 2.11+ and 2.9/2.10): every public release of 12.4
# has security advisories, the fixed ones are ELTS only, and Composer refuses to install them.
# Irrelevant for a throwaway test project.
composer install --no-interaction --no-progress

typo3 setup --no-interaction --force --driver=mysqli --host="${DB_HOST}" --port="${DB_PORT}" \
    --dbname="${DB_NAME}" --username="${DB_USER}" --password="${DB_PASSWORD}" \
    --admin-username=admin --admin-user-password='Migrations-Test-1!' --admin-email='' \
    --project-name='migrations test' --server-type=other > /dev/null

mkdir -p config/system
cat > config/system/additional.php <<EOF
<?php
\$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['migrations']['migrationsPaths'] = [
    '${NAMESPACE}' => '${MIGRATIONS_PATH}',
];
EOF

echo "Scenario 1: fresh installation"

expect_equals "191" "$(column tx_migrations version CHARACTER_MAXIMUM_LENGTH)" "tx_migrations.version is varchar(191)"
expect_equals "version" "$(sql "SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tx_migrations' AND CONSTRAINT_NAME = 'PRIMARY'")" \
    "tx_migrations has the primary key on version"

expect_equals "" "$(column tx_migrations version "COALESCE(COLUMN_DEFAULT, '')")" \
    "tx_migrations.version has no default, like the Doctrine metadata storage"
expect_exit 0 "migrations:status before the first migration" typo3 migrations:status --no-interaction

expect_exit 0 "migrations:generate" typo3 migrations:generate --no-interaction
expect_equals "1" "$(find "${MIGRATIONS_PATH}" -name 'Version*.php' | wc -l | tr -d ' ')" "generate wrote one migration class"
rm -f "${MIGRATIONS_PATH}"/Version*.php

write_migration Version20200101000000 \
    "\$this->addSql('CREATE TABLE mig_probe (uid INT NOT NULL)'); \$this->write('tables: ' . count(\$this->connection->createSchemaManager()->listTableNames()));" \
    "\$this->addSql('DROP TABLE mig_probe');"

write_migration Version20200101000001 \
    "\$this->addSql('ALTER TABLE mig_probe ADD title VARCHAR(10) DEFAULT NULL'); \$this->addSql('INSERT INTO mig_probe (uid, title) VALUES (1, \\'probe\\')');" \
    "\$this->addSql('ALTER TABLE mig_probe DROP title');"

expect_exit 0 "migrations:migrate runs two schema migrations in one go" typo3 migrations:migrate --no-interaction
expect_equals "mig_probe" "$(sql "SHOW TABLES LIKE 'mig_probe'")" "migration created its table"
expect_equals "probe" "$(sql "SELECT title FROM mig_probe")" "second migration altered the table and wrote a row"
expect_equals "2" "$(sql "SELECT COUNT(*) FROM tx_migrations")" "both migrations recorded in tx_migrations"
expect_exit 0 "migrations:list" typo3 migrations:list --no-interaction
grep -q 'Version20200101000000.*migrated' "${PROJECT_PATH}/last-output.txt" && pass "list shows the migration as migrated" || fail "list shows the migration as migrated"
expect_exit 0 "migrations:latest" typo3 migrations:latest --no-interaction

write_migration Version20200102000000 "\$this->addSql('THIS IS NOT SQL');" ""
expect_exit '!0' "migrations:migrate reports a failing migration" typo3 migrations:migrate --no-interaction
rm -f "${MIGRATIONS_PATH}/Version20200102000000.php"

expect_exit 0 "migrations:execute --down" typo3 migrations:execute --down "${NAMESPACE}\\Version20200101000001" --no-interaction
expect_equals "" "$(column mig_probe title COLUMN_NAME)" "down reverted the schema change"
expect_equals "1" "$(sql "SELECT COUNT(*) FROM tx_migrations")" "down removed the entry"
rm -f "${MIGRATIONS_PATH}/Version20200101000001.php"

# upgrade_from <label> <CREATE TABLE statement for the old tx_migrations> <schema update runs needed>
upgrade_from() {
    echo "Scenario 2$1"

    sql "DROP TABLE IF EXISTS tx_migrations, mig_probe, mig_probe_2"
    sql "$2"
    sql "CREATE TABLE mig_probe (uid INT NOT NULL)"
    sql "INSERT INTO tx_migrations VALUES ('${NAMESPACE//\\/\\\\}\\\\Version20200101000000', '2020-01-01 00:00:00', 5)"

    if [ "$3" = "2" ]; then
        # TYPO3 adds the primary key before it shortens the column, so the first run fails on the
        # key (too long for utf8mb4) but shortens the column; the second run adds the key.
        typo3 database:updateschema '*.add,*.change' --no-interaction > /dev/null 2>&1 || true
    fi
    expect_exit 0 "database:updateschema updates the table" typo3 database:updateschema '*.add,*.change' --no-interaction
    expect_equals "191" "$(column tx_migrations version CHARACTER_MAXIMUM_LENGTH)" "tx_migrations.version shortened to varchar(191)"
    expect_equals "${NAMESPACE}\\Version20200101000000" "$(sql "SELECT version FROM tx_migrations")" "executed migration kept"

    write_migration Version20200103000000 "\$this->addSql('CREATE TABLE mig_probe_2 (uid INT NOT NULL)');" "\$this->addSql('DROP TABLE mig_probe_2');"
    expect_exit 0 "migrations:migrate runs only the new migration" typo3 migrations:migrate --no-interaction
    expect_equals "mig_probe_2" "$(sql "SHOW TABLES LIKE 'mig_probe_2'")" "new migration created its table"
    expect_equals "2" "$(sql "SELECT COUNT(*) FROM tx_migrations")" "both migrations recorded"
    expect_equals "version" "$(sql "SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tx_migrations' AND CONSTRAINT_NAME = 'PRIMARY'")" \
        "tx_migrations has the primary key on version"
    expect_exit 0 "migrations:status" typo3 migrations:status --no-interaction
    rm -f "${MIGRATIONS_PATH}/Version20200103000000.php"
}

upgrade_from "a: varchar(1024) DEFAULT '' without primary key" \
    "CREATE TABLE tx_migrations (version varchar(1024) DEFAULT '' NOT NULL, executed_at datetime DEFAULT NULL, execution_time int DEFAULT NULL)" 2
upgrade_from "b: varchar(1024) with primary key on utf8mb3" \
    "CREATE TABLE tx_migrations (version varchar(1024) NOT NULL, executed_at datetime DEFAULT NULL, execution_time int DEFAULT NULL, PRIMARY KEY (version)) DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci" 1

echo
if [ "${FAILURES}" -gt 0 ]; then
    echo "${FAILURES} check(s) failed"
    exit 1
fi
echo "All checks passed"
