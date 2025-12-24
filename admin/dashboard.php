<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_role($pdo, 'admin');

$pageTitle = 'Control Center';
$activeNav = 'admin-dashboard';

$stats = [
    'totalPackages' => (int) $pdo->query('SELECT COUNT(*) FROM packages')->fetchColumn(),
    'pendingPackages' => (int) $pdo->query('SELECT COUNT(*) FROM packages WHERE status = "pending"')->fetchColumn(),
    'expiringPackages' => (int) $pdo->query('SELECT COUNT(*) FROM packages WHERE status = "pending" AND deadline_at <= DATE_ADD(NOW(), INTERVAL 7 DAY)')->fetchColumn(),
    'lostPending' => (int) $pdo->query('SELECT COUNT(*) FROM lost_items WHERE status = "pending"')->fetchColumn(),
    'lostExpiring' => (int) $pdo->query('SELECT COUNT(*) FROM lost_items WHERE status = "pending" AND expiry_at <= DATE_ADD(NOW(), INTERVAL 7 DAY)')->fetchColumn(),
];

$latestParcels = $pdo->query(
    'SELECT p.*, s.full_name AS student_name FROM packages p
     LEFT JOIN users s ON s.id = p.student_id
     ORDER BY p.created_at DESC LIMIT 5'
)->fetchAll();

$latestLost = $pdo->query(
    'SELECT * FROM lost_items ORDER BY created_at DESC LIMIT 4'
)->fetchAll();

include base_path('partials/layout-top.php');
?>
<section class="grid stats-grid">
    <article class="stat-card">
        <p>Total parcels</p>
        <h2><?= $stats['totalPackages']; ?></h2>
        <small>Recorded items overall</small>
    </article>
    <article class="stat-card">
        <p>Pending pickup</p>
        <h2><?= $stats['pendingPackages']; ?></h2>
        <small>Awaiting students</small>
    </article>
    <article class="stat-card warning">
        <p>Expiring parcels</p>
        <h2><?= $stats['expiringPackages']; ?></h2>
        <small>Within 7 days</small>
    </article>
    <article class="stat-card accent">
        <p>Lost items pending</p>
        <h2><?= $stats['lostPending']; ?></h2>
        <small><?= $stats['lostExpiring']; ?> expiring soon</small>
    </article>
</section>

<div class="grid two-columns">
    <section class="card">
        <header class="card-header">
            <div>
                <h3>Recent parcels</h3>
                <p class="muted">Latest five entries.</p>
            </div>
        </header>
        <ul class="timeline">
            <?php if (!$latestParcels): ?>
                <li class="empty">No parcels yet.</li>
            <?php endif; ?>
            <?php foreach ($latestParcels as $parcel): ?>
                <li>
                    <div class="timeline-icon">
                        <img src="<?= courier_logo($parcel['courier']); ?>" alt="<?= e($parcel['courier']); ?>">
                    </div>
                    <div>
                        <strong><?= e($parcel['tracking_number']); ?></strong>
                        <p><?= e($parcel['student_name'] ?? 'Student'); ?> • <?= format_datetime($parcel['arrival_at']); ?></p>
                    </div>
                    <?= status_badge($parcel['status']); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <section class="card">
        <header class="card-header">
            <div>
                <h3>Lost item snapshots</h3>
                <p class="muted">Most recent submissions.</p>
            </div>
        </header>
        <div class="lost-mini-grid">
            <?php if (!$latestLost): ?>
                <p class="empty">No records.</p>
            <?php endif; ?>
            <?php foreach ($latestLost as $item): ?>
                <article>
                    <?php if ($item['photo_path']): ?>
                        <img src="<?= base_url($item['photo_path']); ?>" alt="<?= e($item['item_name']); ?>">
                    <?php endif; ?>
                    <h4><?= e($item['item_name']); ?></h4>
                    <p class="muted"><?= e($item['found_location']); ?></p>
                    <span class="status-tag <?= $item['status']; ?>"><?= ucfirst($item['status']); ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<?php include base_path('partials/layout-bottom.php'); ?>
