<?php

declare(strict_types=1);

/**
 * Migration v9: Multi-day events
 *
 * Schema changes:
 *   + events.end_date  — nullable end date for events spanning multiple days
 *
 * events.event_date is the start date; end_date is the (optional) last day.
 * NULL end_date (or end_date == event_date) means a single-day event, so all
 * existing rows remain valid single-day events without any data backfill.
 *
 * Idempotency: ADD COLUMN is guarded by PRAGMA table_info; wrapped in a
 * transaction.
 */

function runMigrationV9(PDO $pdo): void
{
    autoBackupBeforeMigration(9);

    $pdo->beginTransaction();
    try {
        $cols = $pdo->query("PRAGMA table_info(events)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('end_date', $cols, true)) {
            $pdo->exec('ALTER TABLE events ADD COLUMN end_date TEXT');
        }

        setSchemaVersion($pdo, 9);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw new RuntimeException("Migration v9 failed: " . $e->getMessage(), 0, $e);
    }
}
