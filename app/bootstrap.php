<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/config.php';
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/auth.php';

// Idle session timeout (30 minutes)
if (isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user'])) {
	$now = time();
	$last = (int)($_SESSION['__last_activity_at'] ?? 0);

	if ($last > 0 && ($now - $last) > 30 * 60) {
		// Best-effort log
		activity_log_write('session_timeout', ['idle_seconds' => $now - $last]);
		auth_logout();
		unset($_SESSION['__last_activity_at']);
		@session_regenerate_id(true);
		flash_set('success', 'หมดเวลาใช้งาน (ไม่มีการเคลื่อนไหวเกิน 30 นาที) กรุณาเข้าสู่ระบบใหม่');
		header('Location: /tracks/login');
		exit;
	}

	$_SESSION['__last_activity_at'] = $now;
}
require __DIR__ . '/track_subjects.php';
require __DIR__ . '/track_register.php';
require __DIR__ . '/track_classes.php';
require __DIR__ . '/class_advisors.php';
require __DIR__ . '/report_settings.php';
require __DIR__ . '/backup_restore.php';
require __DIR__ . '/view.php';

// Basic security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

// Normalize timezone
date_default_timezone_set(APP_TIMEZONE);
