<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_role($pdo, 'student');

$pageTitle = 'Collection History';
$activeNav = 'student-history';
$student = current_user($pdo);

$historyStatement = $pdo->prepare(
    'SELECT p.*, recorder.full_name AS recorder_name FROM packages p
     LEFT JOIN users recorder ON recorder.id = p.recorded_by
     WHERE p.student_id = :student AND p.status = "collected"
     ORDER BY p.collected_at DESC'
);
$historyStatement->execute(['student' => $student['id']]);
$history = $historyStatement->fetchAll();

include base_path('partials/layout-top.php');
?>
<section class="card">
    <header class="card-header">
        <div>
            <h3>Collected parcels</h3>
            <p class="muted">Proof of previous pickups for quick reference.</p>
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
                <th>Collected on</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$history): ?>
                <tr>
                    <td colspan="6" class="empty">No collection history available.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($history as $parcel): ?>
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
                        <strong><?= e($student['full_name']); ?></strong>
                        <p class="muted">ID: <?= e($student['student_id'] ?? '—'); ?></p>
                    </td>
                    <td><?= format_datetime($parcel['arrival_at']); ?></td>
                    <td><?= format_datetime($parcel['collected_at']); ?></td>
                    <td><?= status_badge($parcel['status']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include base_path('partials/layout-bottom.php'); ?>
