<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_role($pdo, 'admin');

$pageTitle = 'Lost & Found';
$activeNav = 'admin-lost';
$admin = current_user($pdo);
$errors = [];

$uploadDir = upload_path('lost-and-found');
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$editItem = null;
if (isset($_GET['edit'])) {
    $editStatement = $pdo->prepare('SELECT * FROM lost_items WHERE id = :id');
    $editStatement->execute(['id' => (int) $_GET['edit']]);
    $editItem = $editStatement->fetch();
    if (!$editItem) {
        flash('error', 'Lost item not found.');
        redirect('admin/lost-and-found.php');
    }
}

if (is_post()) {
    $action = $_POST['action'] ?? 'create';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($token)) {
        $errors[] = 'Security token mismatch.';
    }

    if (!$errors) {
        if (in_array($action, ['create', 'update'], true)) {
            $itemName = trim($_POST['item_name'] ?? '');
            $foundLocation = trim($_POST['found_location'] ?? '');
            $foundAt = to_mysql_datetime($_POST['found_at'] ?? '') ?? date('Y-m-d H:i:s');
            $expiryAt = to_mysql_datetime($_POST['expiry_at'] ?? '') ?? date('Y-m-d H:i:s', strtotime('+3 months'));
            $description = trim($_POST['description'] ?? '');
            $storage = trim($_POST['storage_location'] ?? '');

            if ($itemName === '' || $foundLocation === '') {
                $errors[] = 'Item name and location are required.';
            }

            $photoPath = $editItem['photo_path'] ?? null;
            if (!empty($_FILES['photo']['name'])) {
                if (!is_uploaded_file($_FILES['photo']['tmp_name'])) {
                    $errors[] = 'Invalid upload attempt.';
                } else {
                    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                    $detectedType = mime_content_type($_FILES['photo']['tmp_name']);
                    if (!in_array($detectedType, $allowed, true)) {
                        $errors[] = 'Unsupported image type.';
                    } else {
                        $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                        $filename = 'lost_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . strtolower($extension);
                        $target = $uploadDir . '/' . $filename;
                        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
                            $errors[] = 'Failed to upload image.';
                        } else {
                            $photoPath = 'uploads/lost-and-found/' . $filename;
                        }
                    }
                }
            }

            if (!$errors) {
                $payload = [
                    'item_name' => $itemName,
                    'description' => $description,
                    'found_location' => $foundLocation,
                    'found_at' => $foundAt,
                    'expiry_at' => $expiryAt,
                    'photo_path' => $photoPath,
                    'storage_location' => $storage,
                ];

                if ($action === 'create') {
                    $payload['recorded_by'] = $admin['id'];
                    $sql = 'INSERT INTO lost_items (item_name, description, found_location, found_at, expiry_at, photo_path, storage_location, recorded_by)
                            VALUES (:item_name, :description, :found_location, :found_at, :expiry_at, :photo_path, :storage_location, :recorded_by)';
                    $pdo->prepare($sql)->execute($payload);
                    flash('success', 'Lost item recorded.');
                } else {
                    $payload['id'] = (int) ($_POST['item_id'] ?? 0);
                    $sql = 'UPDATE lost_items SET item_name = :item_name, description = :description, found_location = :found_location,
                            found_at = :found_at, expiry_at = :expiry_at, photo_path = :photo_path, storage_location = :storage_location
                            WHERE id = :id';
                    $pdo->prepare($sql)->execute($payload);
                    flash('success', 'Lost item updated.');
                }

                redirect('admin/lost-and-found.php');
            }
        }

        if ($action === 'resolve') {
            $itemId = (int) ($_POST['item_id'] ?? 0);
            $claimer = (int) ($_POST['claimed_by'] ?? 0);

            if ($itemId <= 0) {
                $errors[] = 'Invalid lost item selected.';
            }

            if (!$errors) {
                $sql = 'UPDATE lost_items SET status = "collected", claimed_by = :claimer, claimed_at = NOW()
                        WHERE id = :id';
                $pdo->prepare($sql)->execute([
                    'claimer' => $claimer ?: null,
                    'id' => $itemId,
                ]);
                flash('success', 'Item marked as collected.');
                redirect('admin/lost-and-found.php');
            }
        }
    }
}

$items = $pdo->query('SELECT li.*, s.full_name AS claimer_name FROM lost_items li LEFT JOIN users s ON s.id = li.claimed_by ORDER BY li.created_at DESC')->fetchAll();

$students = $pdo->query('SELECT id, full_name FROM users WHERE role = "student" ORDER BY full_name')->fetchAll();

$oldLostValue = static fn(string $key) => $_POST[$key] ?? ($editItem[$key] ?? '');

include base_path('partials/layout-top.php');
?>
<div class="grid two-columns">
    <section class="card">
        <header class="card-header">
            <div>
                <h3><?= $editItem ? 'Edit lost item' : 'Record lost item'; ?></h3>
                <p class="muted">Attach photos and storage info.</p>
            </div>
        </header>
        <form method="post" enctype="multipart/form-data" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
            <input type="hidden" name="action" value="<?= $editItem ? 'update' : 'create'; ?>">
            <?php if ($editItem): ?>
                <input type="hidden" name="item_id" value="<?= (int) $editItem['id']; ?>">
            <?php endif; ?>
            <?php if ($errors && ($_POST['action'] ?? 'create') !== 'resolve'): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <label>Item name
                <input type="text" name="item_name" required value="<?= e($oldLostValue('item_name')); ?>">
            </label>
            <label>Description
                <textarea name="description" rows="3" placeholder="Colour, brand, identifiers"><?= e($oldLostValue('description')); ?></textarea>
            </label>
            <label>Found location
                <input type="text" name="found_location" required value="<?= e($oldLostValue('found_location')); ?>">
            </label>
            <label>Storage location
                <input type="text" name="storage_location" value="<?= e($oldLostValue('storage_location')); ?>" placeholder="Cabinet A2">
            </label>
            <label>Found at
                <?php
                $foundRaw = $oldLostValue('found_at');
                $foundValue = $foundRaw ? (str_contains($foundRaw, 'T') ? $foundRaw : date('Y-m-d\TH:i', strtotime($foundRaw))) : '';
                ?>
                <input type="datetime-local" name="found_at" value="<?= e($foundValue); ?>">
            </label>
            <label>Expiry
                <?php
                $expiryRaw = $oldLostValue('expiry_at');
                $expiryValue = $expiryRaw ? (str_contains($expiryRaw, 'T') ? $expiryRaw : date('Y-m-d\TH:i', strtotime($expiryRaw))) : '';
                ?>
                <input type="datetime-local" name="expiry_at" value="<?= e($expiryValue); ?>">
            </label>
            <label>Photo
                <input type="file" name="photo" accept="image/*">
            </label>
            <button class="button button-primary" type="submit"><?= $editItem ? 'Save changes' : 'Add item'; ?></button>
            <?php if ($editItem): ?>
                <a class="button button-light" href="<?= base_url('admin/lost-and-found.php'); ?>">Cancel edit</a>
            <?php endif; ?>
        </form>
    </section>

    <section class="card">
        <header class="card-header">
            <div>
                <h3>Mark as collected</h3>
                <p class="muted">Capture claimant details.</p>
            </div>
        </header>
        <form method="post" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
            <input type="hidden" name="action" value="resolve">
            <?php if ($errors && ($_POST['action'] ?? '') === 'resolve'): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <label>Lost item
                <select name="item_id" required>
                    <option value="">Select pending item</option>
                    <?php foreach ($items as $item): ?>
                        <?php if ($item['status'] === 'pending'): ?>
                            <option value="<?= $item['id']; ?>"><?= e($item['item_name']); ?> (<?= format_datetime($item['found_at']); ?>)</option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Claimed by
                <select name="claimed_by">
                    <option value="">Visitor / not student</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= $student['id']; ?>"><?= e($student['full_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="button button-primary" type="submit">Mark collected</button>
        </form>
    </section>
</div>

<section class="card">
    <header class="card-header">
        <div>
            <h3>Lost item list</h3>
            <p class="muted">Photos and expiry tracking.</p>
        </div>
    </header>
    <div class="lost-grid">
        <?php if (!$items): ?>
            <p class="empty">No lost items recorded.</p>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
            <article class="lost-card <?= $item['status'] === 'collected' ? 'is-claimed' : ''; ?>">
                <div class="lost-photo">
                    <?php if ($item['photo_path']): ?>
                        <img src="<?= base_url($item['photo_path']); ?>" alt="<?= e($item['item_name']); ?>">
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
                        <div><dt>Storage</dt><dd><?= e($item['storage_location'] ?? '—'); ?></dd></div>
                        <div><dt>Expiry</dt><dd><?= format_datetime($item['expiry_at']); ?></dd></div>
                    </dl>
                    <a class="button button-light" href="<?= base_url('admin/lost-and-found.php?edit=' . $item['id']); ?>">Edit</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php include base_path('partials/layout-bottom.php'); ?>
