<?php
session_start();
require_once 'includes/mysql/conn.php';

/**
 * Reset Password page:
 *  - Validates the one-time token from the emailed link (hashed lookup + expiry + not used).
 *  - On valid POST, updates the password hash, marks the token used, and logs in.
 */

$error   = '';
$success = false;
$token   = $_GET['token'] ?? ($_POST['token'] ?? '');

// Look up the token by its hash. Invalid/expired/used tokens simply won't match.
$validToken = false;
$resetUserId = null;
if (!empty($token)) {
    $stmt = $pdo->prepare(
        "SELECT user_id FROM password_resets
         WHERE token_hash = ? AND used = 0 AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute([hash('sha256', $token)]);
    $row = $stmt->fetch();
    if ($row) {
        $validToken  = true;
        $resetUserId = (int)$row['user_id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$validToken) {
        $error = 'This reset link is invalid or has expired. Please request a new one.';
    } else {
        $password        = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            // Update the password (securely hashed).
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                ->execute([$newHash, $resetUserId]);

            // Consume the token so it can never be reused.
            $pdo->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0")
                ->execute([$resetUserId]);

            // Log the user straight in and bounce to the dashboard.
            $u = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
            $u->execute([$resetUserId]);
            $user = $u->fetch();
            if ($user) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                header('Location: dashboard.php?msg=password_reset');
                exit();
            }
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMaster - Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 h-full flex items-center justify-center">
    <div class="max-w-md w-full mx-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl p-8">
        <div class="text-center mb-6">
            <div class="inline-flex bg-indigo-600 text-white p-3 rounded-xl shadow-sm mb-2">
                <i class="fa-solid fa-lock-open text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Reset Password</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Choose a new password for your account</p>
        </div>

        <?php if (!$validToken): ?>
            <div class="mb-4 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-400 px-4 py-3 rounded-xl text-sm font-medium">
                This reset link is invalid or has expired.
            </div>
            <p class="text-center text-sm text-slate-500 dark:text-slate-400">
                <a href="forgot_password.php" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">Request a new link</a>
            </p>
        <?php else: ?>

            <?php if ($error): ?>
                <div class="mb-4 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-400 px-4 py-3 rounded-xl text-sm font-medium">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-4 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-400 px-4 py-3 rounded-xl text-sm font-medium">
                    Your password has been reset successfully.
                </div>
            <?php endif; ?>

            <form method="POST" action="reset_password.php" class="space-y-4">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">New Password</label>
                    <input type="password" name="password" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" placeholder="enter new password here">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Confirm Password</label>
                    <input type="password" name="confirm_password" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" placeholder="confirm new password here">
                </div>
                <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition shadow-sm">Reset Password</button>
            </form>

        <?php endif; ?>

        <p class="text-center text-sm text-slate-500 dark:text-slate-400 mt-6">
            <a href="index.php" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">Back to Log in</a>
        </p>
    </div>
</body>
</html>
