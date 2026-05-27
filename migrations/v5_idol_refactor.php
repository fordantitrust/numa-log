<?php

declare(strict_types=1);

/**
 * Migration v5: Membership History + ID-Based Reference
 *
 * Schema changes:
 *   + idol_memberships     (time-bounded member-group relationships)
 *   + idol_entities.display_hint  (disambiguate duplicate names)
 *   - UNIQUE constraint on idol_entities.name
 *   + items.idol_id        (FK to idol_entities, replacing name-based join)
 *
 * Backfill:
 *   - One membership row per existing idol_entities.parent_id (for members)
 *   - items.idol_id from items.idol where name → entity is unambiguous
 *
 * Idempotency:
 *   - Wrapped in a transaction; rollback on failure leaves schema_meta unchanged.
 *   - Memberships backfill uses NOT EXISTS guard so partial re-runs do not duplicate.
 *
 * See MEMBERSHIP_HISTORY_PLAN.md for full design.
 */

function runMigrationV5(PDO $pdo): void
{
    autoBackupBeforeMigration(5);

    $pdo->exec('PRAGMA foreign_keys = OFF');
    $pdo->beginTransaction();
    try {
        v5_createMemberships($pdo);
        v5_recreateIdolEntities($pdo);
        v5_addItemsIdolId($pdo);
        v5_backfillMemberships($pdo);
        v5_backfillItemsIdolId($pdo);

        setSchemaVersion($pdo, 5);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        $pdo->exec('PRAGMA foreign_keys = ON');
        throw new RuntimeException("Migration v5 failed: " . $e->getMessage(), 0, $e);
    }

    $pdo->exec('PRAGMA foreign_keys = ON');
}

function v5_createMemberships(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS idol_memberships (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            member_id   INTEGER NOT NULL REFERENCES idol_entities(id) ON DELETE CASCADE,
            group_id    INTEGER NOT NULL REFERENCES idol_entities(id) ON DELETE CASCADE,
            start_date  TEXT,
            end_date    TEXT,
            is_primary  INTEGER NOT NULL DEFAULT 1,
            note        TEXT DEFAULT '',
            created_at  TEXT DEFAULT (datetime('now','localtime'))
        )
    ");
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_mb_member       ON idol_memberships(member_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_mb_group        ON idol_memberships(group_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_mb_member_dates ON idol_memberships(member_id, start_date, end_date)');
}

function v5_recreateIdolEntities(PDO $pdo): void
{
    $cols = $pdo->query("PRAGMA table_info(idol_entities)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (in_array('display_hint', $cols, true)) {
        return;
    }

    $pdo->exec("
        CREATE TABLE idol_entities_new (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            name          TEXT NOT NULL,
            category      TEXT NOT NULL DEFAULT 'member'
                          CHECK(category IN ('company','group','unit','member')),
            parent_id     INTEGER NULL REFERENCES idol_entities_new(id) ON DELETE SET NULL,
            sort_order    INTEGER NOT NULL DEFAULT 0,
            display_hint  TEXT DEFAULT '',
            created_at    TEXT DEFAULT (datetime('now','localtime'))
        )
    ");

    $pdo->exec("
        INSERT INTO idol_entities_new (id, name, category, parent_id, sort_order, display_hint, created_at)
        SELECT id, name, category, parent_id, sort_order, '', created_at
        FROM idol_entities
    ");

    $pdo->exec('DROP TABLE idol_entities');
    $pdo->exec('ALTER TABLE idol_entities_new RENAME TO idol_entities');

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_ie_name      ON idol_entities(name)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_ie_parent_id ON idol_entities(parent_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_ie_category  ON idol_entities(category)');
}

function v5_addItemsIdolId(PDO $pdo): void
{
    $cols = $pdo->query("PRAGMA table_info(items)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('idol_id', $cols, true)) {
        $pdo->exec('ALTER TABLE items ADD COLUMN idol_id INTEGER REFERENCES idol_entities(id) ON DELETE SET NULL');
    }
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_items_idol_id ON items(idol_id)');
}

function v5_backfillMemberships(PDO $pdo): void
{
    $pdo->exec("
        INSERT INTO idol_memberships (member_id, group_id, start_date, end_date, is_primary, note)
        SELECT e.id, e.parent_id, NULL, NULL, 1, 'auto-migrated from parent_id'
        FROM idol_entities e
        WHERE e.category = 'member'
          AND e.parent_id IS NOT NULL
          AND NOT EXISTS (
              SELECT 1 FROM idol_memberships ms
              WHERE ms.member_id = e.id
                AND ms.note = 'auto-migrated from parent_id'
          )
    ");
}

function v5_backfillItemsIdolId(PDO $pdo): void
{
    $pdo->exec("
        UPDATE items
        SET idol_id = (
            SELECT e.id FROM idol_entities e
            WHERE e.name = items.idol AND e.category = 'member'
            GROUP BY e.name
            HAVING COUNT(*) = 1
        )
        WHERE idol_id IS NULL
          AND idol != '' AND idol != '-'
    ");
}
