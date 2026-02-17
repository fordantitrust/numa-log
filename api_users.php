<?php

declare(strict_types=1);

require __DIR__ . '/config.php';

header('Content-Type: application/json');
requireAuth();

// CSRF check for state-changing requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
}

$pdo = getDB();
$action = $_REQUEST['action'] ?? '';
$me = currentUser();

try {
    match ($action) {
        'list' => handleUserList($pdo),
        'save' => handleUserSave($pdo, $me),
        'delete' => handleUserDelete($pdo, $me),
        'change_password' => handleChangePassword($pdo, $me),
        default => jsonResp(['error' => 'Unknown action'], 400),
    };
} catch (Throwable $e) {
    error_log('API error: ' . $e->getMessage());
    jsonResp(['error' => 'An internal error occurred'], 500);
}

function jsonResp(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function handleUserList(PDO $pdo): void
{
    $users = $pdo->query("SELECT id, username, display_name, role, last_login, created_at FROM users ORDER BY id")->fetchAll();
    jsonResp(['users' => $users]);
}

function handleUserSave(PDO $pdo, array $me): void
{
    if ($me['role'] !== 'admin') {
        jsonResp(['error' => 'Admin access required'], 403);
    }

    $id = (int) ($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $displayName = trim($_POST['display_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? 'user');

    if ($username === '' || $displayName === '') {
        jsonResp(['error' => 'Username and display name are required'], 400);
    }
    if (!in_array($role, ['admin', 'user'], true)) {
        jsonResp(['error' => 'Invalid role'], 400);
    }

    if ($id > 0) {
        // Update
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET display_name = :dn, password = :pw, role = :role WHERE id = :id");
            $stmt->execute([':dn' => $displayName, ':pw' => $hash, ':role' => $role, ':id' => $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET display_name = :dn, role = :role WHERE id = :id");
            $stmt->execute([':dn' => $displayName, ':role' => $role, ':id' => $id]);
        }
    } else {
        // Create
        if ($password === '') {
            jsonResp(['error' => 'Password is required for new users'], 400);
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, display_name, role) VALUES (:u, :pw, :dn, :role)");
        $stmt->execute([':u' => $username, ':pw' => $hash, ':dn' => $displayName, ':role' => $role]);
        $id = (int) $pdo->lastInsertId();
    }

    jsonResp(['success' => true, 'id' => $id]);
}

function handleUserDelete(PDO $pdo, array $me): void
{
    if ($me['role'] !== 'admin') {
        jsonResp(['error' => 'Admin access required'], 403);
    }

    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
        jsonResp(['error' => 'ID is required'], 400);
    }
    if ($id === $me['id']) {
        jsonResp(['error' => 'Cannot delete yourself'], 400);
    }

    $pdo->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $id]);
    jsonResp(['success' => true]);
}

function handleChangePassword(PDO $pdo, array $me): void
{
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    if ($currentPassword === '' || $newPassword === '') {
        jsonResp(['error' => 'Both passwords are required'], 400);
    }
    if (strlen($newPassword) < 4) {
        jsonResp(['error' => 'Password must be at least 4 characters'], 400);
    }

    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute([':id' => $me['id']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        jsonResp(['error' => 'Current password is incorrect'], 400);
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = :pw WHERE id = :id")
        ->execute([':pw' => $hash, ':id' => $me['id']]);

    jsonResp(['success' => true]);
}
