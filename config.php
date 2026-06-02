<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Bangkok');

define('APP_VERSION', '1.9.3');
const DB_SCHEMA_VERSION = 7;

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

    // Login attempt tracking for brute force protection
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip TEXT NOT NULL,
            attempted_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
        )
    ");

    // Indexes for common query patterns
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_items_idol         ON items(idol)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_items_type         ON items(type)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_items_order_date   ON items(order_date)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_items_type_idol    ON items(type, idol)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_ie_parent_id       ON idol_entities(parent_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_ie_category        ON idol_entities(category)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_login_attempts_ip  ON login_attempts(ip, attempted_at)');

    require_once __DIR__ . '/migrations/_helpers.php';
    $currentVer = getSchemaVersion($pdo);
    if ($currentVer < 5) {
        require_once __DIR__ . '/migrations/v5_idol_refactor.php';
        runMigrationV5($pdo);
        $currentVer = 5;
    }
    if ($currentVer < 6) {
        require_once __DIR__ . '/migrations/v6_budgets.php';
        runMigrationV6($pdo);
        $currentVer = 6;
    }
    if ($currentVer < 7) {
        require_once __DIR__ . '/migrations/v7_budget_periods.php';
        runMigrationV7($pdo);
    }
}

require_once __DIR__ . '/helpers_idol.php';

// Ensure backup directory exists before initDB() (migrations write auto-backups here)
if (!is_dir(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}

initDB();
sendSecurityHeaders();

// Security headers
function sendSecurityHeaders(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // CSP: allow CDN for Bootstrap/Chart.js/Icons; unsafe-inline required for inline scripts/styles.
    // connect-src includes cdn.jsdelivr.net so DevTools can fetch sourcemaps (*.js.map / *.css.map).
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; font-src https://cdn.jsdelivr.net; img-src 'self' data:; connect-src 'self' https://cdn.jsdelivr.net; frame-ancestors 'none'");
    // Dynamic responses must not be cached — otherwise GETs after a POST can return stale data.
    // Excel export (export.php) sets its own Cache-Control which overrides this.
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

// Auth helpers
function startAppSession(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $opts = [
        'cookie_lifetime' => SESSION_LIFETIME,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'gc_maxlifetime' => SESSION_LIFETIME,
    ];

    session_start($opts);

    // Enforce server-side session timeout
    if (isset($_SESSION['user_id'], $_SESSION['_last_activity'])) {
        if (time() - $_SESSION['_last_activity'] > SESSION_LIFETIME) {
            session_unset();
            session_destroy();
            session_start($opts);
            return;
        }
        $_SESSION['_last_activity'] = time();
    } elseif (isset($_SESSION['user_id'])) {
        // First request after login — initialise activity timestamp
        $_SESSION['_last_activity'] = time();
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
    // Force password change if the user is still on the default password
    if (!empty($_SESSION['force_password_change'])) {
        $script = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
        if ($script !== 'change_password_required.php') {
            header('Location: change_password_required.php');
            exit;
        }
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

// ── Internationalization (i18n) ───────────────────────────────────────────────
// Two-language support (English default, Thai). Translation strings live in
// lang/en.php and lang/th.php as flat keyed arrays. The same dictionary is
// exposed to client-side JS as window.I18N (see assets/i18n.js).
const SUPPORTED_LANGS = ['en', 'th'];
const DEFAULT_LANG    = 'en';

/**
 * Resolve the active UI language. Order of precedence:
 *   ?lang= (validated, then persisted to session + cookie) → session → cookie → default.
 * Result is cached per request. Must run before any output when ?lang= is present
 * (it sets a cookie); config.php triggers it at load time for non-CLI requests.
 */
function currentLang(): string
{
    static $lang = null;
    if ($lang !== null) return $lang;

    startAppSession();

    if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGS, true)) {
        $lang = $_GET['lang'];
        $_SESSION['lang'] = $lang;
        setcookie('lang', $lang, [
            'expires'  => time() + 31536000, // 1 year
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        ]);
        return $lang;
    }
    if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], SUPPORTED_LANGS, true)) {
        return $lang = $_SESSION['lang'];
    }
    if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], SUPPORTED_LANGS, true)) {
        return $lang = $_COOKIE['lang'];
    }
    return $lang = DEFAULT_LANG;
}

/** Load the merged translation dictionary (English base + active-language overlay). */
function loadLang(): array
{
    static $dict = null;
    if ($dict !== null) return $dict;

    $dict = require __DIR__ . '/lang/en.php';
    $cur  = currentLang();
    if ($cur !== 'en' && is_file(__DIR__ . "/lang/{$cur}.php")) {
        $dict = array_merge($dict, require __DIR__ . "/lang/{$cur}.php");
    }
    return $dict;
}

/**
 * Translate a key. Missing keys fall back to the key itself (so nothing renders
 * blank). {placeholders} in the string are replaced from $params.
 */
function t(string $key, array $params = []): string
{
    $s = loadLang()[$key] ?? $key;
    if (!is_string($s)) return $key; // non-string entries (e.g. date.months) are read directly
    foreach ($params as $k => $v) {
        $s = str_replace('{' . $k . '}', (string) $v, $s);
    }
    return $s;
}

/** Build a URL to the current page with the lang query param set to $code. */
function langUrl(string $code): string
{
    $params = $_GET;
    $params['lang'] = $code;
    $script = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    return $script . '?' . http_build_query($params);
}

/** Render the EN/TH toggle for the navbar. Single source of truth for the markup. */
function langSwitcher(): string
{
    $cur    = currentLang();
    $script = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');

    // Help is two separate per-language files — point each toggle at the right file.
    if ($script === 'help.php' || $script === 'help_en.php') {
        $enUrl = 'help_en.php?lang=en';
        $thUrl = 'help.php?lang=th';
    } else {
        $enUrl = langUrl('en');
        $thUrl = langUrl('th');
    }

    $btn = function (string $code, string $label, string $url) use ($cur) {
        $active = $cur === $code ? ' active fw-bold' : '';
        return '<a href="' . htmlspecialchars($url) . '" class="btn btn-outline-light btn-sm py-0 px-2' . $active . '">' . $label . '</a>';
    };
    return '<div class="btn-group me-2" role="group" aria-label="Language">'
        . $btn('en', 'EN', $enUrl)
        . $btn('th', 'TH', $thUrl)
        . '</div>';
}

// Resolve language early (before page output) so the ?lang= cookie can be set
// cleanly. Skipped on CLI (migrations / tests) where there is no session/cookie.
if (PHP_SAPI !== 'cli') {
    currentLang();
}
