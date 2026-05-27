<?php

declare(strict_types=1);

/**
 * Numa Log — manual migration trigger (CLI only).
 *
 * Usage:
 *   php migrate.php
 *
 * Migrations also run automatically on the first browser request after
 * deploy; this script is for ops/scripted environments where explicit
 * feedback is preferred (e.g., docker exec).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script is CLI only.\n";
    exit(1);
}

require __DIR__ . '/config.php';

$pdo = getDB();
$ver = getSchemaVersion($pdo);
echo "Numa Log schema version: {$ver} (target: " . DB_SCHEMA_VERSION . ")\n";

if ($ver < DB_SCHEMA_VERSION) {
    echo "Note: a pending migration was detected but should have run already via initDB().\n";
    echo "Check error logs if the version did not advance.\n";
    exit(1);
}

echo "Schema is up to date.\n";
exit(0);
