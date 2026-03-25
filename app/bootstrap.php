<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/config.php';
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/track_subjects.php';
require __DIR__ . '/track_register.php';
require __DIR__ . '/report_settings.php';
require __DIR__ . '/view.php';

// Basic security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

// Normalize timezone
date_default_timezone_set(APP_TIMEZONE);
