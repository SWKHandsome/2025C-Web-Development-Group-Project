<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

if (session_name() !== (SESSION_COOKIE_NAME ?? session_name())) {
	session_name(SESSION_COOKIE_NAME ?? 'campus_session');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/auth.php';
