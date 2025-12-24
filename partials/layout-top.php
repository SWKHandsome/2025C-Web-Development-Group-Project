<?php
/** @var string $pageTitle */
/** @var string $activeNav */
/** @var PDO $pdo */

$user = current_user($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME . ' · ' . ($pageTitle ?? 'Dashboard')) ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/main.css'); ?>">
</head>
<body>
<div class="app-shell">
    <?php include base_path('partials/sidebar.php'); ?>
    <div class="app-main">
        <?php include base_path('partials/topbar.php'); ?>
        <main class="app-content">
            <?php include base_path('partials/messages.php'); ?>
