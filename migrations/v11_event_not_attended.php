<?php

declare(strict_types=1);

/**
 * Migration v11: "Did not attend" flag for events
 *
 * Schema changes:
 *   + events.is_not_attended — flags an event as not physically attended (items were
 *     still ordered for it), excluding it from missing-ticket detection like free entry.
 *
 * Defaults to 0 so existing rows are unaffected. Mutually exclusive with
 * is_free_entry in the UI, but stored as an independent column for consistency
 * with the v10 pattern.
 *
 * Idempotency: ADD COLUMN is guarded by PRAGMA table_info; wrapped in a
 * transaction.
 */

function runMigrationV11(PDO $pdo): void
{
    autoBackupBeforeMigration(11);

    $pdo->beginTransaction();
    try {
        $eventCols = $pdo->query("PRAGMA table_info(events)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('is_not_attended', $eventCols, true)) {
            $pdo->exec('ALTER TABLE events ADD COLUMN is_not_attended INTEGER NOT NULL DEFAULT 0');
        }

        setSchemaVersion($pdo, 11);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw new RuntimeException("Migration v11 failed: " . $e->getMessage(), 0, $e);
    }
}
