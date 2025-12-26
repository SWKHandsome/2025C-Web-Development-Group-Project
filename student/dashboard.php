<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_role($pdo, 'student');

$pageTitle = 'Parcels';
$activeNav = 'student-dashboard';
$student = current_user($pdo);

$searchTerm = trim($_GET['q'] ?? '');

$sql = 'SELECT p.*, recorder.full_name AS recorder_name
    FROM packages p
    LEFT JOIN users recorder ON recorder.id = p.recorded_by';

$params = [];

if ($searchTerm !== '') {
    $sql .= ' WHERE p.tracking_number LIKE :term OR p.recipient_name LIKE :term OR p.collected_by_name LIKE :term OR p.collected_by_student_id LIKE :term';
    $params['term'] = '%' . $searchTerm . '%';
}

$sql .= ' ORDER BY p.arrival_at DESC';

$packageQuery = $pdo->prepare($sql);
$packageQuery->execute($params);
$packages = $packageQuery->fetchAll();

include base_path('partials/layout-top.php');
?>
<section class="card">
    <header class="card-header">
        <div>
            <h3>Parcel list</h3>
            <?php if ($searchTerm !== ''): ?>
                <p class="muted">Showing results for "<?= e($searchTerm); ?>"</p>
            <?php endif; ?>
        </div>
        <form class="search-form" method="get">
            <input type="text" name="q" placeholder="Search by tracking no. or name" value="<?= e($searchTerm); ?>">
            <?php if ($searchTerm !== ''): ?>
                <a class="button button-light" href="<?= base_url('student/dashboard.php'); ?>">Clear</a>
            <?php endif; ?>
            <button type="submit" class="button button-primary">Search</button>
        </form>
    </header>
    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>Courier</th>
                <th>Tracking no.</th>
                <th>Recipient</th>
                <th>Arrival</th>
                <th>Deadline</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$packages): ?>
                <tr>
                    <td colspan="6" class="empty">
                        <?= $searchTerm === '' ? 'No parcels yet.' : 'No parcels match your search.'; ?>
                    </td>
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
                        <?php if ($parcel['collected_by_student_id']): ?>
                            <p class="muted">Collected ID: <?= e($parcel['collected_by_student_id']); ?></p>
                        <?php else: ?>
                            <p class="muted">Awaiting collection</p>
                        <?php endif; ?>
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
