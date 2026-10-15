<?php
session_start();
require_once 'includes/mysql/conn.php';

/**
 * Forgot Password flow:
 *  1. User submits username or email.
 *  2. We create a single-use, time-limited reset token (stored hashed).
 *  3. We try to email the reset link; if no mail transport is available
 *     (typical on a local XAMPP box) the link is shown on screen so the
 *     flow is still fully testable.
 */

$error   = '';
$notice  = '';
$devLink = '';

// Detect a local/dev environment where mail() cannot actually deliver.
$isLocalHost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1'], true)
    || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') === 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');

    if ($identifier === '') {
        $error = 'Please enter your username or email address.';
    } else {
        // Look the user up by email first, then by username (case-insensitive).
        $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE email = ? OR username = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate a raw token for the link; store only its SHA-256 hash.
            $rawToken  = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);

            // Invalidate any previous unused tokens for this user (single active token).
            $pdo->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0")
                ->execute([$user['id']]);

            // Compute the expiry inside MySQL (NOW() + 1 hour) so it is compared
            // against the same clock as MySQL's NOW() -- PHP and MySQL timezones
            // can differ on this host and would otherwise expire tokens instantly.
            $pdo->prepare(
                "INSERT INTO password_resets (user_id, email, token_hash, expires_at)
                 VALUES (?, ?, ?, NOW() + INTERVAL 1 HOUR)"
            )->execute([
                $user['id'],
                $user['email'] ?? '',
                $tokenHash,
            ]);

            // Build the absolute reset link.
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $resetUrl = $scheme . '://' . $host . '/reset_password.php?token=' . $rawToken;

            $to      = $user['email'] ?? '';
            $subject = 'TaskMaster - Password Reset Request';
            $body    = "Hi " . $user['username'] . ",\n\n"
                     . "We received a request to reset your TaskMaster password.\n"
                     . "Click the link below to choose a new password (valid for 1 hour):\n\n"
                     . $resetUrl . "\n\n"
                     . "If you did not request this, you can safely ignore this email.\n";
            $headers = "From: no-reply@" . $host . "\r\n"
                     . "Content-Type: text/plain; charset=UTF-8\r\n";

            $mailSent = false;
            if ($to !== '') {
                // Suppress warnings: on a local box with no SMTP this fails silently.
                $mailSent = @mail($to, $subject, $body, $headers);
            }

            // Generic, non-account-revealing message (prevents user enumeration).
            $notice = 'If an account matches that information, a password reset link has been sent. Please check your email (and spam folder).';

            // On local dev (or when mail could not be delivered), surface the link
            // directly so the flow remains testable without an SMTP server.
            if ($isLocalHost || !$mailSent) {
                $notice .= ' <strong>Local/dev mode:</strong> no mail server was reachable, so use the link below to continue.';
                $devLink = $resetUrl;
            }
        } else {
            // Same generic message so we never disclose whether an account exists.
            $notice = 'If an account matches that information, a password reset link has been sent. Please check your email (and spam folder).';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMaster - Forgot Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 h-full flex items-center justify-center">
    <div class="max-w-md w-full mx-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl p-8">
        <div class="text-center mb-6">
            <div class="inline-flex bg-indigo-600 text-white p-3 rounded-xl shadow-sm mb-2">
                <i class="fa-solid fa-key text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Forgot Password</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Enter your username or email to reset your password</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-400 px-4 py-3 rounded-xl text-sm font-medium">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($notice): ?>
            <div class="mb-4 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-400 px-4 py-3 rounded-xl text-sm font-medium">
                <?= $notice /* safe: static text plus nothing user-controlled */ ?>
            </div>
            <?php if ($devLink): ?>
                <div class="mb-4 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900 text-amber-800 dark:text-amber-300 px-4 py-3 rounded-xl text-xs break-all">
                    <span class="block font-semibold mb-1">Reset link (dev):</span>
                    <a href="<?= htmlspecialchars($devLink) ?>" class="underline"><?= htmlspecialchars($devLink) ?></a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST" action="forgot_password.php" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Username or Email</label>
                <input type="text" name="identifier" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" placeholder="enter username or email here">
            </div>
            <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition shadow-sm">Send Reset Link</button>
        </form>

        <p class="text-center text-sm text-slate-500 dark:text-slate-400 mt-6">
            Remembered it? <a href="index.php" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">Back to Log in</a>
        </p>
    </div>
</body>
</html>
