<?php
session_start();
require_once 'includes/mysql/conn.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // --- USERNAME VALIDATION: Check if username already exists in database ---
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            $error = 'Username is already taken.';
        } else {
            // Hash the password securely
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user into the database
            $insert_stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            if ($insert_stmt->execute([$username, $hashed_password])) {
                // Redirect to login with success message
                header('Location: index.php?msg=registered');
                exit();
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow - Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 h-full flex items-center justify-center">
    <div class="max-w-md w-full mx-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl p-8">
        <div class="text-center mb-6">
            <div class="inline-flex bg-indigo-600 text-white p-3 rounded-xl shadow-sm mb-2">
                <i class="fa-solid fa-user-plus text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Create an Account</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Register to manage your tasks efficiently</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-400 px-4 py-3 rounded-xl text-sm font-medium">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Username</label>
                <input type="text" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" placeholder="enter username here">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" placeholder="enter password here">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Confirm Password</label>
                <input type="password" name="confirm_password" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" placeholder="confirm password here">
            </div>
            <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition shadow-sm">Sign Up</button>
        </form>

        <p class="text-center text-sm text-slate-500 dark:text-slate-400 mt-6">
            Already have an account? <a href="index.php" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">Log in</a>
        </p>
    </div>
</body>
</html>