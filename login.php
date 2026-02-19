<?php

declare(strict_types=1);

require __DIR__ . '/config.php';

startAppSession();

// Handle logout
if (($_GET['action'] ?? '') === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}

// If already logged in, redirect
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

// Check if admin still uses the default password
$showDefaultHint = false;
try {
    $adminRow = getDB()->query("SELECT password FROM users WHERE username = 'admin' LIMIT 1")->fetch();
    if ($adminRow && password_verify('admin', $adminRow['password'])) {
        $showDefaultHint = true;
    }
} catch (\Exception $e) {}

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter username and password.';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['display_name'] = $user['display_name'];
            $_SESSION['role'] = $user['role'];

            // Update last login
            $pdo->prepare("UPDATE users SET last_login = datetime('now','localtime') WHERE id = :id")
                ->execute([':id' => $user['id']]);

            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Numa Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary: #7c3aed; --primary-hover: #6d28d9; }
        body { background: #f3f4f6; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-hover); border-color: var(--primary-hover); }
        .login-card { width: 100%; max-width: 400px; border: none; box-shadow: 0 4px 24px rgba(0,0,0,.1); border-radius: 12px; }
        .login-header { background: var(--primary); color: white; border-radius: 12px 12px 0 0; padding: 2rem; text-align: center; }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="login-header">
            <i class="bi bi-stars" style="font-size: 2.5rem;"></i>
            <h4 class="mt-2 mb-0">Numa Log</h4>
        </div>
        <div class="card-body p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                <div class="mb-3">
                    <label class="form-label small">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </button>
            </form>
            <?php if ($showDefaultHint): ?>
            <div class="text-center mt-3 small text-muted">
                Default: admin / admin
            </div>
            <?php endif; ?>
        </div>
        <div class="card-footer text-center small text-muted py-2">
            v<?= APP_VERSION ?>
        </div>
    </div>
</body>
</html>
