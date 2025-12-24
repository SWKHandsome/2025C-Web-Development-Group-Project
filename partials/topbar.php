<?php
?>
<header class="app-topbar">
    <button class="ghost-btn" id="sidebarToggle" aria-label="Toggle menu">☰</button>
    <div>
        <p class="page-title"><?= e($pageTitle ?? 'Dashboard'); ?></p>
        <p class="muted">Welcome back, <?= e($user['full_name'] ?? 'there'); ?>.</p>
    </div>
    <div class="topbar-meta">
        <div class="meta-item">
            <span>Status</span>
            <strong><?= ucfirst($user['role'] ?? 'student'); ?></strong>
        </div>
        <div class="meta-item">
            <span>Last login</span>
            <strong><?= e($user['last_login'] ? format_datetime($user['last_login']) : 'Just now'); ?></strong>
        </div>
    </div>
</header>
