<?php
declare(strict_types=1);

require_auth();
track_registrations_table_ensure();
track_subjects_table_ensure();

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

$level = query_string('class_level');
$room = query_string('room');
$q = query_string('q');
$studentCode = query_string('student_code');

$error = null;
$success = flash_get('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = input_string('action');

    $back = '/tracks/?route=student_track&year_id=' . $yearId;
    if ($level !== '') $back .= '&class_level=' . rawurlencode($level);
    if ($room !== '') $back .= '&room=' . rawurlencode($room);
    if ($q !== '') $back .= '&q=' . rawurlencode($q);
    if ($studentCode !== '') $back .= '&student_code=' . rawurlencode($studentCode);

    try {
        if ($action === 'delete_one') {
            $id = (int)input_string('id');
            track_reg_delete_by_id($id);
            flash_set('success', 'ลบรายการแล้ว');
            header('Location: ' . $back);
            exit;
        }

        throw new RuntimeException('คำสั่งไม่ถูกต้อง');
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'เกิดข้อผิดพลาด';
    }
}

$classLevelOptions = [
    '' => 'ทั้งหมด (ม.4-ม.6)',
    'ม.4' => 'ม.4',
    'ม.5' => 'ม.5',
    'ม.6' => 'ม.6',
];

$students = [];
if ($yearId > 0) {
    $students = school_students_for_track($yearId, $level, $room, $q, 500);
}

$student = null;
$studentRegs = [];
if ($yearId > 0 && $studentCode !== '') {
    $student = school_student_get($yearId, $studentCode);
    if ($student) {
        $studentRegs = track_registrations_for_student($yearId, (string)$student['student_code']);
    }
}

echo render('student_track/index', [
    'title' => 'ดู Track รายคน',
    'years' => $years,
    'yearId' => $yearId,
    'filters' => [
        'class_level' => $level,
        'room' => $room,
        'q' => $q,
        'student_code' => $studentCode,
    ],
    'classLevelOptions' => $classLevelOptions,
    'students' => $students,
    'student' => $student,
    'studentRegs' => $studentRegs,
    'error' => $error,
    'success' => $success,
]);
