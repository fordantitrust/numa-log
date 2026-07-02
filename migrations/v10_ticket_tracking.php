<?php

declare(strict_types=1);

/**
 * Migration v10: Ticket tracking per event
 *
 * Schema changes:
 *   + type_categories.is_ticket  — flags a type category as representing a ticket purchase
 *   + events.is_free_entry       — flags an event as free entry (no ticket expected)
 *
 * Both default to 0 so existing rows are unaffected until the user opts in via
 * the Manage Types / Events UI.
 *
 * Idempotency: ADD COLUMN is guarded by PRAGMA table_info; wrapped in a
 * transaction.
 */

function runMigrationV10(PDO $pdo): void
{
    autoBackupBeforeMigration(10);

    $pdo->beginTransaction();
    try {
        $typeCols = $pdo->query("PRAGMA table_info(type_categories)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('is_ticket', $typeCols, true)) {
            $pdo->exec('ALTER TABLE type_categories ADD COLUMN is_ticket INTEGER NOT NULL DEFAULT 0');
        }

        $eventCols = $pdo->query("PRAGMA table_info(events)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('is_free_entry', $eventCols, true)) {
            $pdo->exec('ALTER TABLE events ADD COLUMN is_free_entry INTEGER NOT NULL DEFAULT 0');
        }

        setSchemaVersion($pdo, 10);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw new RuntimeException("Migration v10 failed: " . $e->getMessage(), 0, $e);
    }
}
