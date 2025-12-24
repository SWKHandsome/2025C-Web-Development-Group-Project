<?php

declare(strict_types=1);

function current_user(PDO $pdo): ?array
{
    if (empty($_SESSION['user']['id'])) {
        return null;
    }

    static $cachedUser = null;

    if ($cachedUser !== null) {
        return $cachedUser;
    }

    $statement = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $_SESSION['user']['id']]);
    $cachedUser = $statement->fetch() ?: null;

    return $cachedUser;
}

function login_user(array $user): void
{
    $_SESSION['user'] = [
        'id' => $user['id'],
        'role' => $user['role'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_login(PDO $pdo): void
{
    if (!current_user($pdo)) {
        flash('error', 'Please sign in to continue.');
        redirect('auth/login.php');
    }
}

function require_role(PDO $pdo, string $role): void
{
    $user = current_user($pdo);
    if (!$user) {
        flash('error', 'Please sign in to continue.');
        redirect('auth/login.php');
    }

    if ($user['role'] !== $role) {
        flash('error', 'You do not have access to that page.');
        $target = $user['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php';
        redirect($target);
    }
}

function attempt_login(PDO $pdo, string $email, string $password, string $role): bool
{
    $statement = $pdo->prepare('SELECT * FROM users WHERE email = :email AND role = :role LIMIT 1');
    $statement->execute([
        'email' => $email,
        'role' => $role,
    ]);

    $user = $statement->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id')->execute(['id' => $user['id']]);
    login_user($user);

    return true;
}

function is_admin(PDO $pdo): bool
{
    $user = current_user($pdo);
    return $user !== null && $user['role'] === 'admin';
}

function is_student(PDO $pdo): bool
{
    $user = current_user($pdo);
    return $user !== null && $user['role'] === 'student';
}
