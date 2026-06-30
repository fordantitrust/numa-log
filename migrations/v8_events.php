<?php

declare(strict_types=1);

/**
 * Migration v8: Named events
 *
 * Schema changes:
 *   + events table         — stores named events (concerts, fanmeets, etc.)
 *   + items.event_id       — nullable FK → events(id) ON DELETE SET NULL
 *
 * items.event_date is retained for backward compatibility and as a standalone
 * fallback for items not linked to a named event.
 *
 * Idempotency: ADD COLUMN is guarded by PRAGMA table_info; CREATE TABLE uses
 * IF NOT EXISTS; wrapped in a transaction.
 */

function runMigrationV8(PDO $pdo): void
{
    autoBackupBeforeMigration(8);

    $pdo->beginTransaction();
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS events (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                name        TEXT NOT NULL,
                event_date  TEXT NOT NULL,
                description TEXT DEFAULT '',
                created_at  TEXT DEFAULT (datetime('now','localtime'))
            )
        ");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_events_date ON events(event_date)');

        $cols = $pdo->query("PRAGMA table_info(items)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('event_id', $cols, true)) {
            $pdo->exec('ALTER TABLE items ADD COLUMN event_id INTEGER REFERENCES events(id) ON DELETE SET NULL');
        }
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_items_event_id ON items(event_id)');

        setSchemaVersion($pdo, 8);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw new RuntimeException("Migration v8 failed: " . $e->getMessage(), 0, $e);
    }
}
