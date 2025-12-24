<?php

declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_role($pdo, 'student');

$pageTitle = 'Lost & Found';
$activeNav = 'student-lost';

$itemsStatement = $pdo->query(
    'SELECT li.*, recorder.full_name AS recorder_name FROM lost_items li
    LEFT JOIN users recorder ON recorder.id = li.recorded_by
    ORDER BY li.status = "pending" DESC, li.found_at DESC'
);
$items = $itemsStatement->fetchAll();

include base_path('partials/layout-top.php');
?>
<section class="card">
    <header class="card-header">
        <div>
            <h3>Lost item list</h3>
            <p class="muted">Report to the counter with identification to claim your belongings.</p>
        </div>
    </header>
    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>Item</th>
                <th>Found at</th>
                <th>Location</th>
                <th>Expiry</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$items): ?>
                <tr>
                    <td colspan="5" class="empty">No lost items recorded.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($items as $item): ?>
                <?php $daysLeft = days_until($item['expiry_at']); ?>
                <tr>
                    <td>
                        <div class="lost-item-cell">
                            <?php if ($item['photo_path']): ?>
                                <img class="lost-thumb" src="<?= base_url($item['photo_path']); ?>" alt="<?= e($item['item_name']); ?> photo">
                            <?php else: ?>
                                <div class="lost-thumb lost-thumb-placeholder">No photo</div>
                            <?php endif; ?>
                            <div>
                                <strong><?= e($item['item_name']); ?></strong>
                                <p class="muted"><?= e($item['description'] ?? 'No description provided.'); ?></p>
                            </div>
                        </div>
                    </td>
                    <td><?= format_datetime($item['found_at']); ?></td>
                    <td>
                        <strong><?= e($item['found_location']); ?></strong>
                    </td>
                    <td>
                        <?= format_datetime($item['expiry_at']); ?>
                        <?php if ($daysLeft !== null): ?>
                            <?php if ($daysLeft > 0): ?>
                                <p class="muted">(<?= $daysLeft; ?> days left)</p>
                            <?php elseif ($daysLeft === 0): ?>
                                <p class="muted">(Expires today)</p>
                            <?php else: ?>
                                <p class="muted">(Expired <?= abs($daysLeft); ?> days ago)</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><?= status_badge($item['status']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include base_path('partials/layout-bottom.php'); ?>
