<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_role($pdo, 'student');

$pageTitle = 'My Parcels';
$activeNav = 'student-dashboard';
$student = current_user($pdo);

$packageQuery = $pdo->prepare(
    'SELECT p.*, recorder.full_name AS recorder_name FROM packages p
     LEFT JOIN users recorder ON recorder.id = p.recorded_by
     WHERE p.student_id = :student
     ORDER BY p.arrival_at DESC'
);
$packageQuery->execute(['student' => $student['id']]);
$packages = $packageQuery->fetchAll();

$pending = array_filter($packages, fn($parcel) => $parcel['status'] === 'pending');
$expiringSoon = array_filter(
    $pending,
    fn($parcel) => ($days = days_until($parcel['deadline_at'])) !== null && $days >= 0 && $days <= 7
);

include base_path('partials/layout-top.php');
?>
<section class="grid stats-grid">
    <article class="stat-card">
        <p>Total parcels</p>
        <h2><?= count($packages); ?></h2>
        <small>Across the last 6 months</small>
    </article>
    <article class="stat-card">
        <p>Waiting pickup</p>
        <h2><?= count($pending); ?></h2>
        <small>Remember to collect within 6 months</small>
    </article>
    <article class="stat-card warning">
        <p>Expiring soon</p>
        <h2><?= count($expiringSoon); ?></h2>
        <small>7-day reminder window</small>
    </article>
</section>

<section class="card">
    <header class="card-header">
        <div>
            <h3>Parcel list</h3>
            <p class="muted">Track arrivals, couriers, and deadlines.</p>
        </div>
    </header>
    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>Courier</th>
                <th>Tracking no.</th>
                <th>Student</th>
                <th>Arrival</th>
                <th>Deadline</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$packages): ?>
                <tr>
                    <td colspan="6" class="empty">No parcels yet.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($packages as $parcel): ?>
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
                        <p class="muted">ID: <?= e($student['student_id'] ?? '—'); ?></p>
                    </td>
                    <td><?= format_datetime($parcel['arrival_at']); ?></td>
                    <td>
                        <?= format_datetime($parcel['deadline_at']); ?>
                        <?php $days = days_until($parcel['deadline_at']); ?>
                        <?php if ($days !== null): ?>
                            <span class="muted">(<?= $days >= 0 ? $days . ' days left' : 'expired'; ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td><?= status_badge($parcel['status']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include base_path('partials/layout-bottom.php'); ?>
