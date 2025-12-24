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
            <h3>Lost item board</h3>
            <p class="muted">Report to the counter with identification to claim your belongings.</p>
        </div>
    </header>
    <div class="lost-grid">
        <?php if (!$items): ?>
            <p class="empty">No lost items recorded.</p>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
            <article class="lost-card <?= $item['status'] === 'pending' ? '' : 'is-claimed'; ?>">
                <div class="lost-photo">
                    <?php if ($item['photo_path']): ?>
                        <img src="<?= base_url($item['photo_path']); ?>" alt="<?= e($item['item_name']); ?> photo">
                    <?php else: ?>
                        <div class="placeholder">No photo</div>
                    <?php endif; ?>
                    <span class="status-tag <?= $item['status']; ?>"><?= ucfirst($item['status']); ?></span>
                </div>
                <div class="lost-info">
                    <h4><?= e($item['item_name']); ?></h4>
                    <p><?= e($item['description'] ?? 'No description provided.'); ?></p>
                    <dl>
                        <div><dt>Found</dt><dd><?= format_datetime($item['found_at']); ?></dd></div>
                        <div><dt>Location</dt><dd><?= e($item['found_location']); ?></dd></div>
                        <div><dt>Expiry</dt><dd><?= format_datetime($item['expiry_at']); ?></dd></div>
                    </dl>
                    <small class="muted">Recorded by <?= e($item['recorder_name'] ?? 'Admin'); ?></small>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php include base_path('partials/layout-bottom.php'); ?>
