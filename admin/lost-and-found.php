<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_role($pdo, 'admin');

$pageTitle = 'Lost & Found';
$activeNav = 'admin-lost';
$admin = current_user($pdo);
$formErrors = [];
$editErrors = [];
$collectErrors = [];
$searchTerm = trim($_GET['q'] ?? '');

$findLostItem = static function (PDO $pdo, int $id): array|false {
    $stmt = $pdo->prepare(
        'SELECT li.*, s.full_name AS claimer_name FROM lost_items li
         LEFT JOIN users s ON s.id = li.claimed_by
         WHERE li.id = :id'
    );
    $stmt->execute(['id' => $id]);
    return $stmt->fetch();
};

$uploadDir = upload_path('lost-and-found');
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$editItem = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    if ($editId > 0) {
        $editItem = $findLostItem($pdo, $editId);
    }
    if (!$editItem) {
        flash('error', 'Lost item not found.');
        redirect('admin/lost-and-found.php');
    }
}

$collectItem = null;
$collectModalOpen = false;
$viewItem = null;
$viewModalOpen = false;
if (isset($_GET['collect'])) {
    $collectId = (int) $_GET['collect'];
    if ($collectId > 0) {
        $collectItem = $findLostItem($pdo, $collectId);
    }
    if (!$collectItem) {
        flash('error', 'Lost item not found.');
        redirect('admin/lost-and-found.php');
    }
    $collectModalOpen = true;
}

if (isset($_GET['view'])) {
    $viewId = (int) $_GET['view'];
    if ($viewId > 0) {
        $viewItem = $findLostItem($pdo, $viewId);
    }
    if (!$viewItem) {
        flash('error', 'Lost item not found.');
        redirect('admin/lost-and-found.php');
    }
    $viewModalOpen = true;
}

if (is_post()) {
    $action = $_POST['action'] ?? 'create';
    $token = $_POST['csrf_token'] ?? '';

    $currentErrors =& $formErrors;
    if ($action === 'update') {
        $currentErrors =& $editErrors;
    } elseif ($action === 'resolve') {
        $currentErrors =& $collectErrors;
    }

    if (!verify_csrf($token)) {
        $currentErrors[] = 'Security token mismatch.';
    }

    if ($action === 'resolve') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $claimedName = trim($_POST['claimed_by_name'] ?? '');
        $claimedStudentId = trim($_POST['claimed_by_student_id'] ?? '');

        if ($itemId <= 0) {
            $currentErrors[] = 'Invalid lost item selected.';
        } else {
            if (!$collectItem || (int) $collectItem['id'] !== $itemId) {
                $collectItem = $findLostItem($pdo, $itemId) ?: null;
            }
            if (!$collectItem) {
                $currentErrors[] = 'Lost item not found.';
            } elseif (($collectItem['status'] ?? '') !== 'pending') {
                $currentErrors[] = 'Only pending items can be collected.';
            }
        }

        if (!$currentErrors) {
            $sql = 'UPDATE lost_items SET status = "collected", claimed_by = NULL, claimed_by_name = :claimer,
                    claimed_by_student_id = :claimer_id, claimed_at = NOW()
                    WHERE id = :id';
            $pdo->prepare($sql)->execute([
                'claimer' => $claimedName ?: null,
                'claimer_id' => $claimedStudentId ?: null,
                'id' => $itemId,
            ]);
            flash('success', 'Item marked as collected.');
            redirect('admin/lost-and-found.php');
        }

        $collectModalOpen = true;
    } elseif (in_array($action, ['create', 'update'], true)) {
        $isEditAction = $action === 'update';
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $activeItem = $editItem;

        if ($isEditAction) {
            if ($itemId <= 0) {
                $currentErrors[] = 'Invalid lost item reference.';
            } else {
                if (!$activeItem || (int) $activeItem['id'] !== $itemId) {
                    $activeItem = $findLostItem($pdo, $itemId) ?: null;
                }
                if (!$activeItem) {
                    $currentErrors[] = 'Lost item not found.';
                }
            }
        }

        $itemName = trim($_POST['item_name'] ?? '');
        $foundLocation = trim($_POST['found_location'] ?? '');
        $foundAt = to_mysql_datetime($_POST['found_at'] ?? '') ?? date('Y-m-d H:i:s');
        $expiryAt = date('Y-m-d H:i:s', strtotime($foundAt . ' +6 months'));
        $description = trim($_POST['description'] ?? '');
        $storage = trim($_POST['storage_location'] ?? '');

        if ($itemName === '' || $foundLocation === '') {
            $currentErrors[] = 'Item name and location are required.';
        }

        $photoPath = $isEditAction ? ($activeItem['photo_path'] ?? null) : null;
        if (!empty($_FILES['photo']['name'])) {
            if (!is_uploaded_file($_FILES['photo']['tmp_name'])) {
                $currentErrors[] = 'Invalid upload attempt.';
            } else {
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                $detectedType = mime_content_type($_FILES['photo']['tmp_name']);
                if (!in_array($detectedType, $allowed, true)) {
                    $currentErrors[] = 'Unsupported image type.';
                } else {
                    $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $filename = 'lost_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . strtolower($extension);
                    $target = $uploadDir . '/' . $filename;
                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
                        $currentErrors[] = 'Failed to upload image.';
                    } else {
                        $photoPath = 'uploads/lost-and-found/' . $filename;
                    }
                }
            }
        }

        if (!$currentErrors) {
            $payload = [
                'item_name' => $itemName,
                'description' => $description,
                'found_location' => $foundLocation,
                'found_at' => $foundAt,
                'expiry_at' => $expiryAt,
                'photo_path' => $photoPath,
                'storage_location' => $storage,
            ];

            if ($isEditAction) {
                $payload['id'] = $itemId;
                $sql = 'UPDATE lost_items SET item_name = :item_name, description = :description, found_location = :found_location,
                        found_at = :found_at, expiry_at = :expiry_at, photo_path = :photo_path, storage_location = :storage_location
                        WHERE id = :id';
                $pdo->prepare($sql)->execute($payload);
                flash('success', 'Lost item updated.');
            } else {
                $payload['recorded_by'] = $admin['id'];
                $sql = 'INSERT INTO lost_items (item_name, description, found_location, found_at, expiry_at, photo_path, storage_location, recorded_by)
                        VALUES (:item_name, :description, :found_location, :found_at, :expiry_at, :photo_path, :storage_location, :recorded_by)';
                $pdo->prepare($sql)->execute($payload);
                flash('success', 'Lost item recorded.');
            }

            redirect('admin/lost-and-found.php');
        }

        if ($isEditAction && $activeItem) {
            $editItem = $activeItem;
        }
    }
}

$itemSql = 'SELECT li.*, s.full_name AS claimer_name FROM lost_items li
            LEFT JOIN users s ON s.id = li.claimed_by';
$itemParams = [];

if ($searchTerm !== '') {
    $itemSql .= ' WHERE li.item_name LIKE :term OR li.description LIKE :term OR li.found_location LIKE :term';
    $itemParams['term'] = '%' . $searchTerm . '%';
}

$itemSql .= ' ORDER BY li.created_at DESC';

$itemStatement = $pdo->prepare($itemSql);
$itemStatement->execute($itemParams);
$items = $itemStatement->fetchAll();

$lastAction = $_POST['action'] ?? null;
$oldCreateValue = static fn(string $key) => ($lastAction === 'create') ? ($_POST[$key] ?? '') : '';
$oldEditValue = static fn(string $key) => ($lastAction === 'update') ? ($_POST[$key] ?? ($editItem[$key] ?? '')) : ($editItem[$key] ?? '');
$oldCollectValue = function (string $key) use ($lastAction, $collectItem) {
    if ($lastAction === 'resolve') {
        return $_POST[$key] ?? '';
    }
    return is_array($collectItem) ? ($collectItem[$key] ?? '') : '';
};
$createModalOpen = isset($_GET['create']) || ($lastAction === 'create' && $formErrors);
$editModalOpen = isset($_GET['edit']) || ($lastAction === 'update' && $editErrors);
$collectModalOpen = $collectModalOpen || isset($_GET['collect']) || ($lastAction === 'resolve' && $collectErrors);
$viewModalOpen = $viewModalOpen || isset($_GET['view']);

if ($collectModalOpen && !$collectItem) {
    $rehydrateId = (int) ($_POST['item_id'] ?? ($_GET['collect'] ?? 0));
    if ($rehydrateId > 0) {
        $collectItem = $findLostItem($pdo, (int) $rehydrateId) ?: null;
    }
}

include base_path('partials/layout-top.php');
?>
<section class="card">
    <header class="card-header">
        <div>
            <h3>Lost item list</h3>
            <?php if ($searchTerm !== ''): ?>
                <p class="muted">Showing results for "<?= e($searchTerm); ?>"</p>
            <?php else: ?>
                <p class="muted">Photos and expiry tracking.</p>
            <?php endif; ?>
        </div>
        <div class="card-header-actions">
            <form class="search-form" method="get">
                <input type="text" name="q" placeholder="Search by name or location" value="<?= e($searchTerm); ?>">
                <?php if ($searchTerm !== ''): ?>
                    <a class="button button-light" href="<?= base_url('admin/lost-and-found.php'); ?>">Clear</a>
                <?php endif; ?>
                <button type="submit" class="button button-primary">Search</button>
            </form>
            <a class="button button-primary" href="<?= base_url('admin/lost-and-found.php?create=1'); ?>">Add</a>
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
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$items): ?>
                <tr>
                    <td colspan="6" class="empty">
                        <?= $searchTerm === '' ? 'No lost items recorded.' : 'No items match your search.'; ?>
                    </td>
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
                    <td><?= e($item['found_location']); ?></td>
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
                    <td>
                        <div class="lost-table-actions">
                            <a class="button button-primary" href="<?= base_url('admin/lost-and-found.php?edit=' . $item['id']); ?>">Edit</a>
                            <?php if ($item['status'] === 'pending'): ?>
                                <a class="button button-primary" href="<?= base_url('admin/lost-and-found.php?collect=' . $item['id']); ?>">Collect</a>
                            <?php else: ?>
                                <a class="button button-primary" href="<?= base_url('admin/lost-and-found.php?view=' . $item['id']); ?>">View</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php if ($createModalOpen): ?>
    <?php
    $foundCreateRaw = $oldCreateValue('found_at');
    $foundCreateValue = $foundCreateRaw
        ? (str_contains($foundCreateRaw, 'T') ? $foundCreateRaw : date('Y-m-d\TH:i', strtotime($foundCreateRaw)))
        : date('Y-m-d\TH:i');
    ?>
    <div class="modal-overlay" id="lostCreateModal">
        <div class="modal-card">
            <header class="modal-header">
                <div>
                    <h3>Add lost item</h3>
                    <p class="muted">Document new finds with storage info.</p>
                </div>
                <a class="modal-close" href="<?= base_url('admin/lost-and-found.php'); ?>" aria-label="Close create">&times;</a>
            </header>
            <form method="post" enctype="multipart/form-data" class="form-grid">
                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                <input type="hidden" name="action" value="create">
                <?php if ($formErrors): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($formErrors as $error): ?>
                                <li><?= e($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <label>Item name
                    <input type="text" name="item_name" required value="<?= e($oldCreateValue('item_name')); ?>">
                </label>
                <label>Description
                    <textarea name="description" rows="3" placeholder="Colour, brand, identifiers"><?= e($oldCreateValue('description')); ?></textarea>
                </label>
                <label>Found location
                    <input type="text" name="found_location" required value="<?= e($oldCreateValue('found_location')); ?>">
                </label>
                <label>Storage location
                    <input type="text" name="storage_location" value="<?= e($oldCreateValue('storage_location')); ?>" placeholder="Cabinet A2">
                </label>
                <label>Found at
                    <input type="datetime-local" name="found_at" value="<?= e($foundCreateValue); ?>" data-offset-months="6" data-offset-target="#lost-create-expiry-preview">
                </label>
                <p class="auto-hint">
                    Item expiry auto-set to <span id="lost-create-expiry-preview" data-default-text="Auto set 6 months after found time">Auto set 6 months after found time</span>
                </p>
                <label>Photo
                    <input type="file" name="photo" accept="image/*">
                </label>
                <button class="button button-primary" type="submit">Save item</button>
                <a class="button button-light" href="<?= base_url('admin/lost-and-found.php'); ?>">Cancel</a>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($editModalOpen): ?>
    <?php
    $foundEditRaw = $oldEditValue('found_at');
    $foundEditValue = $foundEditRaw
        ? (str_contains($foundEditRaw, 'T') ? $foundEditRaw : date('Y-m-d\TH:i', strtotime($foundEditRaw)))
        : date('Y-m-d\TH:i');
    $expiryEditRaw = $oldEditValue('expiry_at') ?: ($editItem['expiry_at'] ?? null);
    $expiryEditPreview = $expiryEditRaw ? format_datetime($expiryEditRaw) : 'Auto set 6 months after found time';
    $editItemId = (int) ($editItem['id'] ?? $_POST['item_id'] ?? 0);
    ?>
    <div class="modal-overlay" id="lostEditModal">
        <div class="modal-card">
            <header class="modal-header">
                <div>
                    <h3>Edit lost item</h3>
                    <p class="muted">Update descriptions or storage info.</p>
                </div>
                <a class="modal-close" href="<?= base_url('admin/lost-and-found.php'); ?>" aria-label="Close edit">&times;</a>
            </header>
            <form method="post" enctype="multipart/form-data" class="form-grid">
                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="item_id" value="<?= $editItemId; ?>">
                <?php if ($editErrors): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($editErrors as $error): ?>
                                <li><?= e($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <label>Item name
                    <input type="text" name="item_name" required value="<?= e($oldEditValue('item_name')); ?>">
                </label>
                <label>Description
                    <textarea name="description" rows="3" placeholder="Colour, brand, identifiers"><?= e($oldEditValue('description')); ?></textarea>
                </label>
                <label>Found location
                    <input type="text" name="found_location" required value="<?= e($oldEditValue('found_location')); ?>">
                </label>
                <label>Storage location
                    <input type="text" name="storage_location" value="<?= e($oldEditValue('storage_location')); ?>" placeholder="Cabinet A2">
                </label>
                <label>Found at
                    <input type="datetime-local" name="found_at" value="<?= e($foundEditValue); ?>" data-offset-months="6" data-offset-target="#lost-edit-expiry-preview">
                </label>
                <p class="auto-hint">
                    Item expiry auto-set to <span id="lost-edit-expiry-preview" data-default-text="<?= e($expiryEditPreview); ?>"><?= e($expiryEditPreview); ?></span>
                </p>
                <label>Photo
                    <input type="file" name="photo" accept="image/*">
                </label>
                <?php if (!empty($editItem['photo_path'])): ?>
                    <p class="muted">Current photo retained unless a new one is uploaded.</p>
                <?php endif; ?>
                <button class="button button-primary" type="submit">Save changes</button>
                <a class="button button-light" href="<?= base_url('admin/lost-and-found.php'); ?>">Cancel</a>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($collectModalOpen && $collectItem): ?>
    <div class="modal-overlay" id="lostCollectModal">
        <div class="modal-card">
            <header class="modal-header">
                <div>
                    <h3>Mark as collected</h3>
                    <p class="muted"><?= e($collectItem['item_name']); ?> · <?= format_datetime($collectItem['found_at']); ?></p>
                </div>
                <a class="modal-close" href="<?= base_url('admin/lost-and-found.php'); ?>" aria-label="Close collect">&times;</a>
            </header>
            <form method="post" class="form-grid">
                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                <input type="hidden" name="action" value="resolve">
                <input type="hidden" name="item_id" value="<?= (int) $collectItem['id']; ?>">
                <?php if ($collectErrors): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($collectErrors as $error): ?>
                                <li><?= e($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <p class="muted">
                    Found at <?= e($collectItem['found_location']); ?> · Stored <?= e($collectItem['storage_location'] ?? '—'); ?>
                </p>
                <label>Claimer name
                    <input type="text" name="claimed_by_name" placeholder="As per ID" value="<?= e($oldCollectValue('claimed_by_name')); ?>">
                </label>
                <label>Claimer ID
                    <input type="text" name="claimed_by_student_id" placeholder="S1234567" value="<?= e($oldCollectValue('claimed_by_student_id')); ?>">
                </label>
                <button class="button button-primary" type="submit">Mark collected</button>
                <a class="button button-light" href="<?= base_url('admin/lost-and-found.php'); ?>">Cancel</a>
            </form>
        </div>
    </div>
<?php endif; ?>
<?php if ($viewModalOpen && $viewItem): ?>
    <div class="modal-overlay" id="lostViewModal">
        <div class="modal-card">
            <header class="modal-header">
                <div>
                    <h3>Collection details</h3>
                    <p class="muted"><?= e($viewItem['item_name']); ?> · <?= format_datetime($viewItem['found_at']); ?></p>
                </div>
                <a class="modal-close" href="<?= base_url('admin/lost-and-found.php'); ?>" aria-label="Close view">&times;</a>
            </header>
            <div class="lost-info">
                <dl>
                    <dt>Claimer name</dt>
                    <dd><?= e($viewItem['claimed_by_name'] ?? 'Not recorded'); ?></dd>
                    <dt>Claimer ID</dt>
                    <dd><?= e($viewItem['claimed_by_student_id'] ?? 'Not recorded'); ?></dd>
                    <dt>Collected at</dt>
                    <dd><?= $viewItem['claimed_at'] ? format_datetime($viewItem['claimed_at']) : 'Not recorded'; ?></dd>
                    <dt>Status</dt>
                    <dd><?= status_badge($viewItem['status']); ?></dd>
                </dl>
                <?php if (!empty($viewItem['claimer_name'])): ?>
                    <p class="muted">Linked student: <?= e($viewItem['claimer_name']); ?></p>
                <?php endif; ?>
                <?php if (!empty($viewItem['storage_location'])): ?>
                    <p class="muted">Stored at <?= e($viewItem['storage_location']); ?> before collection.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php include base_path('partials/layout-bottom.php'); ?>
