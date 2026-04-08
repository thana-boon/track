<?php
declare(strict_types=1);

require_auth();
track_class_tables_ensure();

$sessionId = (int)query_string('id');
$returnDate = (string)query_string('return_date');
$returnYear = (int)query_string('return_year');
$session = track_class_session_get($sessionId);

if (!$session) {
    http_response_code(404);
    echo render('errors/404', [
        'title' => 'ไม่พบรอบเรียน',
        'path' => 'class_attendance_view',
    ]);
    exit;
}

$term = (int)($session['term'] ?? term_from_request(track_active_term()));
$term = ($term === 2) ? 2 : 1;

$error = null;
$success = flash_get('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = input_string('action');

    try {
        if ($action === 'save_check') {
            $attend = $_POST['attend'] ?? [];
            $result = $_POST['result'] ?? [];
            if (!is_array($attend)) $attend = [];
            if (!is_array($result)) $result = [];

            $stats = track_class_save_check($sessionId, $attend, $result);
            flash_set('success', 'บันทึกแล้ว • อัปเดต ' . $stats['updated'] . ' รายการ • เพิ่มเข้าประวัติ (ผ่าน) ' . $stats['passed_added'] . ' รายการ');

            $qs = http_build_query(array_filter([
                'id' => $sessionId,
                'term' => $term,
                'return_date' => $returnDate,
                'return_year' => $returnYear,
            ], static fn($v) => $v !== '' && $v !== 0));
            header('Location: /tracks/class_attendance_view' . ($qs !== '' ? ('?' . $qs) : ''));
            exit;
        }

        throw new RuntimeException('คำสั่งไม่ถูกต้อง');
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'เกิดข้อผิดพลาด';
    }
}

$roster = track_class_roster($sessionId);

$csrf = csrf_token();

// A small note for the UI.
$passNote = 'หมายเหตุ: เมื่อตั้งผลเป็น “ผ่าน” ระบบจะเพิ่มวิชานี้เข้าในประวัติ (หน้า ดูรายคน/Transcript) ให้โดยอัตโนมัติ';

echo render('class_attendance/view', [
    'title' => 'เช็คชื่อ: ' . (string)$session['subject_title'],
    'csrf' => $csrf,
    'session' => $session,
    'roster' => $roster,
    'term' => $term,
    'returnDate' => $returnDate,
    'returnYear' => $returnYear,
    'error' => $error,
    'success' => $success,
    'passNote' => $passNote,
]);
