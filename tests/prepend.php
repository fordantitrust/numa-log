<?php

/**
 * Test bootstrap — runs before every request via PHP built-in server
 * auto_prepend_file option.
 *
 * Defines DB_PATH and BACKUP_DIR constants with test-specific paths
 * BEFORE config.php gets a chance to define them.  The first call to
 * define() wins in PHP; config.php's duplicate calls will silently fail.
 */

$testDir = sys_get_temp_dir() . '/numa_log_tests';

if (!is_dir($testDir)) {
    mkdir($testDir, 0755, true);
}
if (!is_dir($testDir . '/backups')) {
    mkdir($testDir . '/backups', 0755, true);
}

// Suppress the E_WARNING that fires when config.php tries to redefine these
@define('DB_PATH',    $testDir . '/test.sqlite');
@define('BACKUP_DIR', $testDir . '/backups');
@define('APP_VERSION', 'TEST');
@define('ALLOW_IMPORT', false);
@define('ALLOW_RESEED', false);
@define('AUTH_ENABLED', true);
@define('SESSION_LIFETIME', 86400);
