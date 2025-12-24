<?php
$role = $user['role'] ?? 'student';
$navConfig = [
    'student' => [
        ['key' => 'student-dashboard', 'label' => 'Packages', 'path' => 'student/dashboard.php', 'icon' => '📦'],
        ['key' => 'student-lost', 'label' => 'Lost & Found', 'path' => 'student/lost-and-found.php', 'icon' => '🪄'],
        ['key' => 'student-history', 'label' => 'History', 'path' => 'student/history.php', 'icon' => '🕘'],
        ['key' => 'student-profile', 'label' => 'Profile', 'path' => 'student/profile.php', 'icon' => '👤'],
    ],
    'admin' => [
        ['key' => 'admin-dashboard', 'label' => 'Overview', 'path' => 'admin/dashboard.php', 'icon' => '📊'],
        ['key' => 'admin-packages', 'label' => 'Parcels', 'path' => 'admin/packages.php', 'icon' => '🚚'],
        ['key' => 'admin-lost', 'label' => 'Lost & Found', 'path' => 'admin/lost-and-found.php', 'icon' => '🎒'],
        ['key' => 'admin-profile', 'label' => 'Profile', 'path' => 'admin/profile.php', 'icon' => '🧑‍💼'],
    ],
];
?>
<aside class="app-sidebar">
    <div class="brand">
        <div class="brand-logo">CP</div>
        <div>
            <p class="brand-title">Campus Parcel</p>
            <p class="brand-subtitle">Lost &amp; Found</p>
        </div>
    </div>
    <nav>
        <?php foreach ($navConfig[$role] as $item): ?>
            <a class="nav-item <?= $activeNav === $item['key'] ? 'is-active' : ''; ?>" href="<?= base_url($item['path']); ?>">
                <span class="icon"><?= $item['icon']; ?></span>
                <span><?= e($item['label']); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <p><?= e($user['full_name'] ?? 'Guest'); ?></p>
        <small class="muted"><?= ucfirst($role); ?></small>
        <a class="button button-light" href="<?= base_url('auth/logout.php'); ?>">Logout</a>
    </div>
</aside>
