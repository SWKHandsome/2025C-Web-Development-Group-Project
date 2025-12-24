<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_role($pdo, 'admin');

$pageTitle = 'Parcels';
$activeNav = 'admin-packages';
$admin = current_user($pdo);
$formErrors = [];
$collectErrors = [];

$studentsStmt = $pdo->query('SELECT id, full_name, student_id FROM users WHERE role = "student" ORDER BY full_name');
$students = $studentsStmt->fetchAll();

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

if (is_post()) {
    $action = $_POST['action'] ?? 'create';
    $token = $_POST['csrf_token'] ?? '';

    if ($action === 'collect') {
        if (!verify_csrf($token)) {
            $collectErrors[] = 'Security token mismatch.';
        }

        if (!$collectErrors) {
            $packageId = (int) ($_POST['package_id'] ?? 0);
            $collectorName = trim($_POST['collector_name'] ?? '');
            $collectorStudent = trim($_POST['collector_student_id'] ?? '');

            if ($packageId <= 0) {
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
        }
    } else {
        if (!verify_csrf($token)) {
            $formErrors[] = 'Security token mismatch.';
        }

        if (!$formErrors) {
            $studentId = (int) ($_POST['student_id'] ?? 0);
            $studentLookup = $pdo->prepare('SELECT id FROM users WHERE id = :id AND role = "student"');
            $studentLookup->execute(['id' => $studentId]);
            if (!$studentLookup->fetch()) {
                $formErrors[] = 'Invalid student selected.';
            }

            $courier = $_POST['courier'] ?? 'Other';
            $allowedCouriers = ['Lalamove', 'Lazada', 'Shopee', 'Pos Laju', 'Other'];
            if (!in_array($courier, $allowedCouriers, true)) {
                $courier = 'Other';
            }

            $recipientName = trim($_POST['recipient_name'] ?? '');
            $tracking = trim($_POST['tracking_number'] ?? '');
            $arrival = to_mysql_datetime($_POST['arrival_at'] ?? '') ?? date('Y-m-d H:i:s');
            $deadline = to_mysql_datetime($_POST['deadline_at'] ?? '') ?? date('Y-m-d H:i:s', strtotime($arrival . ' +6 months'));

            if ($recipientName === '' || $tracking === '') {
                $formErrors[] = 'Recipient name and tracking number are required.';
            }

            if (!$formErrors) {
                $payload = [
                    'student_id' => $studentId,
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
                    $sql = 'INSERT INTO packages (student_id, recipient_name, tracking_number, parcel_code, courier, arrival_at, deadline_at, shelf_code, notes, recorded_by)
                            VALUES (:student_id, :recipient_name, :tracking_number, :parcel_code, :courier, :arrival_at, :deadline_at, :shelf_code, :notes, :recorded_by)';
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($payload);
                    flash('success', 'Package recorded successfully.');
                } else {
                    $payload['id'] = (int) ($_POST['package_id'] ?? 0);
                    $sql = 'UPDATE packages SET student_id = :student_id, recipient_name = :recipient_name, tracking_number = :tracking_number,
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

$packages = $pdo->query(
    'SELECT p.*, s.full_name AS student_name, s.student_id AS student_code FROM packages p
     LEFT JOIN users s ON s.id = p.student_id
     ORDER BY p.created_at DESC'
)->fetchAll();

$oldValue = static fn(string $key) => $_POST[$key] ?? ($editPackage[$key] ?? '');

include base_path('partials/layout-top.php');
?>
<div class="grid two-columns">
    <section class="card">
        <header class="card-header">
            <div>
                <h3><?= $editPackage ? 'Edit package' : 'Add package'; ?></h3>
                <p class="muted">Capture parcel arrivals and storage data.</p>
            </div>
        </header>
        <form method="post" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
            <input type="hidden" name="action" value="<?= $editPackage ? 'update' : 'create'; ?>">
            <?php if ($editPackage): ?>
                <input type="hidden" name="package_id" value="<?= (int) $editPackage['id']; ?>">
            <?php endif; ?>
            <?php if ($formErrors): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($formErrors as $error): ?>
                            <li><?= e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <label>Student
                <select name="student_id" required>
                    <option value="">Select student</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= $student['id']; ?>" <?= ((string) $oldValue('student_id') === (string) $student['id']) ? 'selected' : ''; ?>>
                            <?= e($student['full_name'] . ' (' . ($student['student_id'] ?? 'N/A') . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Recipient name
                <input type="text" name="recipient_name" required value="<?= e($oldValue('recipient_name')); ?>">
            </label>
            <label>Tracking number
                <input type="text" name="tracking_number" required value="<?= e($oldValue('tracking_number')); ?>">
            </label>
            <label>Parcel code
                <input type="text" name="parcel_code" value="<?= e($oldValue('parcel_code')); ?>" placeholder="Internal reference">
            </label>
            <label>Courier
                <?php $selectedCourier = $oldValue('courier') ?: 'Lalamove'; ?>
                <select name="courier">
                    <?php foreach (['Lalamove', 'Lazada', 'Shopee', 'Pos Laju', 'Other'] as $courierOption): ?>
                        <option value="<?= $courierOption; ?>" <?= ($selectedCourier === $courierOption) ? 'selected' : ''; ?>><?= $courierOption; ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Arrival time
                <?php
                $arrivalRaw = $oldValue('arrival_at');
                $arrivalValue = $arrivalRaw ? (str_contains($arrivalRaw, 'T') ? $arrivalRaw : date('Y-m-d\TH:i', strtotime($arrivalRaw))) : '';
                ?>
                <input type="datetime-local" name="arrival_at" value="<?= e($arrivalValue); ?>">
            </label>
            <label>Deadline
                <?php
                $deadlineRaw = $oldValue('deadline_at');
                $deadlineValue = $deadlineRaw ? (str_contains($deadlineRaw, 'T') ? $deadlineRaw : date('Y-m-d\TH:i', strtotime($deadlineRaw))) : '';
                ?>
                <input type="datetime-local" name="deadline_at" value="<?= e($deadlineValue); ?>">
            </label>
            <label>Shelf / zone
                <input type="text" name="shelf_code" value="<?= e($oldValue('shelf_code')); ?>" placeholder="e.g. Locker B-12">
            </label>
            <label>Notes
                <textarea name="notes" rows="3" placeholder="Special instructions"><?= e($oldValue('notes')); ?></textarea>
            </label>
            <button class="button button-primary" type="submit"><?= $editPackage ? 'Save changes' : 'Add package'; ?></button>
            <?php if ($editPackage): ?>
                <a class="button button-light" href="<?= base_url('admin/packages.php'); ?>">Cancel edit</a>
            <?php endif; ?>
        </form>
    </section>

    <section class="card">
        <header class="card-header">
            <div>
                <h3>Quick mark as collected</h3>
                <p class="muted">Capture student verification data.</p>
            </div>
        </header>
        <form method="post" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
            <input type="hidden" name="action" value="collect">
            <?php if ($collectErrors): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($collectErrors as $error): ?>
                            <li><?= e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <label>Package
                <select name="package_id" required>
                    <option value="">Select package</option>
                    <?php foreach ($packages as $parcel): ?>
                        <?php if ($parcel['status'] === 'pending'): ?>
                            <option value="<?= $parcel['id']; ?>">
                                <?= e($parcel['tracking_number'] . ' · ' . ($parcel['student_name'] ?? 'Student')); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Collector name
                <input type="text" name="collector_name" placeholder="As per student card">
            </label>
            <label>Student ID
                <input type="text" name="collector_student_id" placeholder="S1234567">
            </label>
            <button class="button button-primary" type="submit">Mark collected</button>
        </form>
    </section>
</div>

<section class="card">
    <header class="card-header">
        <div>
            <h3>All parcels</h3>
            <p class="muted">Full listing for students and auditors.</p>
        </div>
    </header>
    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>Courier</th>
                <th>Tracking</th>
                <th>Student</th>
                <th>Arrival</th>
                <th>Deadline</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$packages): ?>
                <tr><td colspan="7" class="empty">Nothing recorded yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($packages as $parcel): ?>
                <tr>
                    <td><?= e($parcel['courier']); ?></td>
                    <td>
                        <strong><?= e($parcel['tracking_number']); ?></strong>
                        <p class="muted">Ref <?= e($parcel['parcel_code'] ?? '—'); ?></p>
                    </td>
                    <td>
                        <?= e($parcel['student_name'] ?? 'Student'); ?><br>
                        <small class="muted">ID <?= e($parcel['student_code'] ?? '—'); ?></small>
                    </td>
                    <td><?= format_datetime($parcel['arrival_at']); ?></td>
                    <td><?= format_datetime($parcel['deadline_at']); ?></td>
                    <td><?= status_badge($parcel['status']); ?></td>
                    <td><a class="button button-light" href="<?= base_url('admin/packages.php?edit=' . $parcel['id']); ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include base_path('partials/layout-bottom.php'); ?>
