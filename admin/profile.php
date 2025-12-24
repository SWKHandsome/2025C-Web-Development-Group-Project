<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_role($pdo, 'admin');

$pageTitle = 'My Profile';
$activeNav = 'admin-profile';
$user = current_user($pdo);
$errors = [];

if (is_post()) {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        $errors[] = 'Invalid security token.';
    }

    $phone = trim($_POST['phone'] ?? '');
    $office = trim($_POST['office'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$errors) {
        $parameters = [
            'phone' => $phone,
            'office' => $office,
            'id' => $user['id'],
        ];

        $sql = 'UPDATE users SET phone = :phone, office = :office';

        if ($password !== '') {
            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
            } else {
                $sql .= ', password_hash = :password_hash';
                $parameters['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
            }
        }

        if (!$errors) {
            $sql .= ', updated_at = NOW() WHERE id = :id';
            $pdo->prepare($sql)->execute($parameters);
            flash('success', 'Profile updated.');
            redirect('admin/profile.php');
        }
    }
}

$user = current_user($pdo);

include base_path('partials/layout-top.php');
?>
<section class="card">
    <header class="card-header">
        <div>
            <h3>Administrator details</h3>
            <p class="muted">Keep contact info current for audit trails.</p>
        </div>
    </header>
    <div class="profile-grid">
        <div>
            <p><strong>Name:</strong> <?= e($user['full_name']); ?></p>
            <p><strong>Staff ID:</strong> <?= e($user['staff_id'] ?? '—'); ?></p>
            <p><strong>Email:</strong> <?= e($user['email']); ?></p>
        </div>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <label>Phone number
                <input type="text" name="phone" value="<?= e($user['phone'] ?? ''); ?>">
            </label>
            <label>Office / Desk
                <input type="text" name="office" value="<?= e($user['office'] ?? ''); ?>" placeholder="Parcel room 01">
            </label>
            <label>New password
                <input type="password" name="password" placeholder="Leave blank to keep current password">
            </label>
            <button class="button button-primary" type="submit">Save changes</button>
        </form>
    </div>
</section>
<?php include base_path('partials/layout-bottom.php'); ?>
