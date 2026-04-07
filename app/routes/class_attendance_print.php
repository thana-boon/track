<?php
declare(strict_types=1);

require_auth();
track_class_tables_ensure();

$sessionId = (int)query_string('id');
$session = track_class_session_get($sessionId);

if (!$session) {
    http_response_code(404);
    echo render('errors/404', [
        'title' => 'ไม่พบรอบเรียน',
        'path' => 'class_attendance_print',
    ]);
    exit;
}

$cols = (int)query_string('cols');
if ($cols <= 0) {
    $cols = 6;
}
$cols = max(5, min(10, $cols));

$roster = track_class_roster($sessionId);

header('Content-Type: text/html; charset=utf-8');
echo render_plain('class_attendance/print', [
    'title' => 'ใบเช็คชื่อ',
    'session' => $session,
    'roster' => $roster,
    'extraCols' => $cols,
]);
exit;
