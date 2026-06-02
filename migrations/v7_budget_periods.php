<?php

declare(strict_types=1);

/**
 * Migration v7: Per-month budget overrides
 *
 * Schema changes:
 *   + budgets.period  (TEXT, nullable)
 *       - NULL          → recurring default for the scope (applies to every month)
 *       - 'YYYY-MM'     → override amount/thresholds for that scope in that month
 *
 * Effective budget for (scope, month) = the override row if one exists, else the
 * recurring default. Existing rows keep period = NULL, so they become the defaults
 * (no data loss). See BUDGET_FEATURE_PLAN.md.
 *
 * Idempotency: ADD COLUMN is guarded by a PRAGMA check; wrapped in a transaction.
 */

function runMigrationV7(PDO $pdo): void
{
    autoBackupBeforeMigration(7);

    $pdo->beginTransaction();
    try {
        $cols = $pdo->query("PRAGMA table_info(budgets)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('period', $cols, true)) {
            $pdo->exec("ALTER TABLE budgets ADD COLUMN period TEXT NULL");
        }
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_budgets_scope_period ON budgets(scope_type, scope_ref_id, period)');

        setSchemaVersion($pdo, 7);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw new RuntimeException("Migration v7 failed: " . $e->getMessage(), 0, $e);
    }
}
