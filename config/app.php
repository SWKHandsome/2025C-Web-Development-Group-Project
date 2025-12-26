<?php

declare(strict_types=1);

const APP_NAME = 'Campus Parcel & Lost & Found';
const BASE_PATH = __DIR__ . '/..';
const UPLOAD_DIR = BASE_PATH . '/uploads';
const SESSION_COOKIE_NAME = 'campus_portal_session';

$baseUrl = getenv('APP_BASE_URL');

if ($baseUrl === false || $baseUrl === '') {
	$projectPath = str_replace('\\', '/', realpath(BASE_PATH) ?: '');
	$documentRoot = isset($_SERVER['DOCUMENT_ROOT'])
		? str_replace('\\', '/', realpath((string) $_SERVER['DOCUMENT_ROOT']) ?: '')
		: '';

	if ($projectPath !== '' && $documentRoot !== '' && strpos($projectPath, $documentRoot) === 0) {
		$relativePath = trim(substr($projectPath, strlen($documentRoot)), '/');

		if ($relativePath !== '') {
			$segments = array_filter(explode('/', $relativePath), 'strlen');
			$encodedSegments = array_map('rawurlencode', $segments);
			$baseUrl = '/' . implode('/', $encodedSegments);
		} else {
			$baseUrl = '';
		}
	} else {
		$baseUrl = '';
	}
} else {
	$baseUrl = rtrim($baseUrl, '/');

	if ($baseUrl !== '' && $baseUrl[0] !== '/') {
		$baseUrl = '/' . $baseUrl;
	}
}

define('BASE_URL', $baseUrl);

date_default_timezone_set('Asia/Kuala_Lumpur');
