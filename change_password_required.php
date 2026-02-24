<?php

declare(strict_types=1);

require __DIR__ . '/config.php';

requireAuth();

$error = '';
$me = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword === '') {
        $error = 'Please enter a new password.';
    } elseif (strlen($newPassword) < 12) {
        $error = 'Password must be at least 12 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        getDB()->prepare("UPDATE users SET password = :pw WHERE id = :id")
            ->execute([':pw' => $hash, ':id' => $me['id']]);

        unset($_SESSION['force_password_change']);
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Numa Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary: #7c3aed; --primary-hover: #6d28d9; }
        body { background: #f3f4f6; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-hover); border-color: var(--primary-hover); }
        .login-card { width: 100%; max-width: 420px; border: none; box-shadow: 0 4px 24px rgba(0,0,0,.1); border-radius: 12px; }
        .login-header { background: var(--primary); color: white; border-radius: 12px 12px 0 0; padding: 2rem; text-align: center; }
        @media (max-width: 575.98px) {
            input[type="password"] { font-size: 16px !important; }
        }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="login-header">
            <i class="bi bi-shield-lock" style="font-size: 2.5rem;"></i>
            <h4 class="mt-2 mb-0">Change Password Required</h4>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-warning py-2 small mb-3">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                Your account is using the default password. Please set a new password to continue.
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                <div class="mb-3">
                    <label class="form-label small">New Password <span class="text-muted">(min 12 characters)</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" name="new_password" required autofocus minlength="12">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label small">Confirm New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" name="confirm_password" required minlength="12">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-circle"></i> Save New Password
                </button>
            </form>
        </div>
        <div class="card-footer text-center py-2">
            <a href="login.php?action=logout" class="small text-muted">
                <i class="bi bi-box-arrow-left"></i> Logout
            </a>
        </div>
    </div>
</body>
</html>
