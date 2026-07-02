<?php

/**
 * Numa Log — Integration Test Suite
 *
 * Usage:
 *   php tests/run.php
 *
 * Requirements:
 *   - PHP CLI with curl extension
 *   - No external dependencies (no PHPUnit needed)
 *
 * What it does:
 *   1. Creates an isolated test SQLite database in the system temp directory
 *   2. Starts PHP built-in server on a free port with prepend.php overriding DB_PATH
 *   3. Logs in as admin and runs ~50 tests covering all API endpoints
 *   4. Prints a colour-coded summary and exits with code 0 (pass) or 1 (fail)
 */

declare(strict_types=1);

// ─────────────────────────────────────────────────────────────────────────────
// Configuration
// ─────────────────────────────────────────────────────────────────────────────

$PROJECT_DIR  = realpath(__DIR__ . '/..');
$PREPEND_FILE = __DIR__ . '/prepend.php';
$TEST_DIR     = sys_get_temp_dir() . '/numa_log_tests';
$TEST_DB      = $TEST_DIR . '/test.sqlite';
$TEST_BACKUP  = $TEST_DIR . '/backups';
$PORT         = findFreePort(8765);
$BASE_URL     = "http://127.0.0.1:{$PORT}"; // explicit IPv4 to avoid ::1 ambiguity
$COOKIE_FILE  = tempnam(sys_get_temp_dir(), 'numa_cookies_');

// Admin credentials used for tests (strong enough to avoid force-change flow)
$ADMIN_PASS = 'TestAdmin2024!';

// ─────────────────────────────────────────────────────────────────────────────
// Colour helpers
// ─────────────────────────────────────────────────────────────────────────────

function green(string $s): string  { return "\033[32m{$s}\033[0m"; }
function red(string $s): string    { return "\033[31m{$s}\033[0m"; }
function yellow(string $s): string { return "\033[33m{$s}\033[0m"; }
function bold(string $s): string   { return "\033[1m{$s}\033[0m"; }

// ─────────────────────────────────────────────────────────────────────────────
// Test counters
// ─────────────────────────────────────────────────────────────────────────────

$PASS = 0;
$FAIL = 0;
$ERRORS = [];

function pass(string $name): void {
    global $PASS;
    $PASS++;
    echo '  ' . green('✓') . " {$name}\n";
}

function fail(string $name, string $reason): void {
    global $FAIL, $ERRORS;
    $FAIL++;
    $ERRORS[] = "FAIL [{$name}]: {$reason}";
    echo '  ' . red('✗') . " {$name}\n";
    echo '    ' . yellow($reason) . "\n";
}

function section(string $title): void {
    echo "\n" . bold($title) . "\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// Assertions
// ─────────────────────────────────────────────────────────────────────────────

function assertStatus(string $test, array $res, int $expected): bool {
    if ($res['status'] !== $expected) {
        fail($test, "Expected HTTP {$expected}, got {$res['status']}. Body: " . substr($res['body'], 0, 200));
        return false;
    }
    pass($test);
    return true;
}

function assertJson(string $test, array $res, int $expectedStatus, callable $check): bool {
    if ($res['status'] !== $expectedStatus) {
        fail($test, "Expected HTTP {$expectedStatus}, got {$res['status']}. Body: " . substr($res['body'], 0, 200));
        return false;
    }
    $data = json_decode($res['body'], true);
    if ($data === null) {
        fail($test, "Invalid JSON: " . substr($res['body'], 0, 200));
        return false;
    }
    $reason = $check($data);
    if ($reason !== null) {
        fail($test, $reason);
        return false;
    }
    pass($test);
    return true;
}

// ─────────────────────────────────────────────────────────────────────────────
// HTTP helpers
// ─────────────────────────────────────────────────────────────────────────────

function httpGet(string $url, string $cookieFile, array $headers = []): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => array_merge(['Accept: application/json'], $headers),
    ]);
    $response   = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $status     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error      = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['status' => 0, 'headers' => '', 'body' => $error];
    }
    return [
        'status'  => $status,
        'headers' => substr($response, 0, $headerSize),
        'body'    => substr($response, $headerSize),
    ];
}

function httpPost(string $url, array $data, string $cookieFile, array $headers = []): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => array_merge([
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ], $headers),
    ]);
    $response   = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $status     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error      = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['status' => 0, 'headers' => '', 'body' => $error];
    }
    return [
        'status'  => $status,
        'headers' => substr($response, 0, $headerSize),
        'body'    => substr($response, $headerSize),
    ];
}

function apiGet(string $base, string $action, array $params, string $cookieFile): array {
    $qs  = http_build_query(array_merge(['action' => $action], $params));
    $url = "{$base}/api.php?{$qs}";
    return httpGet($url, $cookieFile);
}

function apiPost(string $base, string $action, array $data, string $csrf, string $cookieFile): array {
    $url  = "{$base}/api.php?action={$action}";
    $data = array_merge(['csrf_token' => $csrf], $data);
    return httpPost($url, $data, $cookieFile);
}

function usersApiGet(string $base, string $action, array $params, string $cookieFile): array {
    $qs  = http_build_query(array_merge(['action' => $action], $params));
    $url = "{$base}/api_users.php?{$qs}";
    return httpGet($url, $cookieFile);
}

function usersApiPost(string $base, string $action, array $data, string $csrf, string $cookieFile): array {
    $url  = "{$base}/api_users.php?action={$action}";
    $data = array_merge(['csrf_token' => $csrf], $data);
    return httpPost($url, $data, $cookieFile);
}

function extractCsrfToken(string $html): string {
    if (preg_match('/name="csrf_token"\s+value="([a-f0-9]+)"/', $html, $m)) {
        return $m[1];
    }
    if (preg_match('/value="([a-f0-9]+)"\s+name="csrf_token"/', $html, $m)) {
        return $m[1];
    }
    return '';
}

// ─────────────────────────────────────────────────────────────────────────────
// Port helper
// ─────────────────────────────────────────────────────────────────────────────

function findFreePort(int $start = 8765): int {
    for ($port = $start; $port < $start + 100; $port++) {
        // Check both IPv4 and IPv6 — Windows resolves "localhost" to ::1
        $ipv4 = @fsockopen('127.0.0.1', $port, $e, $s, 0.2);
        $ipv6 = @fsockopen('[::1]',     $port, $e, $s, 0.2);
        if (!$ipv4 && !$ipv6) {
            return $port;
        }
        if ($ipv4) fclose($ipv4);
        if ($ipv6) fclose($ipv6);
    }
    return $start;
}

// ─────────────────────────────────────────────────────────────────────────────
// Server management
// ─────────────────────────────────────────────────────────────────────────────

function startServer(string $projectDir, int $port, string $prependFile): mixed {
    // display_errors=Off keeps constant-redefinition warnings out of HTTP bodies
    $cmd = sprintf(
        'php -S 127.0.0.1:%d -t %s -d auto_prepend_file=%s -d display_errors=Off -d log_errors=On',
        $port,
        escapeshellarg($projectDir),
        escapeshellarg($prependFile)
    );

    // Redirect stdout/stderr to a log file to prevent pipe-buffer deadlocks.
    // Using pipes causes the server to block once the OS buffer fills.
    // stdin must be a readable pipe (not NUL) so the server can accept sockets.
    $logFile = sys_get_temp_dir() . '/numa_test_server.log';

    $descriptors = [
        0 => ['pipe', 'r'],           // stdin — pipe kept open so server runs
        1 => ['file', $logFile, 'w'], // stdout → log file
        2 => ['file', $logFile, 'a'], // stderr → log file
    ];

    $proc = proc_open($cmd, $descriptors, $pipes);

    if (!is_resource($proc)) {
        echo red("Failed to start PHP server.\n");
        exit(1);
    }

    // Do NOT close $pipes[0] — the built-in server needs stdin to remain open.

    // Wait up to 3 s for the server to become ready
    $baseUrl = "http://127.0.0.1:{$port}";
    $waited  = 0;
    while ($waited < 3000) {
        usleep(100_000); // 100 ms
        $waited += 100;
        $ch = curl_init("{$baseUrl}/login.php");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 1]);
        $res = curl_exec($ch);
        curl_close($ch);
        if ($res !== false) {
            break;
        }
    }

    return $proc;
}

function stopServer(mixed $proc): void {
    if (is_resource($proc)) {
        proc_terminate($proc);
        proc_close($proc);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Test database setup
// ─────────────────────────────────────────────────────────────────────────────

function setupTestDatabase(string $testDir, string $testDb, string $testBackup, string $adminPass): void {
    // Remove old test DB if present
    if (file_exists($testDb)) {
        unlink($testDb);
    }

    if (!is_dir($testDir)) {
        mkdir($testDir, 0755, true);
    }
    if (!is_dir($testBackup)) {
        mkdir($testBackup, 0755, true);
    }

    $pdo = new PDO("sqlite:{$testDb}", null, null, [
        PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode=WAL');
    $pdo->exec('PRAGMA foreign_keys=ON');

    // Schema (mirrors config.php initDB)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_date TEXT,
            event_date TEXT,
            title TEXT NOT NULL,
            idol TEXT NOT NULL,
            type TEXT NOT NULL,
            price_per_qty REAL NOT NULL DEFAULT 0,
            qty INTEGER NOT NULL DEFAULT 1,
            created_at TEXT DEFAULT (datetime('now','localtime')),
            updated_at TEXT DEFAULT (datetime('now','localtime'))
        )
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS type_categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            description TEXT DEFAULT '',
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT DEFAULT (datetime('now','localtime'))
        )
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            display_name TEXT NOT NULL DEFAULT '',
            role TEXT NOT NULL DEFAULT 'user' CHECK(role IN ('admin','user')),
            created_at TEXT DEFAULT (datetime('now','localtime')),
            last_login TEXT
        )
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS idol_entities (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            category TEXT NOT NULL DEFAULT 'member' CHECK(category IN ('company','group','unit','member')),
            parent_id INTEGER NULL REFERENCES idol_entities(id) ON DELETE SET NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT DEFAULT (datetime('now','localtime'))
        )
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip TEXT NOT NULL,
            attempted_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
        )
    ");

    // Seed admin with a strong password to bypass the force-change flow
    $hash = password_hash($adminPass, PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (username, password, display_name, role) VALUES ('admin', :pw, 'Administrator', 'admin')")
        ->execute([':pw' => $hash]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Auth helpers
// ─────────────────────────────────────────────────────────────────────────────

function loginAdmin(string $baseUrl, string $cookieFile, string $adminPass): string {
    // Step 1: GET login page to obtain CSRF token
    $loginPage  = httpGet("{$baseUrl}/login.php", $cookieFile);
    $csrfToken  = extractCsrfToken($loginPage['body']);

    if ($csrfToken === '') {
        echo red("Could not extract CSRF token from login page.\n");
        exit(1);
    }

    // Step 2: POST credentials
    $res = httpPost("{$baseUrl}/login.php", [
        'username'   => 'admin',
        'password'   => $adminPass,
        'csrf_token' => $csrfToken,
    ], $cookieFile);

    if ($res['status'] !== 302) {
        echo red("Login failed (expected 302, got {$res['status']}).\n");
        echo $res['body'] . "\n";
        exit(1);
    }

    // The CSRF token persists in the session after login — reuse it for API calls
    return $csrfToken;
}

// ─────────────────────────────────────────────────────────────────────────────
// Cleanup
// ─────────────────────────────────────────────────────────────────────────────

function cleanup(mixed $proc, string $cookieFile, string $testDir): void {
    stopServer($proc);

    if (file_exists($cookieFile)) {
        unlink($cookieFile);
    }

    // Remove test database and backups
    array_map('unlink', glob("{$testDir}/backups/*.sqlite") ?: []);
    if (file_exists("{$testDir}/test.sqlite")) {
        unlink("{$testDir}/test.sqlite");
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// MAIN — setup
// ─────────────────────────────────────────────────────────────────────────────

echo bold("\n=== Numa Log Integration Tests ===\n");
echo "  Project : {$PROJECT_DIR}\n";
echo "  Base URL: {$BASE_URL}\n";
echo "  Test DB : {$TEST_DB}\n\n";

setupTestDatabase($TEST_DIR, $TEST_DB, $TEST_BACKUP, $ADMIN_PASS);

echo "Starting PHP built-in server on port {$PORT}...";
$serverProc = startServer($PROJECT_DIR, $PORT, $PREPEND_FILE);
echo green(" OK\n");

// Register cleanup on script exit
register_shutdown_function(function () use (&$serverProc, $COOKIE_FILE, $TEST_DIR) {
    cleanup($serverProc, $COOKIE_FILE, $TEST_DIR);
});

// Login
echo "Logging in as admin...";
$CSRF = loginAdmin($BASE_URL, $COOKIE_FILE, $ADMIN_PASS);
echo green(" OK\n\n");

// ─────────────────────────────────────────────────────────────────────────────
// TEST SUITE 0 — Schema Migration (v5)
//
// setupTestDatabase() creates v1.4 baseline; initDB() on first request runs
// the v5 migration. We open the same SQLite file directly to verify state.
// ─────────────────────────────────────────────────────────────────────────────

section('0. Schema Migration');

$migPdo = new PDO("sqlite:{$TEST_DB}", null, null, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// 0a. schema_meta.version matches DB_SCHEMA_VERSION in config.php
// Parsed from source (not included) to avoid config.php side effects in this CLI.
$configSrc = file_get_contents("{$PROJECT_DIR}/config.php");
$expectedVer = preg_match('/DB_SCHEMA_VERSION\s*=\s*(\d+)/', $configSrc ?: '', $m) ? (int) $m[1] : 0;
$ver = (int) $migPdo->query("SELECT value FROM schema_meta WHERE key='version'")->fetchColumn();
if ($ver === $expectedVer) pass("schema_meta.version = {$expectedVer}");
else                       fail("schema_meta.version = {$expectedVer}", "got {$ver}");

// 0b. idol_memberships table exists with expected columns
$mbCols = $migPdo->query("PRAGMA table_info(idol_memberships)")->fetchAll(PDO::FETCH_COLUMN, 1);
$expectedMb = ['id','member_id','group_id','start_date','end_date','is_primary','note','created_at'];
$missing = array_diff($expectedMb, $mbCols);
if (empty($missing)) pass('idol_memberships has all expected columns');
else                 fail('idol_memberships has all expected columns', 'missing: ' . implode(',', $missing));

// 0c. idol_entities.display_hint column exists
$ieCols = $migPdo->query("PRAGMA table_info(idol_entities)")->fetchAll(PDO::FETCH_COLUMN, 1);
if (in_array('display_hint', $ieCols, true)) pass('idol_entities.display_hint column present');
else                                          fail('idol_entities.display_hint column present', 'column missing');

// 0d. items.idol_id column exists
$itCols = $migPdo->query("PRAGMA table_info(items)")->fetchAll(PDO::FETCH_COLUMN, 1);
if (in_array('idol_id', $itCols, true)) pass('items.idol_id column present');
else                                     fail('items.idol_id column present', 'column missing');

// 0e. UNIQUE constraint on idol_entities.name dropped (test via direct INSERT)
try {
    $migPdo->exec("INSERT INTO idol_entities (name, category) VALUES ('__dup_test__', 'member')");
    $migPdo->exec("INSERT INTO idol_entities (name, category) VALUES ('__dup_test__', 'member')");
    $migPdo->exec("DELETE FROM idol_entities WHERE name='__dup_test__'");
    pass('UNIQUE on idol_entities.name dropped (duplicate insert allowed)');
} catch (Throwable $e) {
    fail('UNIQUE on idol_entities.name dropped', 'INSERT raised: ' . $e->getMessage());
}

// 0f. Auto-backup file present
$backupFiles = glob($TEST_BACKUP . '/pre-v5-*.sqlite') ?: [];
if (count($backupFiles) >= 1) pass('Auto-backup file created (pre-v5-*.sqlite)');
else                          fail('Auto-backup file created',  'no pre-v5 backup found in ' . $TEST_BACKUP);

unset($migPdo);

// ─────────────────────────────────────────────────────────────────────────────
// TEST SUITE 1 — Authentication
// ─────────────────────────────────────────────────────────────────────────────

section('1. Authentication');

// 1a. Unauthenticated API request returns 401
$fresh = tempnam(sys_get_temp_dir(), 'numa_anon_');
$res = httpGet("{$BASE_URL}/api.php?action=list", $fresh);
assertJson('Unauthenticated API → 401', $res, 401, function ($d) {
    return isset($d['error']) ? null : 'Expected error field';
});
unlink($fresh);

// 1b. Login page renders (use a fresh anonymous cookie — logged-in session redirects to index)
$anonCookie = tempnam(sys_get_temp_dir(), 'numa_anon2_');
$res = httpGet("{$BASE_URL}/login.php", $anonCookie);
unlink($anonCookie);
assertStatus('Login page renders', $res, 200);

// 1c. Wrong password stays on login page
$freshCookie = tempnam(sys_get_temp_dir(), 'numa_bad_');
$loginPage = httpGet("{$BASE_URL}/login.php", $freshCookie);
$badCsrf   = extractCsrfToken($loginPage['body']);
$res = httpPost("{$BASE_URL}/login.php", [
    'username'   => 'admin',
    'password'   => 'wrongpassword',
    'csrf_token' => $badCsrf,
], $freshCookie);
assertStatus('Wrong password → 200 (stays on login)', $res, 200);
unlink($freshCookie);

// 1d. Missing CSRF token → 403
$noCsrfCookie = tempnam(sys_get_temp_dir(), 'numa_nocsrf_');
$res = httpPost("{$BASE_URL}/api.php?action=create", ['title' => 'x', 'idol' => 'y', 'type' => 'z', 'price_per_qty' => 0, 'qty' => 1], $COOKIE_FILE);
assertStatus('Missing CSRF → 403', $res, 403);
unlink($noCsrfCookie);

// ─────────────────────────────────────────────────────────────────────────────
// TEST SUITE 2 — Items CRUD
// ─────────────────────────────────────────────────────────────────────────────

section('2. Items CRUD');

// 2a. List items — empty
assertJson('List items (empty)', apiGet($BASE_URL, 'list', [], $COOKIE_FILE), 200, function ($d) {
    if (!isset($d['data'], $d['total'])) return 'Missing data/total fields';
    if ($d['total'] !== 0)              return "Expected 0 items, got {$d['total']}";
    return null;
});

// 2b. Create item
$res = apiPost($BASE_URL, 'create', [
    'order_date'    => '2024-01-15',
    'event_date'    => '2024-02-01',
    'title'         => 'Test Photo Card',
    'idol'          => 'Member A',
    'type'          => 'Photo',
    'price_per_qty' => 350,
    'qty'           => 2,
], $CSRF, $COOKIE_FILE);
$createdId = null;
assertJson('Create item', $res, 200, function ($d) use (&$createdId) {
    if (empty($d['success']))  return 'Expected success=true';
    if (!isset($d['id']))      return 'Missing id in response';
    $createdId = (int) $d['id'];
    return null;
});

// 2c. Get item by ID
assertJson('Get item by ID', apiGet($BASE_URL, 'get', ['id' => $createdId], $COOKIE_FILE), 200, function ($d) use ($createdId) {
    if (!isset($d['data']))              return 'Missing data field';
    if ((int)$d['data']['id'] !== $createdId) return 'ID mismatch';
    if ($d['data']['title'] !== 'Test Photo Card') return 'Title mismatch';
    if ((float)$d['data']['price_per_qty'] !== 350.0) return 'Price mismatch';
    return null;
});

// 2d. Get non-existent item → 404
assertJson('Get missing item → 404', apiGet($BASE_URL, 'get', ['id' => 99999], $COOKIE_FILE), 404, function ($d) {
    return isset($d['error']) ? null : 'Expected error field';
});

// 2e. Update item
assertJson('Update item', apiPost($BASE_URL, 'update', [
    'id'            => $createdId,
    'order_date'    => '2024-01-20',
    'event_date'    => '',
    'title'         => 'Updated Photo Card',
    'idol'          => 'Member A',
    'type'          => 'Photo',
    'price_per_qty' => 400,
    'qty'           => 3,
], $CSRF, $COOKIE_FILE), 200, function ($d) {
    return empty($d['success']) ? 'Expected success=true' : null;
});

// Confirm update was persisted
assertJson('Updated fields persisted', apiGet($BASE_URL, 'get', ['id' => $createdId], $COOKIE_FILE), 200, function ($d) {
    if ($d['data']['title'] !== 'Updated Photo Card') return "Title was '{$d['data']['title']}'";
    if ((float)$d['data']['price_per_qty'] !== 400.0) return 'Price not updated';
    return null;
});

// 2f. Update with missing ID → 400
assertJson('Update without ID → 400', apiPost($BASE_URL, 'update', [
    'title' => 'No ID', 'idol' => 'x', 'type' => 'y', 'price_per_qty' => 0, 'qty' => 1,
], $CSRF, $COOKIE_FILE), 400, function ($d) {
    return isset($d['error']) ? null : 'Expected error field';
});

// 2g. Create more items for filter/report tests
apiPost($BASE_URL, 'create', [
    'order_date' => '2024-02-10', 'event_date' => '',
    'title' => 'Concert Ticket', 'idol' => 'Member B',
    'type' => 'Ticket', 'price_per_qty' => 1500, 'qty' => 1,
], $CSRF, $COOKIE_FILE);
apiPost($BASE_URL, 'create', [
    'order_date' => '2024-02-15', 'event_date' => '',
    'title' => 'Photobook Vol.1', 'idol' => 'Member A',
    'type' => 'Photobook', 'price_per_qty' => 800, 'qty' => 1,
], $CSRF, $COOKIE_FILE);

// 2h. List items shows correct total
assertJson('List items (3 items)', apiGet($BASE_URL, 'list', [], $COOKIE_FILE), 200, function ($d) {
    if ($d['total'] !== 3) return "Expected 3 items, got {$d['total']}";
    return null;
});

// 2i. List items with idol filter
assertJson('Filter by idol=Member A', apiGet($BASE_URL, 'list', ['idol' => ['Member A']], $COOKIE_FILE), 200, function ($d) {
    if ($d['total'] !== 2) return "Expected 2 items for Member A, got {$d['total']}";
    return null;
});

// 2j. List items with type filter
assertJson('Filter by type=Ticket', apiGet($BASE_URL, 'list', ['type' => ['Ticket']], $COOKIE_FILE), 200, function ($d) {
    if ($d['total'] !== 1) return "Expected 1 Ticket, got {$d['total']}";
    return null;
});

// 2k. List items with search filter
assertJson('Search "Concert"', apiGet($BASE_URL, 'list', ['search' => 'Concert'], $COOKIE_FILE), 200, function ($d) {
    if ($d['total'] !== 1) return "Expected 1 match, got {$d['total']}";
    return null;
});

// 2l. Date range filter
assertJson('Date range filter', apiGet($BASE_URL, 'list', ['date_from' => '2024-02-01', 'date_to' => '2024-02-28'], $COOKIE_FILE), 200, function ($d) {
    if ($d['total'] !== 2) return "Expected 2 in Feb 2024, got {$d['total']}";
    return null;
});

// 2m. Invalid date_from → 400
assertJson('Invalid date_from → 400', apiGet($BASE_URL, 'list', ['date_from' => 'not-a-date'], $COOKIE_FILE), 400, function ($d) {
    return isset($d['error']) ? null : 'Expected error field';
});

// 2n. Pagination
assertJson('Pagination per_page=2', apiGet($BASE_URL, 'list', ['per_page' => 2], $COOKIE_FILE), 200, function ($d) {
    if (count($d['data']) !== 2)    return "Expected 2 items on page, got " . count($d['data']);
    if ($d['total_pages'] !== 2)    return "Expected 2 pages, got {$d['total_pages']}";
    return null;
});

// 2o. Summary totals
assertJson('Summary totals', apiGet($BASE_URL, 'list', [], $COOKIE_FILE), 200, function ($d) {
    if (!isset($d['summary']['total_price'])) return 'Missing summary.total_price';
    if (!isset($d['summary']['total_qty']))   return 'Missing summary.total_qty';
    // Updated item: 400*3=1200, Ticket: 1500*1=1500, Photobook: 800*1=800 → 3500
    if ((float)$d['summary']['total_price'] !== 3500.0) return "Expected total_price=3500, got {$d['summary']['total_price']}";
    return null;
});

// 2p. Filters endpoint
assertJson('Filters endpoint', apiGet($BASE_URL, 'filters', [], $COOKIE_FILE), 200, function ($d) {
    if (!isset($d['idols'], $d['types'])) return 'Missing idols/types fields';
    if (!in_array('Member A', $d['idols'])) return 'Member A not in idols';
    if (!in_array('Photo', $d['types']))    return 'Photo not in types';
    return null;
});

// 2q. Sort by price descending
assertJson('Sort by price desc', apiGet($BASE_URL, 'list', ['sort' => 'price_per_qty', 'dir' => 'desc'], $COOKIE_FILE), 200, function ($d) {
    $prices = array_column($d['data'], 'price_per_qty');
    for ($i = 1; $i < count($prices); $i++) {
        if ((float)$prices[$i] > (float)$prices[$i - 1]) {
            return 'Items not sorted descending by price';
        }
    }
    return null;
});

// 2r. Delete item
$res4 = apiPost($BASE_URL, 'create', [
    'order_date' => '2024-03-01', 'event_date' => '',
    'title' => 'To Delete', 'idol' => 'Member C',
    'type' => 'Merch', 'price_per_qty' => 100, 'qty' => 1,
], $CSRF, $COOKIE_FILE);
$toDeleteId = (int)(json_decode($res4['body'], true)['id'] ?? 0);

assertJson('Delete item', apiPost($BASE_URL, 'delete', ['id' => $toDeleteId], $CSRF, $COOKIE_FILE), 200, function ($d) {
    return empty($d['success']) ? 'Expected success=true' : null;
});

// Verify deleted
assertJson('Deleted item → 404', apiGet($BASE_URL, 'get', ['id' => $toDeleteId], $COOKIE_FILE), 404, function ($d) {
    return isset($d['error']) ? null : 'Expected error field';
});

// 2s. Delete without ID → 400
assertJson('Delete without ID → 400', apiPost($BASE_URL, 'delete', [], $CSRF, $COOKIE_FILE), 400, function ($d) {
    return isset($d['error']) ? null : 'Expected error field';
});

// ─────────────────────────────────────────────────────────────────────────────
// TEST SUITE 3 — Reports
// ─────────────────────────────────────────────────────────────────────────────

section('3. Reports');

// 3a. Monthly report
assertJson('report_monthly', apiGet($BASE_URL, 'report_monthly', [], $COOKIE_FILE), 200, function ($d) {
    if (!isset($d['data'])) return 'Missing data field';
    if (!is_array($d['data'])) return 'data is not array';
    // Should have entries for 2024-01 and 2024-02
    $months = array_column($d['data'], 'month');
    if (!in_array('2024-01', $months)) return '2024-01 not in monthly report';
    if (!in_array('2024-02', $months)) return '2024-02 not in monthly report';
    return null;
});

// 3b. Daily report
assertJson('report_daily for 2024-02', apiGet($BASE_URL, 'report_daily', ['month' => '2024-02'], $COOKIE_FILE), 200, function ($d) {
    if (!isset($d['data'], $d['months'], $d['by_type'], $d['by_idol'])) {
        return 'Missing required fields in daily report';
    }
    return null;
});

// 3c. Daily report without month → 400
assertJson('report_daily missing month → 400', apiGet($BASE_URL, 'report_daily', [], $COOKIE_FILE), 400, function ($d) {
    return isset($d['error']) ? null : 'Expected error field';
});

// 3d. Idol report
assertJson('report_idol', apiGet($BASE_URL, 'report_idol', [], $COOKIE_FILE), 200, function ($d) {
    return isset($d['data']) ? null : 'Missing data field';
});

// 3e. Type report
assertJson('report_type', apiGet($BASE_URL, 'report_type', [], $COOKIE_FILE), 200, function ($d) {
    return isset($d['data']) ? null : 'Missing data field';
});

// 3f. Idol detail report
assertJson('report_idol_detail for Member A', apiGet($BASE_URL, 'report_idol_detail', ['idol' => 'Member A'], $COOKIE_FILE), 200, function ($d) {
    if (!isset($d['by_type'], $d['by_month'])) return 'Missing by_type or by_month';
    return null;
});

// 3g. Type detail report
assertJson('report_type_detail for Photo', apiGet($BASE_URL, 'report_type_detail', ['type' => 'Photo'], $COOKIE_FILE), 200, function ($d) {
    if (!isset($d['members'], $d['by_month'])) return 'Missing members or by_month';
    return null;
});

// 3h. Group report
assertJson('report_by_group', apiGet($BASE_URL, 'report_by_group', [], $COOKIE_FILE), 200, function ($d) {
    return isset($d['data']) ? null : 'Missing data field';
});

// 3i. Company report
assertJson('report_by_company', apiGet($BASE_URL, 'report_by_company', [], $COOKIE_FILE), 200, function ($d) {
    return isset($d['data']) ? null : 'Missing data field';
});

// 3j. Budget analytics (Insights) — overall scope, default range
assertJson('budget_analytics overall', apiGet($BASE_URL, 'budget_analytics', ['scope_type' => 'overall', 'months' => 12], $COOKIE_FILE), 200, function ($d) {
    foreach (['scope', 'months', 'scopes', 'summary', 'recommendations'] as $k) {
        if (!isset($d[$k])) return "Missing {$k} field";
    }
    if (!is_array($d['months']) || count($d['months']) !== 12) return 'Expected 12 month entries';
    $first = $d['months'][0];
    foreach (['month', 'budget', 'spent', 'pct', 'status', 'has_budget', 'over'] as $k) {
        if (!array_key_exists($k, $first)) return "Month entry missing {$k}";
    }
    return null;
});

// ─────────────────────────────────────────────────────────────────────────────
// TEST SUITE 4 — Idol Entities
// ─────────────────────────────────────────────────────────────────────────────

section('4. Idol Entities');

// 4a. Empty tree
assertJson('idol_entities_tree (empty)', apiGet($BASE_URL, 'idol_entities_tree', [], $COOKIE_FILE), 200, function ($d) {
    if (!isset($d['entities'], $d['parents'])) return 'Missing entities/parents fields';
    if (count($d['entities']) !== 0) return 'Expected empty entities';
    return null;
});

// 4b. Create company
$res = apiPost($BASE_URL, 'idol_entity_save', [
    'name' => 'Test Company', 'category' => 'company', 'parent_id' => '', 'sort_order' => 1,
], $CSRF, $COOKIE_FILE);
$companyId = null;
assertJson('Create company entity', $res, 200, function ($d) use (&$companyId) {
    if (empty($d['success'])) return 'Expected success=true';
    $companyId = (int)$d['id'];
    return null;
});

// 4c. Create group under company
$res = apiPost($BASE_URL, 'idol_entity_save', [
    'name' => 'Test Group', 'category' => 'group', 'parent_id' => $companyId, 'sort_order' => 1,
], $CSRF, $COOKIE_FILE);
$groupId = null;
assertJson('Create group entity', $res, 200, function ($d) use (&$groupId) {
    if (empty($d['success'])) return 'Expected success=true';
    $groupId = (int)$d['id'];
    return null;
});

// 4d. Create member under group
$res = apiPost($BASE_URL, 'idol_entity_save', [
    'name' => 'Member A', 'category' => 'member', 'parent_id' => $groupId, 'sort_order' => 1,
], $CSRF, $COOKIE_FILE);
$memberId = null;
assertJson('Create member entity', $res, 200, function ($d) use (&$memberId) {
    if (empty($d['success'])) return 'Expected success=true';
    $memberId = (int)$d['id'];
    return null;
});

// 4e. Tree now has 3 entities
assertJson('idol_entities_tree has 3 entities', apiGet($BASE_URL, 'idol_entities_tree', [], $COOKIE_FILE), 200, function ($d) {
    if (count($d['entities']) !== 3) return "Expected 3 entities, got " . count($d['entities']);
    return null;
});

// 4f. Duplicate name allowed (v5 schema dropped UNIQUE on idol_entities.name)
// Phase-2 will add validation requiring display_hint when name collides;
// for now the duplicate insert simply succeeds.
assertJson('Duplicate entity name allowed (post-v5)', apiPost($BASE_URL, 'idol_entity_save', [
    'name' => 'Test Company', 'category' => 'company', 'parent_id' => '', 'sort_order' => 0,
], $CSRF, $COOKIE_FILE), 200, function ($d) {
    if (empty($d['success'])) return 'Expected success=true';
    if (empty($d['id']))      return 'Missing id in response';
    return null;
});

// 4g. Update entity
assertJson('Update entity', apiPost($BASE_URL, 'idol_entity_save', [
    'id' => $companyId, 'name' => 'Test Company Updated', 'category' => 'company', 'parent_id' => '', 'sort_order' => 2,
], $CSRF, $COOKIE_FILE), 200, function ($d) {
    return empty($d['success']) ? 'Expected success=true' : null;
});

// 4h. Delete member entity
assertJson('Delete member entity', apiPost($BASE_URL, 'idol_entity_delete', ['id' => $memberId], $CSRF, $COOKIE_FILE), 200, function ($d) {
    return empty($d['success']) ? 'Expected success=true' : null;
});

// 4i. Delete without ID → error
assertJson('Delete entity without ID → error', apiPost($BASE_URL, 'idol_entity_delete', [], $CSRF, $COOKIE_FILE), 400, function ($d) {
    return isset($d['error']) ? null : 'Expected error field';
});

// ─────────────────────────────────────────────────────────────────────────────
// TEST SUITE 5 — Type Categories
// ─────────────────────────────────────────────────────────────────────────────

section('5. Type Categories');

// 5a. List types (empty)
assertJson('type_list (empty)', apiGet($BASE_URL, 'type_list', [], $COOKIE_FILE), 200, function ($d) {
    if (!isset($d['types'], $d['unmapped'])) return 'Missing types/unmapped fields';
    // Items exist with types Photo, Ticket, Photobook — all should be in unmapped
    if (!in_array('Photo', $d['unmapped'])) return 'Photo not in unmapped';
    return null;
});

// 5b. Create type category
$res = apiPost($BASE_URL, 'type_save', [
    'name' => 'Photo', 'description' => 'Photo cards and sets', 'sort_order' => 1,
], $CSRF, $COOKIE_FILE);
$typeId = null;
assertJson('Create type category', $res, 200, function ($d) use (&$typeId) {
    if (empty($d['success'])) return 'Expected success=true';
    $typeId = (int)$d['id'];
    return null;
});

// 5c. Create another type
apiPost($BASE_URL, 'type_save', [
    'name' => 'Ticket', 'description' => 'Concert tickets', 'sort_order' => 2,
], $CSRF, $COOKIE_FILE);

// 5d. List types now has 2, Photo no longer unmapped
assertJson('type_list has 2 categories', apiGet($BASE_URL, 'type_list', [], $COOKIE_FILE), 200, function ($d) {
    if (count($d['types']) !== 2) return "Expected 2 types, got " . count($d['types']);
    if (in_array('Photo', $d['unmapped'])) return 'Photo should not be unmapped now';
    return null;
});

// 5e. type_members_report
assertJson('type_members_report', apiGet($BASE_URL, 'type_members_report', [], $COOKIE_FILE), 200, function ($d) {
    return isset($d['by_type']) ? null : 'Missing by_type field';
});

// 5f. Update type category
assertJson('Update type category', apiPost($BASE_URL, 'type_save', [
    'id' => $typeId, 'name' => 'Photo', 'description' => 'Updated description', 'sort_order' => 10,
], $CSRF, $COOKIE_FILE), 200, function ($d) {
    return empty($d['success']) ? 'Expected success=true' : null;
});

// 5g. Delete type category
assertJson('Delete type category', apiPost($BASE_URL, 'type_delete', ['id' => $typeId], $CSRF, $COOKIE_FILE), 200, function ($d) {
    return empty($d['success']) ? 'Expected success=true' : null;
});

// 5h. Delete without ID → error
assertJson('Delete type without ID → error', apiPost($BASE_URL, 'type_delete', [], $CSRF, $COOKIE_FILE), 400, function ($d) {
    return isset($d['error']) ? null : 'Expected error field';
});

// ─────────────────────────────────────────────────────────────────────────────
// TEST SUITE 6 — Users
// ─────────────────────────────────────────────────────────────────────────────

section('6. Users');

// 6a. List users
assertJson('List users', usersApiGet($BASE_URL, 'list', [], $COOKIE_FILE), 200, function ($d) {
    if (!isset($d['users'])) return 'Missing users field';
    if (count($d['users']) < 1) return 'Expected at least 1 user (admin)';
    $roles = array_column($d['users'], 'username');
    if (!in_array('admin', $roles)) return 'admin not in users list';
    return null;
});

// 6b. Create regular user
$res = usersApiPost($BASE_URL, 'save', [
    'username'     => 'testuser',
    'display_name' => 'Test User',
    'password'     => 'UserPassword123!',
    'role'         => 'user',
], $CSRF, $COOKIE_FILE);
$newUserId = null;
assertJson('Create user', $res, 200, function ($d) use (&$newUserId) {
    if (empty($d['success'])) return 'Expected success=true';
    $newUserId = (int)$d['id'];
    return null;
});

// 6c. Password too short → error
assertJson('Create user short password → error', usersApiPost($BASE_URL, 'save', [
    'username' => 'shortpw', 'display_name' => 'Short', 'password' => 'short', 'role' => 'user',
], $CSRF, $COOKIE_FILE), 400, function ($d) {
    return isset($d['error']) ? null : 'Expected error field';
});

// 6d. Missing username → error
assertJson('Create user missing username → error', usersApiPost($BASE_URL, 'save', [
    'username' => '', 'display_name' => 'No Username', 'password' => 'ValidPass123!', 'role' => 'user',
], $CSRF, $COOKIE_FILE), 400, function ($d) {
    return isset($d['error']) ? null : 'Expected error field';
});

// 6e. Update user (change display name only)
assertJson('Update user display name', usersApiPost($BASE_URL, 'save', [
    'id' => $newUserId, 'username' => 'testuser', 'display_name' => 'Updated User', 'password' => '', 'role' => 'user',
], $CSRF, $COOKIE_FILE), 200, function ($d) {
    return empty($d['success']) ? 'Expected success=true' : null;
});

// 6f. Change own password
assertJson('Change admin password', usersApiPost($BASE_URL, 'change_password', [
    'current_password' => $ADMIN_PASS,
    'new_password'     => 'NewAdminPass456!',
], $CSRF, $COOKIE_FILE), 200, function ($d) {
    return empty($d['success']) ? 'Expected success=true' : null;
});
// Restore admin password for subsequent tests
usersApiPost($BASE_URL, 'change_password', [
    'current_password' => 'NewAdminPass456!',
    'new_password'     => $ADMIN_PASS,
], $CSRF, $COOKIE_FILE);

// 6g. Wrong current password → error
assertJson('Change password wrong current → error', usersApiPost($BASE_URL, 'change_password', [
    'current_password' => 'WrongCurrentPass!',
    'new_password'     => 'ValidNewPass123!',
], $CSRF, $COOKIE_FILE), 400, function ($d) {
    return isset($d['error']) ? null : 'Expected error field';
});

// 6h. New password too short → error
assertJson('Change password too short → error', usersApiPost($BASE_URL, 'change_password', [
    'current_password' => $ADMIN_PASS,
    'new_password'     => 'short',
], $CSRF, $COOKIE_FILE), 400, function ($d) {
    return isset($d['error']) ? null : 'Expected error field';
});

// 6i. Non-admin cannot delete users
// Login as testuser
$userCookie = tempnam(sys_get_temp_dir(), 'numa_user_');
$userPage   = httpGet("{$BASE_URL}/login.php", $userCookie);
$userCsrf   = extractCsrfToken($userPage['body']);
httpPost("{$BASE_URL}/login.php", [
    'username' => 'testuser', 'password' => 'UserPassword123!', 'csrf_token' => $userCsrf,
], $userCookie);

assertJson('Non-admin delete → 403', usersApiPost($BASE_URL, 'delete', ['id' => 1], $userCsrf, $userCookie), 403, function ($d) {
    return isset($d['error']) ? null : 'Expected error field';
});
unlink($userCookie);

// 6j. Cannot delete yourself
assertJson('Cannot delete self → error', usersApiPost($BASE_URL, 'delete', ['id' => 1], $CSRF, $COOKIE_FILE), 400, function ($d) {
    return isset($d['error']) ? null : 'Expected error field';
});

// 6k. Delete test user
assertJson('Delete test user', usersApiPost($BASE_URL, 'delete', ['id' => $newUserId], $CSRF, $COOKIE_FILE), 200, function ($d) {
    return empty($d['success']) ? 'Expected success=true' : null;
});

// ─────────────────────────────────────────────────────────────────────────────
// TEST SUITE 7 — Backups (admin only)
// ─────────────────────────────────────────────────────────────────────────────

section('7. Backups');

// 7a. List backups (empty)
assertJson('backup_list (empty)', apiGet($BASE_URL, 'backup_list', [], $COOKIE_FILE), 200, function ($d) {
    if (!isset($d['backups'])) return 'Missing backups field';
    return null;
});

// 7b. Non-admin cannot create backup
$userCookie2 = tempnam(sys_get_temp_dir(), 'numa_user2_');
$userPage2   = httpGet("{$BASE_URL}/login.php", $userCookie2);
$userCsrf2   = extractCsrfToken($userPage2['body']);
// First recreate testuser for this test
usersApiPost($BASE_URL, 'save', [
    'username' => 'testuser2', 'display_name' => 'Test User 2',
    'password' => 'UserPassword123!', 'role' => 'user',
], $CSRF, $COOKIE_FILE);
httpPost("{$BASE_URL}/login.php", [
    'username' => 'testuser2', 'password' => 'UserPassword123!', 'csrf_token' => $userCsrf2,
], $userCookie2);

assertJson('Non-admin backup_create → 403', apiPost($BASE_URL, 'backup_create', [], $userCsrf2, $userCookie2), 403, function ($d) {
    return isset($d['error']) ? null : 'Expected error field';
});
unlink($userCookie2);

// Clean up testuser2
$listRes = usersApiGet($BASE_URL, 'list', [], $COOKIE_FILE);
$listData = json_decode($listRes['body'], true);
foreach ($listData['users'] ?? [] as $u) {
    if ($u['username'] === 'testuser2') {
        usersApiPost($BASE_URL, 'delete', ['id' => $u['id']], $CSRF, $COOKIE_FILE);
        break;
    }
}

// 7c. Create backup
$backupFilename = null;
assertJson('backup_create', apiPost($BASE_URL, 'backup_create', ['label' => 'test_backup'], $CSRF, $COOKIE_FILE), 200, function ($d) use (&$backupFilename) {
    if (empty($d['success']))  return 'Expected success=true';
    if (empty($d['filename'])) return 'Missing filename in response';
    $backupFilename = $d['filename'];
    return null;
});

// 7d. Backup appears in list
assertJson('backup_list shows created backup', apiGet($BASE_URL, 'backup_list', [], $COOKIE_FILE), 200, function ($d) use ($backupFilename) {
    $filenames = array_column($d['backups'], 'filename');
    if (!in_array($backupFilename, $filenames)) return "Backup {$backupFilename} not in list";
    return null;
});

// 7e. Download backup
if ($backupFilename) {
    $dlRes = httpGet("{$BASE_URL}/api.php?action=backup_download&filename=" . urlencode($backupFilename), $COOKIE_FILE);
    assertStatus('backup_download', $dlRes, 200);
} else {
    fail('backup_download', 'Skipped — no backup filename (backup_create failed)');
}

// 7f. Download non-existent backup → 404
$dlBad = httpGet("{$BASE_URL}/api.php?action=backup_download&filename=nonexistent.sqlite", $COOKIE_FILE);
assertStatus('backup_download missing file → 404', $dlBad, 404);

// 7g. Restore backup
if ($backupFilename) {
    assertJson('backup_restore', apiPost($BASE_URL, 'backup_restore', ['filename' => $backupFilename], $CSRF, $COOKIE_FILE), 200, function ($d) {
        return empty($d['success']) ? 'Expected success=true' : null;
    });

    // 7h. After restore, items still exist (backup was taken after items were created)
    assertJson('Items survive backup restore', apiGet($BASE_URL, 'list', [], $COOKIE_FILE), 200, function ($d) {
        if ($d['total'] < 1) return "Expected items after restore, got {$d['total']}";
        return null;
    });

    // 7i. Delete original backup (restore auto-creates a pre-restore backup)
    assertJson('backup_delete', apiPost($BASE_URL, 'backup_delete', ['filename' => $backupFilename], $CSRF, $COOKIE_FILE), 200, function ($d) {
        return empty($d['success']) ? 'Expected success=true' : null;
    });
} else {
    fail('backup_restore',  'Skipped — no backup filename');
    fail('backup_delete',   'Skipped — no backup filename');
}

// 7j. Unknown action → 400
assertJson('Unknown action → 400', apiGet($BASE_URL, 'unknown_action', [], $COOKIE_FILE), 400, function ($d) {
    return isset($d['error']) ? null : 'Expected error field';
});

// ─────────────────────────────────────────────────────────────────────────────
// TEST SUITE 8 — v5 endpoints (memberships, idol resolution, conflict handling)
// ─────────────────────────────────────────────────────────────────────────────

section('8. v5 endpoints (memberships + idol resolution)');

// Setup: fresh hierarchy for membership tests
// JYP (company)
//   └ ITZY (group)
//       └ Yuna (member)   ← id captured below
//   └ TWICE (group)
//       └ Mina (member)
// AKB48Co (company)
//   └ AKB48 (group)
//       └ Yuna (member)   ← duplicate name — used for ambiguity tests

$jypId = (int) (json_decode(apiPost($BASE_URL, 'idol_entity_save', [
    'name' => 'JYP', 'category' => 'company', 'parent_id' => '', 'sort_order' => 0,
], $CSRF, $COOKIE_FILE)['body'], true)['id'] ?? 0);

$itzyId = (int) (json_decode(apiPost($BASE_URL, 'idol_entity_save', [
    'name' => 'ITZY', 'category' => 'group', 'parent_id' => $jypId, 'sort_order' => 0,
], $CSRF, $COOKIE_FILE)['body'], true)['id'] ?? 0);

$twiceId = (int) (json_decode(apiPost($BASE_URL, 'idol_entity_save', [
    'name' => 'TWICE', 'category' => 'group', 'parent_id' => $jypId, 'sort_order' => 0,
], $CSRF, $COOKIE_FILE)['body'], true)['id'] ?? 0);

$akbCoId = (int) (json_decode(apiPost($BASE_URL, 'idol_entity_save', [
    'name' => 'AKB48Co', 'category' => 'company', 'parent_id' => '', 'sort_order' => 0,
], $CSRF, $COOKIE_FILE)['body'], true)['id'] ?? 0);

$akbId = (int) (json_decode(apiPost($BASE_URL, 'idol_entity_save', [
    'name' => 'AKB48', 'category' => 'group', 'parent_id' => $akbCoId, 'sort_order' => 0,
], $CSRF, $COOKIE_FILE)['body'], true)['id'] ?? 0);

// Member with auto-created membership
$yunaItzyRes = apiPost($BASE_URL, 'idol_entity_save', [
    'name' => 'Yuna', 'category' => 'member', 'parent_id' => $itzyId, 'display_hint' => 'ITZY', 'sort_order' => 0,
], $CSRF, $COOKIE_FILE);
$yunaItzyData = json_decode($yunaItzyRes['body'], true);
$yunaItzyId = (int) ($yunaItzyData['id'] ?? 0);
assertJson('Create member entity creates default membership', $yunaItzyRes, 200, function ($d) {
    return empty($d['success']) ? 'Expected success=true' : null;
});

// Verify membership list endpoint
assertJson('membership_list returns 1 default membership',
    apiGet($BASE_URL, 'membership_list', ['member_id' => $yunaItzyId], $COOKIE_FILE), 200,
    function ($d) {
        if (count($d['data']) !== 1) return 'Expected 1 membership, got ' . count($d['data']);
        if ((int)$d['data'][0]['is_primary'] !== 1) return 'Expected is_primary=1';
        return null;
    });

// Duplicate Yuna under AKB48 — name collision intentional
$yunaAkbRes  = apiPost($BASE_URL, 'idol_entity_save', [
    'name' => 'Yuna', 'category' => 'member', 'parent_id' => $akbId, 'display_hint' => 'AKB48', 'sort_order' => 0,
], $CSRF, $COOKIE_FILE);
$yunaAkbId = (int) (json_decode($yunaAkbRes['body'], true)['id'] ?? 0);
assertStatus('Create duplicate-name member (different group)', $yunaAkbRes, 200);

// Mina under TWICE (unique name)
$minaRes = apiPost($BASE_URL, 'idol_entity_save', [
    'name' => 'Mina', 'category' => 'member', 'parent_id' => $twiceId, 'display_hint' => 'TWICE', 'sort_order' => 0,
], $CSRF, $COOKIE_FILE);
$minaId = (int) (json_decode($minaRes['body'], true)['id'] ?? 0);

// 8a. idol_search
assertJson('idol_search returns Yuna entities',
    apiGet($BASE_URL, 'idol_search', ['q' => 'Yuna'], $COOKIE_FILE), 200,
    function ($d) {
        if (count($d['data']) !== 2) return 'Expected 2 Yuna entries, got ' . count($d['data']);
        $hints = array_column($d['data'], 'display_hint');
        if (!in_array('ITZY', $hints) || !in_array('AKB48', $hints)) return 'Missing display_hint values';
        return null;
    });

// 8b. idol_resolve_name → ambiguous
assertJson('idol_resolve_name = "Yuna" → ambiguous',
    apiGet($BASE_URL, 'idol_resolve_name', ['name' => 'Yuna'], $COOKIE_FILE), 200,
    function ($d) {
        if (empty($d['ambiguous'])) return 'Expected ambiguous=true';
        if (count($d['candidates']) !== 2) return 'Expected 2 candidates';
        return null;
    });

// 8c. idol_resolve_name → single match (Mina)
assertJson('idol_resolve_name = "Mina" → resolved',
    apiGet($BASE_URL, 'idol_resolve_name', ['name' => 'Mina'], $COOKIE_FILE), 200,
    function ($d) use ($minaId) {
        if (!empty($d['ambiguous'])) return 'Expected ambiguous=false';
        if ((int)$d['id'] !== $minaId) return "Expected id={$minaId}, got " . ($d['id'] ?? 'null');
        return null;
    });

// 8d. Create item with idol_id (Hybrid policy — preferred path)
$createMina = apiPost($BASE_URL, 'create', [
    'order_date' => '2025-03-01', 'event_date' => '',
    'title' => 'Mina album', 'idol_id' => $minaId, 'idol' => '',
    'type' => 'Album', 'price_per_qty' => 800, 'qty' => 1,
], $CSRF, $COOKIE_FILE);
assertJson('Create item with idol_id', $createMina, 200, function ($d) {
    return empty($d['success']) ? 'Expected success=true' : null;
});

// 8e. Create item with ambiguous idol name → 409
$ambItem = apiPost($BASE_URL, 'create', [
    'order_date' => '2025-04-01', 'event_date' => '',
    'title' => 'Ambiguous Yuna', 'idol' => 'Yuna',
    'type' => 'Photo', 'price_per_qty' => 200, 'qty' => 1,
], $CSRF, $COOKIE_FILE);
assertJson('Create item with ambiguous idol name → 409', $ambItem, 409, function ($d) {
    if (!isset($d['error']))      return 'Expected error field';
    if (!isset($d['candidates'])) return 'Expected candidates field';
    if (count($d['candidates']) !== 2) return 'Expected 2 candidates';
    return null;
});

// 8f. Create item with idol_id resolves the right Yuna
$createYunaItem = apiPost($BASE_URL, 'create', [
    'order_date' => '2025-04-15', 'event_date' => '',
    'title' => 'Yuna photocard', 'idol_id' => $yunaItzyId, 'idol' => 'Yuna',
    'type' => 'Photo', 'price_per_qty' => 300, 'qty' => 2,
], $CSRF, $COOKIE_FILE);
$yunaItemId = (int) (json_decode($createYunaItem['body'], true)['id'] ?? 0);
assertStatus('Create item with idol_id resolves correctly', $createYunaItem, 200);

// 8g. report_by_group includes ITZY
assertJson('report_by_group includes ITZY',
    apiGet($BASE_URL, 'report_by_group', [], $COOKIE_FILE), 200,
    function ($d) {
        $names = array_column($d['data'], 'name');
        return in_array('ITZY', $names) ? null : 'ITZY missing from groups: ' . implode(',', $names);
    });

// 8h. report_by_company includes JYP
assertJson('report_by_company includes JYP',
    apiGet($BASE_URL, 'report_by_company', [], $COOKIE_FILE), 200,
    function ($d) {
        $names = array_column($d['data'], 'name');
        return in_array('JYP', $names) ? null : 'JYP missing from companies: ' . implode(',', $names);
    });

// 8i. report_group_detail for ITZY
assertJson('report_group_detail for ITZY',
    apiGet($BASE_URL, 'report_group_detail', ['group_id' => $itzyId], $COOKIE_FILE), 200,
    function ($d) {
        if (!isset($d['members'], $d['sub_units'], $d['by_month'])) return 'Missing fields';
        return null;
    });

// 8j. membership_move — Yuna moves from ITZY to TWICE
assertJson('membership_move (Yuna ITZY → TWICE)',
    apiPost($BASE_URL, 'membership_move', [
        'member_id'    => $yunaItzyId,
        'new_group_id' => $twiceId,
        'move_date'    => '2025-06-01',
    ], $CSRF, $COOKIE_FILE), 200,
    function ($d) {
        if (empty($d['success']))            return 'Expected success=true';
        if (empty($d['new_membership_id']))  return 'Missing new_membership_id';
        return null;
    });

// After move: Yuna has 2 memberships (ITZY ended, TWICE current)
assertJson('After move Yuna has 2 memberships',
    apiGet($BASE_URL, 'membership_list', ['member_id' => $yunaItzyId], $COOKIE_FILE), 200,
    function ($d) {
        if (count($d['data']) !== 2) return 'Expected 2, got ' . count($d['data']);
        $hasClosed = false; $hasOpen = false;
        foreach ($d['data'] as $m) {
            if ($m['end_date'] !== null) $hasClosed = true;
            if ($m['end_date'] === null) $hasOpen = true;
        }
        if (!$hasClosed) return 'No closed membership';
        if (!$hasOpen)   return 'No open membership';
        return null;
    });

// 8k. After move, item from 2025-04-15 should still appear under ITZY (before move date)
$beforeMove = apiPost($BASE_URL, 'create', [
    'order_date' => '2025-05-15', 'event_date' => '',
    'title' => 'Before move', 'idol_id' => $yunaItzyId, 'idol' => 'Yuna',
    'type' => 'Photo', 'price_per_qty' => 100, 'qty' => 1,
], $CSRF, $COOKIE_FILE);
$afterMove = apiPost($BASE_URL, 'create', [
    'order_date' => '2025-07-01', 'event_date' => '',
    'title' => 'After move', 'idol_id' => $yunaItzyId, 'idol' => 'Yuna',
    'type' => 'Photo', 'price_per_qty' => 100, 'qty' => 1,
], $CSRF, $COOKIE_FILE);

// Group breakdown should reflect membership history
assertJson('Items split between groups by membership date',
    apiGet($BASE_URL, 'report_by_group', [], $COOKIE_FILE), 200,
    function ($d) {
        $byName = [];
        foreach ($d['data'] as $g) $byName[$g['name']] = $g;
        if (!isset($byName['ITZY']))  return 'ITZY missing';
        if (!isset($byName['TWICE'])) return 'TWICE missing';
        // ITZY should have at least 1 item (before move + initial photocard)
        if ($byName['ITZY']['items'] < 1)  return "ITZY items < 1";
        if ($byName['TWICE']['items'] < 1) return "TWICE items < 1";
        return null;
    });

// 8l. membership_save validation — invalid date order → 400
assertJson('membership_save invalid date order → 400',
    apiPost($BASE_URL, 'membership_save', [
        'member_id'  => $yunaItzyId,
        'group_id'   => $itzyId,
        'start_date' => '2025-12-01',
        'end_date'   => '2025-01-01',
    ], $CSRF, $COOKIE_FILE), 400,
    function ($d) {
        return isset($d['error']) ? null : 'Expected error field';
    });

// 8m. Overlapping primary membership → warning (loose policy, success but with warnings)
assertJson('Overlapping primary returns warning',
    apiPost($BASE_URL, 'membership_save', [
        'member_id'  => $yunaItzyId,
        'group_id'   => $itzyId,
        'start_date' => '2025-04-01',
        'end_date'   => '2025-05-30',
        'is_primary' => 1,
    ], $CSRF, $COOKIE_FILE), 200,
    function ($d) {
        if (empty($d['success'])) return 'Expected success=true';
        if (empty($d['warnings']) || !is_array($d['warnings'])) return 'Expected warnings array';
        if (count($d['warnings']) < 1) return 'Expected at least 1 warning';
        return null;
    });

// 8n. ambiguous_list — should be empty (items used idol_id explicitly)
assertJson('ambiguous_list empty when all items used idol_id',
    apiGet($BASE_URL, 'ambiguous_list', [], $COOKIE_FILE), 200,
    function ($d) {
        return isset($d['data']) ? null : 'Missing data field';
    });

// 8o. autoBackfill — create an item with idol text matching a unique entity
$itemMina = apiPost($BASE_URL, 'create', [
    'order_date' => '2025-08-01', 'event_date' => '',
    'title' => 'Mina by name', 'idol' => 'Mina',
    'type' => 'Sticker', 'price_per_qty' => 50, 'qty' => 1,
], $CSRF, $COOKIE_FILE);
$itemMinaId = (int) (json_decode($itemMina['body'], true)['id'] ?? 0);

assertJson('Item by unique name → idol_id auto-resolved',
    apiGet($BASE_URL, 'get', ['id' => $itemMinaId], $COOKIE_FILE), 200,
    function ($d) use ($minaId) {
        $iid = $d['data']['idol_id'] ?? null;
        return ((int) $iid) === $minaId ? null : "Expected idol_id={$minaId}, got " . var_export($iid, true);
    });

// 8p. item_remap — manually fix an item's idol_id
assertJson('item_remap',
    apiPost($BASE_URL, 'item_remap', ['item_id' => $itemMinaId, 'idol_id' => $yunaItzyId], $CSRF, $COOKIE_FILE), 200,
    function ($d) {
        return empty($d['success']) ? 'Expected success=true' : null;
    });

// 8q. membership_delete (rollback all Yuna memberships then cleanup)
$mbList = json_decode(apiGet($BASE_URL, 'membership_list', ['member_id' => $yunaItzyId], $COOKIE_FILE)['body'], true);
foreach ($mbList['data'] as $m) {
    apiPost($BASE_URL, 'membership_delete', ['id' => $m['id']], $CSRF, $COOKIE_FILE);
}
$mbListAfter = json_decode(apiGet($BASE_URL, 'membership_list', ['member_id' => $yunaItzyId], $COOKIE_FILE)['body'], true);
if (count($mbListAfter['data']) === 0) pass('membership_delete clears all rows');
else                                    fail('membership_delete clears all rows', 'remaining: ' . count($mbListAfter['data']));

// ─────────────────────────────────────────────────────────────────────────────
// TEST SUITE 9 — Ticket detection (events + type_categories)
// ─────────────────────────────────────────────────────────────────────────────

section('9. Ticket detection (events + type_categories)');

// 9a. Create a type category flagged as a ticket type
$ticketTypeRes = apiPost($BASE_URL, 'type_save', [
    'name' => 'ConcertTicket', 'description' => 'Admission ticket', 'sort_order' => 1, 'is_ticket' => '1',
], $CSRF, $COOKIE_FILE);
$ticketTypeId = (int) (json_decode($ticketTypeRes['body'], true)['id'] ?? 0);
assertJson('Create ticket-flagged type category', $ticketTypeRes, 200, function ($d) {
    return empty($d['success']) ? 'Expected success=true' : null;
});

assertJson('type_list reflects is_ticket=1', apiGet($BASE_URL, 'type_list', [], $COOKIE_FILE), 200, function ($d) use ($ticketTypeId) {
    foreach ($d['types'] as $ty) {
        if ((int) $ty['id'] === $ticketTypeId) {
            return ((int) $ty['is_ticket'] === 1) ? null : 'Expected is_ticket=1';
        }
    }
    return 'Type not found in list';
});

// 9b. Create an event without a ticket item yet → should report as missing
$eventRes = apiPost($BASE_URL, 'event_save', [
    'name' => 'Ticket Test Concert', 'event_date' => '2025-09-01', 'end_date' => '', 'description' => '',
], $CSRF, $COOKIE_FILE);
$ticketEventId = (int) (json_decode($eventRes['body'], true)['id'] ?? 0);
assertJson('Create event', $eventRes, 200, function ($d) {
    return empty($d['success']) ? 'Expected success=true' : null;
});

assertJson('event_list: new event has no ticket yet', apiGet($BASE_URL, 'event_list', [], $COOKIE_FILE), 200, function ($d) use ($ticketEventId) {
    if (($d['ticket_types_count'] ?? 0) < 1) return 'Expected ticket_types_count >= 1';
    foreach ($d['events'] as $ev) {
        if ((int) $ev['id'] === $ticketEventId) {
            if ((int) $ev['is_free_entry'] !== 0) return 'Expected is_free_entry=0';
            return ((int) $ev['ticket_items_count'] === 0) ? null : 'Expected ticket_items_count=0';
        }
    }
    return 'Event not found in list';
});

// 9c. Link a ticket-typed item to the event → should now be detected
$ticketItemRes = apiPost($BASE_URL, 'create', [
    'order_date' => '2025-08-20', 'event_date' => '2025-09-01', 'event_id' => $ticketEventId,
    'title' => 'Concert admission', 'idol' => 'Member A',
    'type' => 'ConcertTicket', 'price_per_qty' => 2000, 'qty' => 1,
], $CSRF, $COOKIE_FILE);
$ticketItemId = (int) (json_decode($ticketItemRes['body'], true)['id'] ?? 0);

assertJson('event_list: ticket item now detected', apiGet($BASE_URL, 'event_list', [], $COOKIE_FILE), 200, function ($d) use ($ticketEventId) {
    foreach ($d['events'] as $ev) {
        if ((int) $ev['id'] === $ticketEventId) {
            return ((int) $ev['ticket_items_count'] === 1) ? null : 'Expected ticket_items_count=1, got ' . $ev['ticket_items_count'];
        }
    }
    return 'Event not found in list';
});

// 9d. Free-entry event is reported regardless of linked items
$freeEventRes = apiPost($BASE_URL, 'event_save', [
    'name' => 'Free Fan Meeting', 'event_date' => '2025-09-05', 'end_date' => '', 'description' => '', 'is_free_entry' => '1',
], $CSRF, $COOKIE_FILE);
$freeEventId = (int) (json_decode($freeEventRes['body'], true)['id'] ?? 0);

assertJson('event_list: free-entry flag persisted', apiGet($BASE_URL, 'event_list', [], $COOKIE_FILE), 200, function ($d) use ($freeEventId) {
    foreach ($d['events'] as $ev) {
        if ((int) $ev['id'] === $freeEventId) {
            return ((int) $ev['is_free_entry'] === 1) ? null : 'Expected is_free_entry=1';
        }
    }
    return 'Event not found in list';
});

// 9e. Not-attended event is reported regardless of linked items, and clears is_free_entry
$notAttendedRes = apiPost($BASE_URL, 'event_save', [
    'name' => 'Skipped Fan Meeting', 'event_date' => '2025-09-06', 'end_date' => '', 'description' => '',
    'is_free_entry' => '1', 'is_not_attended' => '1',
], $CSRF, $COOKIE_FILE);
$notAttendedEventId = (int) (json_decode($notAttendedRes['body'], true)['id'] ?? 0);

assertJson('event_list: not-attended flag persisted and free entry cleared', apiGet($BASE_URL, 'event_list', [], $COOKIE_FILE), 200, function ($d) use ($notAttendedEventId) {
    foreach ($d['events'] as $ev) {
        if ((int) $ev['id'] === $notAttendedEventId) {
            if ((int) $ev['is_not_attended'] !== 1) return 'Expected is_not_attended=1';
            return ((int) $ev['is_free_entry'] === 0) ? null : 'Expected is_free_entry=0 when is_not_attended=1';
        }
    }
    return 'Event not found in list';
});

// 9f. Cleanup
apiPost($BASE_URL, 'delete', ['id' => $ticketItemId], $CSRF, $COOKIE_FILE);
apiPost($BASE_URL, 'event_delete', ['id' => $ticketEventId], $CSRF, $COOKIE_FILE);
apiPost($BASE_URL, 'event_delete', ['id' => $freeEventId], $CSRF, $COOKIE_FILE);
apiPost($BASE_URL, 'event_delete', ['id' => $notAttendedEventId], $CSRF, $COOKIE_FILE);
apiPost($BASE_URL, 'type_delete', ['id' => $ticketTypeId], $CSRF, $COOKIE_FILE);

// ─────────────────────────────────────────────────────────────────────────────
// SUMMARY
// ─────────────────────────────────────────────────────────────────────────────

$total = $PASS + $FAIL;
echo "\n" . bold('=== Results ===') . "\n";
echo green("  Passed: {$PASS}") . " / {$total}\n";

if ($FAIL > 0) {
    echo red("  Failed: {$FAIL}") . " / {$total}\n\n";
    echo bold("Failed tests:\n");
    foreach ($ERRORS as $e) {
        echo '  ' . red('•') . " {$e}\n";
    }
    echo "\n";
    exit(1);
} else {
    echo "\n  " . green('All tests passed!') . "\n\n";
    exit(0);
}
