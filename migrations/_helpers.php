<?php

declare(strict_types=1);

/**
 * Migration helpers — shared utilities used by all migration scripts.
 *
 * Loaded by config.php once per request (require_once guard).
 * Defines:
 *   - getSchemaVersion / setSchemaVersion : version tracking via schema_meta
 *   - autoBackupBeforeMigration            : snapshot DB file before destructive ops
 */

function getSchemaVersion(PDO $pdo): int
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_meta (
        key   TEXT PRIMARY KEY,
        value TEXT
    )");

    $v = $pdo->query("SELECT value FROM schema_meta WHERE key='version'")->fetchColumn();
    if ($v !== false) {
        return (int) $v;
    }

    $hasItems = (bool) $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='items'")->fetchColumn();
    $baseline = $hasItems ? 4 : 0;

    $pdo->prepare("INSERT INTO schema_meta (key, value) VALUES ('version', :v)")
        ->execute([':v' => (string) $baseline]);

    return $baseline;
}

function setSchemaVersion(PDO $pdo, int $v): void
{
    $pdo->prepare("INSERT OR REPLACE INTO schema_meta (key, value) VALUES ('version', :v)")
        ->execute([':v' => (string) $v]);
}

function autoBackupBeforeMigration(int $targetVer): string
{
    if (!is_dir(BACKUP_DIR)) {
        mkdir(BACKUP_DIR, 0755, true);
    }

    // Include milliseconds so back-to-back calls within the same second don't collide.
    $now   = microtime(true);
    $ms    = sprintf('%03d', (int) (($now - floor($now)) * 1000));
    $stamp = date('Ymd_His', (int) $now) . "_{$ms}";
    $backupFile = BACKUP_DIR . "/pre-v{$targetVer}-{$stamp}.sqlite";

    $pdo = getDB();
    $pdo->exec('VACUUM INTO ' . $pdo->quote($backupFile));

    if (!file_exists($backupFile)) {
        throw new RuntimeException("Backup failed before migration to v{$targetVer}: file not created at {$backupFile}");
    }

    return $backupFile;
}
