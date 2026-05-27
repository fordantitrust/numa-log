# Database Migrations

This folder contains one-time database migrations applied automatically when `initDB()` runs.

## How it works

1. `config.php` calls `initDB()` on every request.
2. After creating the baseline tables (CREATE TABLE IF NOT EXISTS), `initDB()` reads the current schema version from `schema_meta`.
3. For each pending version, it `require_once`s the matching `vN_*.php` file and runs the migration function inside a transaction.
4. The version stamp is committed only if the transaction succeeds — partial migrations roll back and re-run on the next request.

`require_once` is cheap when no migration is pending, so this adds near-zero overhead after the database is up-to-date.

## File layout

```
migrations/
├── README.md           ← this file
├── _helpers.php        ← getSchemaVersion, setSchemaVersion, autoBackupBeforeMigration
└── vN_<slug>.php       ← one file per schema version (N = target version)
```

## Version baseline

| Version | App version | Notes |
|---------|-------------|-------|
| 4       | v1.4.x      | Implicit baseline — assigned when `schema_meta` is first created on an existing v1.4 database |
| 5       | v1.5.0      | Membership history + ID-based reference (see `v5_idol_refactor.php`) |

A fresh install starts at version 0; baseline tables are created by `initDB()`, then migrations from v1 onward run in order.

## Safety guarantees

- **Auto-backup** — `autoBackupBeforeMigration()` writes a snapshot to `data/backups/pre-v{N}-{timestamp}.sqlite` using SQLite's `VACUUM INTO` (WAL-safe).
- **Transactional** — All schema changes and backfills run inside one transaction. A failure rolls everything back, including the version stamp.
- **Idempotent** — Each migration function checks before applying (e.g., column existence, `NOT EXISTS` clauses on backfill INSERTs). Re-running after a failed/partial run is safe.
- **Foreign keys** — Disabled during migration (required for SQLite table-recreate pattern), re-enabled afterwards.

## Rollback

If a migration causes problems in production:

1. **Preferred:** restore from the auto-backup file (`data/backups/pre-v{N}-{timestamp}.sqlite`) via the Backup UI or by copying the file over `data/database.sqlite`, then revert the application code.
2. **Manual SQL:** reverse the schema changes and reset `schema_meta.version` to the previous number. See each `vN_*.php` for details.

## Adding a new migration

1. Bump `DB_SCHEMA_VERSION` in `config.php`.
2. Create `migrations/vN_<slug>.php` with a single entry point `runMigrationVN(PDO $pdo)`.
3. Add the `if ($currentVer < N)` branch in `config.php` `initDB()`.
4. Document the changes in `DATABASE_SCHEMA.md` and `CHANGELOG.md`.
5. Add integration tests in `tests/run.php`.
