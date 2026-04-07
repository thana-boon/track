<?php
declare(strict_types=1);

require_auth();
track_class_tables_ensure();
$pdoSchool = db_school();
$years = $pdoSchool->query('SELECT id, year_be, title, is_active FROM academic_years ORDER BY year_be DESC')->fetchAll();

$defaultYearId = 0;
foreach ($years as $y) {
    if ((int)$y['is_active'] === 1) {
        $defaultYearId = (int)$y['id'];
        break;
    }
}
if ($defaultYearId === 0 && isset($years[0]['id'])) {
    $defaultYearId = (int)$years[0]['id'];
}

$yearId = (int)(query_string('year_id') !== '' ? query_string('year_id') : (string)$defaultYearId);
$yearValid = false;
foreach ($years as $y) {
    if ((int)$y['id'] === $yearId) {
        $yearValid = true;
        break;
    }
}
if (!$yearValid) {
    $yearId = $defaultYearId;
}

$sessionDate = query_string('session_date');
if ($sessionDate !== '') {
    $sessionDate = trim($sessionDate);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $sessionDate)) {
        // ok
    } elseif (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $sessionDate, $m)) {
        $d = (int)$m[1];
        $mo = (int)$m[2];
        $y = (int)$m[3];
        if ($y >= 2400) {
            $y -= 543; // พ.ศ. -> ค.ศ.
        }
        if ($y < 1900 || !checkdate($mo, $d, $y)) {
            $sessionDate = '';
        } else {
            $sessionDate = sprintf('%04d-%02d-%02d', $y, $mo, $d);
        }
    } else {
        $sessionDate = '';
    }
}

$error = null;
$success = flash_get('success');

$csrf = csrf_token();

$defaultDate = $sessionDate !== '' ? $sessionDate : date('Y-m-d');
$sessionsForDate = track_class_sessions_for_date($yearId, $defaultDate);
$ym = substr($defaultDate, 0, 7);
$sessionDatesInMonth = track_class_session_dates_in_month($yearId, $ym);

echo render('class_attendance/index', [
    'title' => 'ตารางเรียน (ตามวัน)',
    'csrf' => $csrf,
    'years' => $years,
    'yearId' => $yearId,
    'sessionDate' => $defaultDate,
    'sessionsForDate' => $sessionsForDate,
    'sessionDatesInMonth' => $sessionDatesInMonth,
    'error' => $error,
    'success' => $success,
]);

