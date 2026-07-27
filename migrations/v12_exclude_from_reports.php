<?php

declare(strict_types=1);

/**
 * Migration v12: "Exclude from reports" flag for type categories
 *
 * Schema changes:
 *   + type_categories.exclude_from_reports — items of this type are left out of
 *     the normal totals (dashboard, reports, budgets, item list, export) so that
 *     spending the user tracks but does not consider merch — travel costs, gifts
 *     bought for other people, resale stock — stops inflating the KPIs.
 *
 * Nothing is deleted or hidden permanently: every read endpoint accepts
 * include_excluded=1 to switch the exclusion off, and the excluded amount is
 * always surfaced alongside the filtered totals.
 *
 * Defaults to 0 so existing rows are unaffected until the user opts in via the
 * Manage Types UI.
 *
 * Idempotency: ADD COLUMN is guarded by PRAGMA table_info; wrapped in a
 * transaction.
 */

function runMigrationV12(PDO $pdo): void
{
    autoBackupBeforeMigration(12);

    $pdo->beginTransaction();
    try {
        $typeCols = $pdo->query("PRAGMA table_info(type_categories)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('exclude_from_reports', $typeCols, true)) {
            $pdo->exec('ALTER TABLE type_categories ADD COLUMN exclude_from_reports INTEGER NOT NULL DEFAULT 0');
        }

        setSchemaVersion($pdo, 12);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw new RuntimeException("Migration v12 failed: " . $e->getMessage(), 0, $e);
    }
}
