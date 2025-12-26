<?php

declare(strict_types=1);

$databaseConfig = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ? (int) getenv('DB_PORT') : 3306,
    'name' => getenv('DB_NAME') ?: 'web-development',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASS') ?: '',
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
];

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $databaseConfig['host'],
    $databaseConfig['port'],
    $databaseConfig['name'],
    $databaseConfig['charset']
);

try {
    $pdo = new PDO($dsn, $databaseConfig['user'], $databaseConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $exception) {
    exit('Database connection failed: ' . $exception->getMessage());
}
