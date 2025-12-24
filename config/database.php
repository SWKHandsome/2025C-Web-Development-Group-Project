<?php

declare(strict_types=1);

$databaseConfig = [
    'host' => 'localhost',
    'port' => 3306,
    'name' => 'web-development',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
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
