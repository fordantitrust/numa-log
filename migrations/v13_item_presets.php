<?php

declare(strict_types=1);

/**
 * Migration v13: Item presets
 *
 * Schema changes:
 *   + item_presets table — form fillers for the item form's Preset dropdown (type +
 *     price, optional title), e.g. "Cheki ฿300", "Deco Cheki = Pin Cheki ฿500".
 *
 * Presets are deliberately NOT linked to items (no FK, no items.preset_id):
 * picking one only fills the form, and the item keeps its own snapshot of
 * type/price/title. Editing or deleting a preset later therefore never moves
 * historical data or report totals. `type` is free text, matched to
 * items.type by name like everything else.
 *
 * Created empty — presets are personal, so no seed data ships to other installs.
 *
 * Idempotency: CREATE TABLE IF NOT EXISTS; wrapped in a transaction.
 */

function runMigrationV13(PDO $pdo): void
{
    autoBackupBeforeMigration(13);

    $pdo->beginTransaction();
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS item_presets (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                name          TEXT NOT NULL,
                type          TEXT NOT NULL,
                price_per_qty REAL NOT NULL,
                title         TEXT NOT NULL DEFAULT '',
                sort_order    INTEGER NOT NULL DEFAULT 0,
                created_at    TEXT DEFAULT (datetime('now','localtime'))
            )
        ");

        setSchemaVersion($pdo, 13);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw new RuntimeException("Migration v13 failed: " . $e->getMessage(), 0, $e);
    }
}
