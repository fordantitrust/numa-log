<?php

declare(strict_types=1);

define('APP_VERSION', '1.3.2');

// Use data/ directory if it exists (Docker), otherwise use project root
$dataDir = is_dir(__DIR__ . '/data') ? __DIR__ . '/data' : __DIR__;
define('DB_PATH', $dataDir . '/database.sqlite');
define('BACKUP_DIR', $dataDir . '/backups');
define('ALLOW_IMPORT', false);
define('ALLOW_RESEED', false);
define('AUTH_ENABLED', true);
define('SESSION_LIFETIME', 86400); // 24 hours

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA foreign_keys=ON');
        $pdo->exec('PRAGMA synchronous=NORMAL');
        $pdo->exec('PRAGMA cache_size=-8000');
        $pdo->exec('PRAGMA temp_store=MEMORY');
    }
    return $pdo;
}

function initDB(): void
{
    $pdo = getDB();
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

    // Seed default admin if no users exist
    $userCount = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount === 0) {
        $hash = password_hash('admin', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, password, display_name, role) VALUES ('admin', :pw, 'Administrator', 'admin')")
            ->execute([':pw' => $hash]);
    }

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

    // Indexes for common query patterns
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_items_idol       ON items(idol)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_items_type       ON items(type)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_items_order_date ON items(order_date)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_items_type_idol  ON items(type, idol)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_ie_parent_id     ON idol_entities(parent_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_ie_category      ON idol_entities(category)');
}

initDB();
sendSecurityHeaders();

// Create backup directory
if (!is_dir(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}

// Security headers
function sendSecurityHeaders(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Auth helpers
function startAppSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_lifetime' => SESSION_LIFETIME,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict',
            'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        ]);
    }
}

function isLoggedIn(): bool
{
    startAppSession();
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array
{
    startAppSession();
    if (!isset($_SESSION['user_id'])) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'display_name' => $_SESSION['display_name'],
        'role' => $_SESSION['role'],
    ];
}

function requireAuth(): void
{
    if (!AUTH_ENABLED) return;
    if (!isLoggedIn()) {
        // Check if this is an AJAX/API request
        $isApi = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
            || str_ends_with($_SERVER['SCRIPT_NAME'] ?? '', 'api.php')
            || str_ends_with($_SERVER['SCRIPT_NAME'] ?? '', 'api_users.php');
        if ($isApi) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void
{
    requireAuth();
    $user = currentUser();
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit;
    }
}

// CSRF protection
function csrfToken(): string
{
    startAppSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    startAppSession();
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
}
