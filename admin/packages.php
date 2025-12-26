<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_role($pdo, 'admin');

$pageTitle = 'Parcels';
$activeNav = 'admin-packages';
$admin = current_user($pdo);
$formErrors = [];
$collectErrors = [];
$editErrors = [];
$searchTerm = trim($_GET['q'] ?? '');

$findPackage = static function (PDO $pdo, int $id): array|false {
    $lookup = $pdo->prepare('SELECT * FROM packages WHERE id = :id');
    $lookup->execute(['id' => $id]);
    return $lookup->fetch();
};

$editPackage = null;
if (isset($_GET['edit'])) {
    $editStatement = $pdo->prepare('SELECT * FROM packages WHERE id = :id');
    $editStatement->execute(['id' => (int) $_GET['edit']]);
    $editPackage = $editStatement->fetch();
    if (!$editPackage) {
        flash('error', 'Package not found.');
        redirect('admin/packages.php');
    }
}

$collectPackage = null;
$collectModalOpen = false;
$viewPackage = null;
$viewModalOpen = false;
if (isset($_GET['collect'])) {
    $collectId = (int) $_GET['collect'];
    if ($collectId > 0) {
        $collectPackage = $findPackage($pdo, $collectId);
    }

    if (!$collectPackage) {
        flash('error', 'Package not found.');
        redirect('admin/packages.php');
    }

    $collectModalOpen = true;
}

if (isset($_GET['view'])) {
    $viewId = (int) $_GET['view'];
    if ($viewId > 0) {
        $viewPackage = $findPackage($pdo, $viewId);
    }

    if (!$viewPackage) {
        flash('error', 'Package not found.');
        redirect('admin/packages.php');
    }

    $viewModalOpen = true;
}

if (is_post()) {
    $action = $_POST['action'] ?? 'create';
    $token = $_POST['csrf_token'] ?? '';

    if ($action === 'collect') {
        if (!verify_csrf($token)) {
            $collectErrors[] = 'Security token mismatch.';
        }

        $packageId = (int) ($_POST['package_id'] ?? 0);
        $collectorName = trim($_POST['collector_name'] ?? '');
        $collectorStudent = trim($_POST['collector_student_id'] ?? '');

        if ($packageId > 0) {
            if (!$collectPackage || (int) $collectPackage['id'] !== $packageId) {
                $collectPackage = $findPackage($pdo, $packageId);
            }

            if (!$collectPackage) {
                $collectErrors[] = 'Invalid package reference.';
            } elseif (($collectPackage['status'] ?? '') !== 'pending') {
                $collectErrors[] = 'Only pending packages can be collected.';
            }
        } else {
            $collectErrors[] = 'Invalid package reference.';
        }

        if (!$collectErrors) {
            $sql = 'UPDATE packages SET status = "collected", collected_at = NOW(), collected_by_name = :name,
                    collected_by_student_id = :student, updated_at = NOW() WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'name' => $collectorName,
                'student' => $collectorStudent,
                'id' => $packageId,
            ]);
            flash('success', 'Package marked as collected.');
            redirect('admin/packages.php');
        }

        $collectModalOpen = true;
    } else {
        $currentErrors =& $formErrors;
        $isEditAction = $action === 'update';
        if ($isEditAction) {
            $currentErrors =& $editErrors;
            if (!$editPackage) {
                $editPackage = ['id' => (int) ($_POST['package_id'] ?? 0)];
            }
        }

        if (!verify_csrf($token)) {
            $currentErrors[] = 'Security token mismatch.';
        }

        if (!$currentErrors) {
            $courier = $_POST['courier'] ?? 'Other';
            $allowedCouriers = ['Lalamove', 'Lazada', 'Shopee', 'Pos Laju', 'Other'];
            if (!in_array($courier, $allowedCouriers, true)) {
                $courier = 'Other';
            }

            $recipientName = trim($_POST['recipient_name'] ?? '');
            $tracking = trim($_POST['tracking_number'] ?? '');
            $arrival = to_mysql_datetime($_POST['arrival_at'] ?? '') ?? date('Y-m-d H:i:s');
            $deadline = date('Y-m-d H:i:s', strtotime($arrival . ' +6 months'));

            if ($recipientName === '' || $tracking === '') {
                $currentErrors[] = 'Recipient name and tracking number are required.';
            }

            if (!$currentErrors) {
                $payload = [
                    'recipient_name' => $recipientName,
                    'tracking_number' => $tracking,
                    'parcel_code' => trim($_POST['parcel_code'] ?? ''),
                    'courier' => $courier,
                    'arrival_at' => $arrival,
                    'deadline_at' => $deadline,
                    'shelf_code' => trim($_POST['shelf_code'] ?? ''),
                    'notes' => trim($_POST['notes'] ?? ''),
                ];

                if ($action === 'create') {
                    $payload['recorded_by'] = $admin['id'];
                        $sql = 'INSERT INTO packages (recipient_name, tracking_number, parcel_code, courier, arrival_at, deadline_at, shelf_code, notes, recorded_by)
                            VALUES (:recipient_name, :tracking_number, :parcel_code, :courier, :arrival_at, :deadline_at, :shelf_code, :notes, :recorded_by)';
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($payload);
                    flash('success', 'Package recorded successfully.');
                } else {
                    $payload['id'] = (int) ($_POST['package_id'] ?? 0);
                        $sql = 'UPDATE packages SET recipient_name = :recipient_name, tracking_number = :tracking_number,
                            parcel_code = :parcel_code, courier = :courier, arrival_at = :arrival_at, deadline_at = :deadline_at,
                            shelf_code = :shelf_code, notes = :notes WHERE id = :id';
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($payload);
                    flash('success', 'Package updated successfully.');
                }

                redirect('admin/packages.php');
            }
        }
    }
}

$packageSql = 'SELECT * FROM packages';
$packageParams = [];

if ($searchTerm !== '') {
    $packageSql .= ' WHERE tracking_number LIKE :term OR recipient_name LIKE :term OR collected_by_name LIKE :term OR collected_by_student_id LIKE :term';
    $packageParams['term'] = '%' . $searchTerm . '%';
}

$packageSql .= ' ORDER BY created_at DESC';

$packageStatement = $pdo->prepare($packageSql);
$packageStatement->execute($packageParams);
$packages = $packageStatement->fetchAll();

$lastAction = $_POST['action'] ?? null;
$oldCreateValue = static fn(string $key) => ($lastAction === 'create') ? ($_POST[$key] ?? '') : '';
$oldEditValue = static fn(string $key) => ($lastAction === 'update') ? ($_POST[$key] ?? ($editPackage[$key] ?? '')) : ($editPackage[$key] ?? '');
$oldCollectValue = static fn(string $key) => ($lastAction === 'collect') ? ($_POST[$key] ?? '') : '';
$createModalOpen = isset($_GET['create']) || ($lastAction === 'create' && $formErrors);
$editModalOpen = isset($_GET['edit']) || ($lastAction === 'update' && $editErrors);
$collectModalOpen = $collectModalOpen || ($lastAction === 'collect' && $collectErrors);
$viewModalOpen = $viewModalOpen || isset($_GET['view']);

if ($collectModalOpen && !$collectPackage) {
    $rehydrateId = (int) ($_POST['package_id'] ?? 0);
    if ($rehydrateId > 0) {
        $collectPackage = $findPackage($pdo, $rehydrateId) ?: null;
    }
}

include base_path('partials/layout-top.php');
?>
<section class="card">
    <header class="card-header">
        <div>
            <h3>Parcel list</h3>
            <?php if ($searchTerm !== ''): ?>
                <p class="muted">Showing results for "<?= e($searchTerm); ?>"</p>
            <?php else: ?>
                <p class="muted">Full listing for students and auditors.</p>
            <?php endif; ?>
        </div>
        <div class="card-header-actions">
            <form class="search-form" method="get">
                <input type="text" name="q" placeholder="Search by tracking no. or name" value="<?= e($searchTerm); ?>">
                <?php if ($searchTerm !== ''): ?>
                    <a class="button button-light" href="<?= base_url('admin/packages.php'); ?>">Clear</a>
                <?php endif; ?>
                <button type="submit" class="button button-primary">Search</button>
            </form>
            <a class="button button-primary" href="<?= base_url('admin/packages.php?create=1'); ?>">Add</a>
        </div>
    </header>
    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>Courier</th>
                <th>Tracking</th>
                <th>Recipient</th>
                <th>Arrival</th>
                <th>Deadline</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$packages): ?>
                <tr>
                    <td colspan="7" class="empty">
                        <?= $searchTerm === '' ? 'Nothing recorded yet.' : 'No parcels match your search.'; ?>
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
                    <td>
                        <div class="table-actions">
                            <a class="button button-primary" href="<?= base_url('admin/packages.php?edit=' . $parcel['id']); ?>">Edit</a>
                            <?php if ($parcel['status'] === 'pending'): ?>
                                <a class="button button-primary" href="<?= base_url('admin/packages.php?collect=' . $parcel['id']); ?>">Collect</a>
                            <?php else: ?>
                                <a class="button button-primary" href="<?= base_url('admin/packages.php?view=' . $parcel['id']); ?>">View</a>
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
    $selectedCreateCourier = $oldCreateValue('courier') ?: 'Lalamove';
    $arrivalCreateRaw = $oldCreateValue('arrival_at');
    $arrivalCreateValue = $arrivalCreateRaw
        ? (str_contains($arrivalCreateRaw, 'T') ? $arrivalCreateRaw : date('Y-m-d\TH:i', strtotime($arrivalCreateRaw)))
        : date('Y-m-d\TH:i');
    ?>
    <div class="modal-overlay" id="packageCreateModal">
        <div class="modal-card">
            <header class="modal-header">
                <div>
                    <h3>Add package</h3>
                    <p class="muted">Capture parcel arrivals and storage data.</p>
                </div>
                <                USE `web-development`;
                SOURCE "D:/XAMPP/htdocs/Web Development/2025C-Web-Development-Group-Project/database.sql";a class="modal-close" href="<?= base_url('admin/packages.php'); ?>" aria-label="Close create">&times;</a>
            </header>
            <form method="post" class="form-grid">
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
                <label>Recipient name
                    <input type="text" name="recipient_name" required value="<?= e($oldCreateValue('recipient_name')); ?>">
                </label>
                <label>Tracking number
                    <input type="text" name="tracking_number" required value="<?= e($oldCreateValue('tracking_number')); ?>">
                </label>
                <label>Parcel code
                    <input type="text" name="parcel_code" value="<?= e($oldCreateValue('parcel_code')); ?>" placeholder="Internal reference">
                </label>
                <label>Courier
                    <select name="courier">
                        <?php foreach (['Lalamove', 'Lazada', 'Shopee', 'Pos Laju', 'Other'] as $courierOption): ?>
                            <option value="<?= $courierOption; ?>" <?= ($selectedCreateCourier === $courierOption) ? 'selected' : ''; ?>><?= $courierOption; ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Arrival time
                    <input type="datetime-local" name="arrival_at" value="<?= e($arrivalCreateValue); ?>" data-offset-months="6" data-offset-target="#package-create-deadline-preview">
                </label>
                <p class="auto-hint">
                    Deadline auto-set to <span id="package-create-deadline-preview" data-default-text="Auto set 6 months after arrival time">Auto set 6 months after arrival time</span>
                </p>
                <label>Shelf / zone
                    <input type="text" name="shelf_code" value="<?= e($oldCreateValue('shelf_code')); ?>" placeholder="e.g. Locker B-12">
                </label>
                <label>Notes
                    <textarea name="notes" rows="3" placeholder="Special instructions"><?= e($oldCreateValue('notes')); ?></textarea>
                </label>
                <button class="button button-primary" type="submit">Save package</button>
                <a class="button button-light" href="<?= base_url('admin/packages.php'); ?>">Cancel</a>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($editModalOpen): ?>
    <?php
    $selectedEditCourier = $oldEditValue('courier') ?: 'Lalamove';
    $arrivalEditRaw = $oldEditValue('arrival_at');
    $arrivalEditValue = $arrivalEditRaw
        ? (str_contains($arrivalEditRaw, 'T') ? $arrivalEditRaw : date('Y-m-d\TH:i', strtotime($arrivalEditRaw)))
        : date('Y-m-d\TH:i');
    $deadlineEdit = $oldEditValue('deadline_at') ?: ($editPackage['deadline_at'] ?? null);
    $deadlineEditPreview = $deadlineEdit ? format_datetime($deadlineEdit) : 'Auto set 6 months after arrival time';
    ?>
    <div class="modal-overlay" id="packageEditModal">
        <div class="modal-card">
            <header class="modal-header">
                <div>
                    <h3>Edit package</h3>
                    <p class="muted">Update parcel details.</p>
                </div>
                <a class="modal-close" href="<?= base_url('admin/packages.php'); ?>" aria-label="Close edit">&times;</a>
            </header>
            <form method="post" class="form-grid">
                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="package_id" value="<?= (int) ($editPackage['id'] ?? $_POST['package_id'] ?? 0); ?>">
                <?php if ($editErrors): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($editErrors as $error): ?>
                                <li><?= e($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <label>Recipient name
                    <input type="text" name="recipient_name" required value="<?= e($oldEditValue('recipient_name')); ?>">
                </label>
                <label>Tracking number
                    <input type="text" name="tracking_number" required value="<?= e($oldEditValue('tracking_number')); ?>">
                </label>
                <label>Parcel code
                    <input type="text" name="parcel_code" value="<?= e($oldEditValue('parcel_code')); ?>" placeholder="Internal reference">
                </label>
                <label>Courier
                    <select name="courier">
                        <?php foreach (['Lalamove', 'Lazada', 'Shopee', 'Pos Laju', 'Other'] as $courierOption): ?>
                            <option value="<?= $courierOption; ?>" <?= ($selectedEditCourier === $courierOption) ? 'selected' : ''; ?>><?= $courierOption; ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Arrival time
                    <input type="datetime-local" name="arrival_at" value="<?= e($arrivalEditValue); ?>" data-offset-months="6" data-offset-target="#package-edit-deadline-preview">
                </label>
                <p class="auto-hint">
                    Deadline auto-set to <span id="package-edit-deadline-preview" data-default-text="<?= e($deadlineEditPreview); ?>"><?= e($deadlineEditPreview); ?></span>
                </p>
                <label>Shelf / zone
                    <input type="text" name="shelf_code" value="<?= e($oldEditValue('shelf_code')); ?>" placeholder="e.g. Locker B-12">
                </label>
                <label>Notes
                    <textarea name="notes" rows="3" placeholder="Special instructions"><?= e($oldEditValue('notes')); ?></textarea>
                </label>
                <button class="button button-primary" type="submit">Save changes</button>
                <a class="button button-light" href="<?= base_url('admin/packages.php'); ?>">Cancel</a>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($collectModalOpen && $collectPackage): ?>
    <div class="modal-overlay" id="packageCollectModal">
        <div class="modal-card">
            <header class="modal-header">
                <div>
                    <h3>Mark as collected</h3>
                    <p class="muted">Tracking <?= e($collectPackage['tracking_number']); ?> · <?= e($collectPackage['courier'] ?? 'Courier'); ?></p>
                </div>
                <a class="modal-close" href="<?= base_url('admin/packages.php'); ?>" aria-label="Close collect">&times;</a>
            </header>
            <form method="post" class="form-grid">
                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                <input type="hidden" name="action" value="collect">
                <input type="hidden" name="package_id" value="<?= (int) $collectPackage['id']; ?>">
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
                    Recipient: <?= e($collectPackage['recipient_name']); ?>
                </p>
                <label>Collector name
                    <input type="text" name="collector_name" placeholder="As per student card" value="<?= e($oldCollectValue('collector_name')); ?>">
                </label>
                <label>Student ID
                    <input type="text" name="collector_student_id" placeholder="S1234567" value="<?= e($oldCollectValue('collector_student_id')); ?>">
                </label>
                <button class="button button-primary" type="submit">Mark collected</button>
                <a class="button button-light" href="<?= base_url('admin/packages.php'); ?>">Cancel</a>
            </form>
        </div>
    </div>
<?php endif; ?>
<?php if ($viewModalOpen && $viewPackage): ?>
    <div class="modal-overlay" id="packageViewModal">
        <div class="modal-card">
            <header class="modal-header">
                <div>
                    <h3>Collection details</h3>
                    <p class="muted">Tracking <?= e($viewPackage['tracking_number']); ?> · <?= e($viewPackage['courier'] ?? 'Courier'); ?></p>
                </div>
                <a class="modal-close" href="<?= base_url('admin/packages.php'); ?>" aria-label="Close view">&times;</a>
            </header>
            <div class="lost-info">
                <dl>
                    <dt>Recipient</dt>
                    <dd><?= e($viewPackage['recipient_name']); ?></dd>
                    <dt>Collector name</dt>
                    <dd><?= e($viewPackage['collected_by_name'] ?? 'Not recorded'); ?></dd>
                    <dt>Collector ID</dt>
                    <dd><?= e($viewPackage['collected_by_student_id'] ?? 'Not recorded'); ?></dd>
                    <dt>Collected at</dt>
                    <dd><?= $viewPackage['collected_at'] ? format_datetime($viewPackage['collected_at']) : 'Not recorded'; ?></dd>
                    <dt>Status</dt>
                    <dd><?= status_badge($viewPackage['status']); ?></dd>
                </dl>
                <?php if (!empty($viewPackage['shelf_code'])): ?>
                    <p class="muted">Stored at <?= e($viewPackage['shelf_code']); ?> prior to collection.</p>
                <?php endif; ?>
                <?php if (!empty($viewPackage['notes'])): ?>
                    <p class="muted">Notes: <?= e($viewPackage['notes']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include base_path('partials/layout-bottom.php'); ?>
