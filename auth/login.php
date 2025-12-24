<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

if ($existingUser = current_user($pdo)) {
    $home = $existingUser['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php';
    redirect($home);
}

$errors = [];

if (is_post()) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($token)) {
        $errors[] = 'Security token mismatch. Please try again.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Password cannot be empty.';
    }

    if (!in_array($role, ['student', 'admin'], true)) {
        $errors[] = 'Invalid role selected.';
    }

    if (!$errors) {
        if (attempt_login($pdo, $email, $password, $role)) {
            $destination = $role === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php';
            flash('success', 'Welcome back!');
            redirect($destination);
        }

        $errors[] = 'Invalid credentials. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME); ?> · Sign in</title>
    <link rel="stylesheet" href="<?= asset_url('css/main.css'); ?>">
</head>
<body class="auth-body">
    <div class="auth-card">
        <div class="auth-brand">
            <h1>Campus Parcel</h1>
            <p>Parcel tracking &amp; lost item recovery for campus residents.</p>
        </div>
        <form method="post" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
            <h2>Login</h2>
            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if ($notice = flash('error')): ?>
                <div class="alert alert-error"><?= e($notice); ?></div>
            <?php endif; ?>
            <?php if ($notice = flash('success')): ?>
                <div class="alert alert-success"><?= e($notice); ?></div>
            <?php endif; ?>
            <label>Email address
                <input type="email" name="email" placeholder="b240110b@sc.edu.my" required value="<?= e($_POST['email'] ?? ''); ?>">
            </label>
            <label>Password
                <input type="password" name="password" placeholder="Your password" required>
            </label>
            <label>Login as</label>
            <div class="role-select">
                <label><input type="radio" name="role" value="student" <?= (($_POST['role'] ?? 'student') === 'student') ? 'checked' : ''; ?>> Student</label>
                <label><input type="radio" name="role" value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'checked' : ''; ?>> Administrator</label>
            </div>
            <button type="submit" class="button button-primary">Sign in</button>
        </form>
    </div>
</body>
</html>
