<?php

declare(strict_types=1);

function base_path(string $path = ''): string
{
    $root = BASE_PATH;
    return $path === '' ? $root : $root . '/' . ltrim($path, '/');
}

function base_url(string $path = ''): string
{
    $root = rtrim(BASE_URL, '/');
    if ($path === '' || $path === '/') {
        return $root ?: '/';
    }

    return ($root ?: '') . '/' . ltrim($path, '/');
}

function asset_url(string $path): string
{
    return base_url('assets/' . ltrim($path, '/'));
}

function upload_path(string $path = ''): string
{
    $base = rtrim(UPLOAD_DIR, '/');
    return $path === '' ? $base : $base . '/' . ltrim($path, '/');
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect(string $path): void
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : base_url($path)));
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $value = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $value;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(string $token): bool
{
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function format_datetime(?string $value, string $format = 'd M Y, h:i A'): string
{
    if (!$value) {
        return '—';
    }

    $date = new DateTime($value);
    return $date->format($format);
}

function days_until(?string $date): ?int
{
    if (!$date) {
        return null;
    }

    $target = new DateTime($date);
    $today = new DateTime('today');
    return (int) $today->diff($target)->format('%r%a');
}

function to_mysql_datetime(?string $value): ?string
{
    if (!$value) {
        return null;
    }

    try {
        $date = new DateTime($value);
        return $date->format('Y-m-d H:i:s');
    } catch (Exception) {
        return null;
    }
}

function courier_logo(string $courier): string
{
    $slug = strtolower(str_replace(' ', '-', $courier));
    $file = base_path('assets/img/couriers/' . $slug . '.svg');

    if (!file_exists($file)) {
        $file = base_path('assets/img/couriers/default.svg');
    }

    $relative = str_replace(base_path(), '', $file);
    return base_url(ltrim($relative, '/'));
}

function status_badge(string $status): string
{
    $map = [
        'pending' => 'badge badge-waiting',
        'collected' => 'badge badge-done',
    ];

    $class = $map[$status] ?? 'badge';
    return '<span class="' . $class . '">' . ucfirst($status) . '</span>';
}
