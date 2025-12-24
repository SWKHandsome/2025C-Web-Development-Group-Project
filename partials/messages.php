<?php
$success = flash('success');
$error = flash('error');
?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error); ?></div>
<?php endif; ?>
