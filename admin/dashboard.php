<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_role($pdo, 'admin');

$pageTitle = 'Control Center';
$activeNav = 'admin-dashboard';

$stats = [
    'pendingPackages' => (int) $pdo->query('SELECT COUNT(*) FROM packages WHERE status = "pending"')->fetchColumn(),
    'expiringPackages' => (int) $pdo->query('SELECT COUNT(*) FROM packages WHERE status = "pending" AND deadline_at <= DATE_ADD(NOW(), INTERVAL 7 DAY)')->fetchColumn(),
    'lostPending' => (int) $pdo->query('SELECT COUNT(*) FROM lost_items WHERE status = "pending"')->fetchColumn(),
    'lostExpiring' => (int) $pdo->query('SELECT COUNT(*) FROM lost_items WHERE status = "pending" AND expiry_at <= DATE_ADD(NOW(), INTERVAL 7 DAY)')->fetchColumn(),
];

$latestParcels = $pdo->query(
    'SELECT * FROM packages ORDER BY created_at DESC LIMIT 5'
)->fetchAll();

$latestLost = $pdo->query(
    'SELECT * FROM lost_items ORDER BY created_at DESC LIMIT 3'
)->fetchAll();

include base_path('partials/layout-top.php');
?>
<section class="grid stats-grid">
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

<div class="grid two-columns dashboard-columns">
    <section class="card card-tall">
        <header class="card-header">
            <div>
                <h3>Recent parcels</h3>
                <p class="muted">Latest five entries.</p>
            </div>
        </header>
        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>Courier</th>
                    <th>Tracking no.</th>
                    <th>Recipient</th>
                    <th>Arrival</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$latestParcels): ?>
                    <tr>
                        <td colspan="5" class="empty">No parcels yet.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($latestParcels as $parcel): ?>
                    <tr>
                        <td>
                            <div class="courier">
                                <img src="<?= courier_logo($parcel['courier']); ?>" alt="<?= e($parcel['courier']); ?> logo">
                                <span><?= e($parcel['courier']); ?></span>
                            </div>
                        </td>
                        <td>
                            <strong><?= e($parcel['tracking_number']); ?></strong>
                            <p class="muted">Ref: <?= e($parcel['parcel_code'] ?? '—'); ?></p>
                        </td>
                        <td>
                            <strong><?= e($parcel['recipient_name']); ?></strong>
                            <?php if ($parcel['collected_by_student_id']): ?>
                                <p class="muted">Collector ID: <?= e($parcel['collected_by_student_id']); ?></p>
                            <?php endif; ?>
                        </td>
                        <td><?= format_datetime($parcel['arrival_at']); ?></td>
                        <td><?= status_badge($parcel['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <section class="card card-tall">
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
