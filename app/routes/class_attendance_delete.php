<?php
declare(strict_types=1);

require_auth();
track_class_tables_ensure();

$me = auth_user();
$role = is_array($me) ? (string)($me['role'] ?? 'teacher') : 'teacher';
if ($role !== 'admin') {
    redirect('class_attendance');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

csrf_verify();

$sessionId = (int)input_string('session_id');
$returnDate = input_string('return_date');
$returnYear = (int)input_string('return_year');

try {
    $session = track_class_session_get($sessionId);
    if (!$session) {
        throw new RuntimeException('ไม่พบรอบเรียน');
    }

    $term = (int)($session['term'] ?? term_from_request(track_active_term()));
    $term = ($term === 2) ? 2 : 1;

    track_class_session_delete($sessionId);
    flash_set('success', 'ลบรอบเรียนแล้ว ✅');

    $yearId = (int)($session['year_id'] ?? 0);
    $sessionDate = (string)($session['session_date'] ?? '');

    $qs = http_build_query(array_filter([
        'year_id' => $returnYear > 0 ? $returnYear : $yearId,
        'term' => $term,
        'session_date' => $returnDate !== '' ? $returnDate : $sessionDate,
    ], static fn($v) => $v !== '' && $v !== 0));

    header('Location: /tracks/class_attendance' . ($qs !== '' ? ('?' . $qs) : ''));
    exit;
} catch (Throwable $e) {
    flash_set('success', 'ลบไม่สำเร็จ');
    header('Location: /tracks/class_attendance');
    exit;
}
