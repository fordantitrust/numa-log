<?php

/**
 * Numa Log — Migration Unit Tests
 *
 * Standalone tests for the v5 migration. Does NOT use the HTTP server —
 * creates an isolated SQLite database, populates it with v1.4-shape data,
 * runs the migration directly, and asserts on the resulting state.
 *
 * Usage:
 *   php tests/migration_test.php
 *
 * Exit code 0 = all pass, 1 = at least one failure.
 */

declare(strict_types=1);

// ─────────────────────────────────────────────────────────────────────────────
// Setup
// ─────────────────────────────────────────────────────────────────────────────

$testDir   = sys_get_temp_dir() . '/numa_migration_test';
$dbPath    = $testDir . '/test.sqlite';
$backupDir = $testDir . '/backups';

@mkdir($testDir,   0755, true);
@mkdir($backupDir, 0755, true);
if (file_exists($dbPath)) unlink($dbPath);
array_map('unlink', glob($backupDir . '/*.sqlite') ?: []);

define('DB_PATH',    $dbPath);
define('BACKUP_DIR', $backupDir);

// getDB() singleton used by migration helpers (mirrors config.php signature)
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA foreign_keys=ON');
    }
    return $pdo;
}

// ─────────────────────────────────────────────────────────────────────────────
// v1.4 baseline schema + test data
// ─────────────────────────────────────────────────────────────────────────────

function buildV14State(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_date TEXT, event_date TEXT,
            title TEXT NOT NULL, idol TEXT NOT NULL, type TEXT NOT NULL,
            price_per_qty REAL NOT NULL DEFAULT 0, qty INTEGER NOT NULL DEFAULT 1,
            created_at TEXT DEFAULT (datetime('now','localtime')),
            updated_at TEXT DEFAULT (datetime('now','localtime'))
        )
    ");
    $pdo->exec("
        CREATE TABLE idol_entities (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            category TEXT NOT NULL DEFAULT 'member' CHECK(category IN ('company','group','unit','member')),
            parent_id INTEGER NULL REFERENCES idol_entities(id) ON DELETE SET NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT DEFAULT (datetime('now','localtime'))
        )
    ");

    // Hierarchy: Company → Group → Member
    $pdo->exec("INSERT INTO idol_entities (id, name, category, parent_id) VALUES (1, 'JYP', 'company', NULL)");
    $pdo->exec("INSERT INTO idol_entities (id, name, category, parent_id) VALUES (2, 'ITZY', 'group', 1)");
    $pdo->exec("INSERT INTO idol_entities (id, name, category, parent_id) VALUES (3, 'Yuna', 'member', 2)");
    $pdo->exec("INSERT INTO idol_entities (id, name, category, parent_id) VALUES (4, 'Lia', 'member', 2)");
    // Member without parent (should NOT get a membership during backfill)
    $pdo->exec("INSERT INTO idol_entities (id, name, category, parent_id) VALUES (5, 'Orphan', 'member', NULL)");

    $pdo->exec("INSERT INTO items (order_date, title, idol, type, price_per_qty, qty)
                VALUES ('2024-05-01', 'Photocard', 'Yuna', 'Photo', 300, 2)");
    $pdo->exec("INSERT INTO items (order_date, title, idol, type, price_per_qty, qty)
                VALUES ('2024-06-10', 'Album',     'Lia',  'Album', 600, 1)");
    // Item whose idol does not match any entity → idol_id should stay NULL
    $pdo->exec("INSERT INTO items (order_date, title, idol, type, price_per_qty, qty)
                VALUES ('2024-07-15', 'Sticker',   'Unknown Person', 'Misc', 50, 3)");
}

// ─────────────────────────────────────────────────────────────────────────────
// Test runner
// ─────────────────────────────────────────────────────────────────────────────

$PASS = 0;
$FAIL = 0;
$ERRORS = [];

function ok(string $name): void { global $PASS; $PASS++; echo "  \033[32m✓\033[0m {$name}\n"; }
function fail(string $name, string $reason): void {
    global $FAIL, $ERRORS;
    $FAIL++;
    $ERRORS[] = "FAIL [{$name}]: {$reason}";
    echo "  \033[31m✗\033[0m {$name}\n    \033[33m{$reason}\033[0m\n";
}
function section(string $title): void { echo "\n\033[1m{$title}\033[0m\n"; }

function assertEq(string $name, $expected, $actual): void {
    if ($expected === $actual) {
        ok($name);
    } else {
        fail($name, "expected " . var_export($expected, true) . ", got " . var_export($actual, true));
    }
}

function assertTrue(string $name, bool $cond, string $reason = ''): void {
    if ($cond) ok($name); else fail($name, $reason ?: 'condition was false');
}

// ─────────────────────────────────────────────────────────────────────────────
// MAIN
// ─────────────────────────────────────────────────────────────────────────────

echo "\033[1m=== Migration v5 Unit Tests ===\033[0m\n";
echo "  DB: {$dbPath}\n\n";

$pdo = getDB();
buildV14State($pdo);

require_once __DIR__ . '/../migrations/_helpers.php';
require_once __DIR__ . '/../migrations/v5_idol_refactor.php';

// Baseline detection — fresh schema_meta should record v4 (we have items table)
section('1. Baseline & version tracking');
$baseline = getSchemaVersion($pdo);
assertEq('Baseline detected as v4 (items table exists)', 4, $baseline);

// ─────────────────────────────────────────────────────────────────────────────
section('2. Run migration v5');

try {
    runMigrationV5($pdo);
    ok('runMigrationV5 completes without throwing');
} catch (Throwable $e) {
    fail('runMigrationV5 throws', $e->getMessage());
    echo "\n\033[31mAborting — migration failed.\033[0m\n";
    exit(1);
}

assertEq('Schema version stamped to 5', 5, getSchemaVersion($pdo));

// ─────────────────────────────────────────────────────────────────────────────
section('3. Schema changes');

$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
assertTrue('idol_memberships table exists', in_array('idol_memberships', $tables, true));

$entCols = $pdo->query("PRAGMA table_info(idol_entities)")->fetchAll(PDO::FETCH_COLUMN, 1);
assertTrue('idol_entities.display_hint column exists', in_array('display_hint', $entCols, true));

$itemCols = $pdo->query("PRAGMA table_info(items)")->fetchAll(PDO::FETCH_COLUMN, 1);
assertTrue('items.idol_id column exists', in_array('idol_id', $itemCols, true));

// UNIQUE constraint dropped — must be able to insert duplicate name
try {
    $pdo->exec("INSERT INTO idol_entities (name, category) VALUES ('Yuna', 'member')");
    ok('Duplicate name allowed (UNIQUE constraint dropped)');
} catch (Throwable $e) {
    fail('Duplicate name allowed', 'INSERT failed: ' . $e->getMessage());
}

// ─────────────────────────────────────────────────────────────────────────────
section('4. Membership backfill');

$mbCount = (int) $pdo->query("SELECT COUNT(*) FROM idol_memberships")->fetchColumn();
assertEq('2 memberships created (Yuna, Lia — Orphan excluded)', 2, $mbCount);

$mbYuna = $pdo->query("SELECT group_id, start_date, end_date, is_primary FROM idol_memberships WHERE member_id=3")->fetch();
assertTrue('Yuna membership exists', $mbYuna !== false);
if ($mbYuna) {
    assertEq("Yuna membership → group_id=2 (ITZY)", 2, (int) $mbYuna['group_id']);
    assertEq('Yuna membership start_date NULL',     null, $mbYuna['start_date']);
    assertEq('Yuna membership end_date NULL',       null, $mbYuna['end_date']);
    assertEq('Yuna membership is_primary=1',        1, (int) $mbYuna['is_primary']);
}

$orphanMb = (int) $pdo->query("SELECT COUNT(*) FROM idol_memberships WHERE member_id=5")->fetchColumn();
assertEq('Orphan member has no membership', 0, $orphanMb);

// ─────────────────────────────────────────────────────────────────────────────
section('5. Items idol_id backfill');

$item1 = $pdo->query("SELECT idol, idol_id FROM items WHERE title='Photocard'")->fetch();
assertEq("Photocard.idol_id → Yuna's entity id (3)", 3, (int) ($item1['idol_id'] ?? 0));

$item2 = $pdo->query("SELECT idol, idol_id FROM items WHERE title='Album'")->fetch();
assertEq("Album.idol_id → Lia's entity id (4)", 4, (int) ($item2['idol_id'] ?? 0));

$item3 = $pdo->query("SELECT idol, idol_id FROM items WHERE title='Sticker'")->fetch();
assertEq("Sticker.idol_id stays NULL (Unknown Person has no entity)", null, $item3['idol_id']);

// items.idol (text) preserved
$txt = $pdo->query("SELECT idol FROM items WHERE title='Photocard'")->fetchColumn();
assertEq('items.idol (text) preserved as snapshot', 'Yuna', $txt);

// ─────────────────────────────────────────────────────────────────────────────
section('6. Auto-backup created');

$backups = glob($backupDir . '/pre-v5-*.sqlite') ?: [];
assertEq('Exactly 1 auto-backup file created', 1, count($backups));
if (count($backups) === 1) {
    assertTrue('Backup file is non-empty', filesize($backups[0]) > 0);
}

// ─────────────────────────────────────────────────────────────────────────────
section('7. Idempotency — re-run migration');

try {
    runMigrationV5($pdo);
    ok('Second runMigrationV5 does not throw');
} catch (Throwable $e) {
    fail('Idempotent re-run', $e->getMessage());
}

$mbCountAfter = (int) $pdo->query("SELECT COUNT(*) FROM idol_memberships WHERE note='auto-migrated from parent_id'")->fetchColumn();
assertEq('Memberships not duplicated after re-run', 2, $mbCountAfter);

$itemCols2 = $pdo->query("PRAGMA table_info(items)")->fetchAll(PDO::FETCH_COLUMN, 1);
assertEq('items columns unchanged after re-run',
    count($itemCols), count($itemCols2));

// ─────────────────────────────────────────────────────────────────────────────
// SUMMARY
// ─────────────────────────────────────────────────────────────────────────────

$total = $PASS + $FAIL;
echo "\n\033[1m=== Results ===\033[0m\n";
echo "  \033[32mPassed: {$PASS}\033[0m / {$total}\n";

if ($FAIL > 0) {
    echo "  \033[31mFailed: {$FAIL}\033[0m / {$total}\n\n";
    foreach ($ERRORS as $e) {
        echo "  \033[31m•\033[0m {$e}\n";
    }
    echo "\n";
    exit(1);
}

echo "\n  \033[32mAll migration tests passed!\033[0m\n\n";

// Cleanup
unset($pdo);
@unlink($dbPath);
array_map('unlink', glob($backupDir . '/*.sqlite') ?: []);

exit(0);
