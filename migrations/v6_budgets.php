<?php

declare(strict_types=1);

/**
 * Migration v6: Budgets / Spending Goals
 *
 * Schema changes:
 *   + budgets  (recurring monthly spending limits, per scope)
 *
 * Scope model:
 *   - scope_type: overall | type | group | company | member
 *   - scope_ref_id   : idol_entities.id for group/company/member (NULL otherwise)
 *   - scope_ref_name : type name for scope_type='type'; label snapshot otherwise
 *
 * Colour thresholds are configurable per budget:
 *   - ok    when pct < warn_pct
 *   - near  when warn_pct <= pct < danger_pct
 *   - over  when pct >= danger_pct
 *
 * Idempotency:
 *   - CREATE TABLE / INDEX IF NOT EXISTS, wrapped in a transaction.
 *   - Adding budgets is additive only — no existing data is touched.
 *
 * See BUDGET_FEATURE_PLAN.md for full design.
 */

function runMigrationV6(PDO $pdo): void
{
    autoBackupBeforeMigration(6);

    $pdo->beginTransaction();
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS budgets (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                scope_type     TEXT NOT NULL DEFAULT 'overall'
                               CHECK(scope_type IN ('overall','type','group','company','member')),
                scope_ref_id   INTEGER NULL,
                scope_ref_name TEXT DEFAULT '',
                amount         REAL NOT NULL DEFAULT 0,
                warn_pct       INTEGER NOT NULL DEFAULT 80,
                danger_pct     INTEGER NOT NULL DEFAULT 100,
                note           TEXT DEFAULT '',
                is_active      INTEGER NOT NULL DEFAULT 1,
                created_at     TEXT DEFAULT (datetime('now','localtime'))
            )
        ");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_budgets_scope ON budgets(scope_type, scope_ref_id)');

        setSchemaVersion($pdo, 6);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw new RuntimeException("Migration v6 failed: " . $e->getMessage(), 0, $e);
    }
}
