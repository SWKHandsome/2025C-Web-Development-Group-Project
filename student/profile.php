<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_role($pdo, 'student');

$pageTitle = 'My Profile';
$activeNav = 'student-profile';
$user = current_user($pdo);
$errors = [];

if (is_post()) {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        $errors[] = 'Invalid security token.';
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $studentId = trim($_POST['student_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $faculty = trim($_POST['faculty'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fullName === '') {
        $errors[] = 'Name cannot be empty.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

    if (!$errors) {
        $fields = [
            'full_name' => $fullName,
            'student_id' => $studentId !== '' ? $studentId : null,
            'email' => $email,
            'phone' => $phone,
            'faculty' => $faculty,
            'id' => $user['id'],
        ];

        $sql = 'UPDATE users SET full_name = :full_name, student_id = :student_id, email = :email, phone = :phone, faculty = :faculty';

        if ($password !== '') {
            if (strlen($password) < 6) {
                $errors[] = 'Password should have at least 6 characters.';
            } else {
                $sql .= ', password_hash = :password_hash';
                $fields['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
            }
        }

        if (!$errors) {
            $sql .= ', updated_at = NOW() WHERE id = :id';
            $statement = $pdo->prepare($sql);
            $statement->execute($fields);
            flash('success', 'Profile updated successfully.');
            redirect('student/profile.php');
        }
    }
}

$user = current_user($pdo);

include base_path('partials/layout-top.php');
?>
<?php
$formVisible = !empty($errors);
$old = static fn(string $key) => $_POST[$key] ?? ($user[$key] ?? '');
?>
<section class="card">
    <header class="card-header">
        <div>
            <h3>Profile</h3>
            <p class="muted">Update your contact details to receive timely alerts.</p>
        </div>
    </header>
    <div class="profile-grid">
        <div class="profile-summary">
            <p><strong>Name:</strong> <?= e($user['full_name']); ?></p>
            <p><strong>Student ID:</strong> <?= e($user['student_id'] ?? '—'); ?></p>
            <p><strong>Email:</strong> <?= e($user['email']); ?></p>
            <p><strong>Phone:</strong> <?= e($user['phone'] ?? 'Not provided'); ?></p>
            <p><strong>Faculty:</strong> <?= e($user['faculty'] ?? 'Not provided'); ?></p>
            <button type="button" class="button button-primary" id="profileEditButton" <?= $formVisible ? 'hidden' : ''; ?>>Edit profile</button>
        </div>
        <form method="post" id="profileForm" <?= $formVisible ? '' : 'hidden'; ?>>
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
            <label>Full name
                <input type="text" name="full_name" value="<?= e($old('full_name')); ?>" required>
            </label>
            <label>Student ID
                <input type="text" name="student_id" value="<?= e($old('student_id')); ?>" placeholder="e.g. 21WMR12345">
            </label>
            <label>Email
                <input type="email" name="email" value="<?= e($old('email')); ?>" required>
            </label>
            <label>Phone number
                <input type="text" name="phone" value="<?= e($old('phone')); ?>" placeholder="e.g. 012-1234567">
            </label>
            <label>Faculty / School
                <input type="text" name="faculty" value="<?= e($old('faculty')); ?>" placeholder="Faculty of Engineering">
            </label>
            <label>New password
                <input type="password" name="password" placeholder="Leave blank to keep current password">
            </label>
            <button class="button button-primary" type="submit">Save changes</button>
        </form>
    </div>
</section>
<?php include base_path('partials/layout-bottom.php'); ?>
