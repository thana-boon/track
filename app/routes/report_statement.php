<?php
declare(strict_types=1);

require_auth();
track_registrations_table_ensure();
track_subjects_table_ensure();
report_settings_table_ensure();

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

$yearText = '';
foreach ($years as $y) {
    if ((int)$y['id'] === $yearId) {
        // Avoid duplicated year like "2568 (2568)"; show title only.
        $yearText = trim((string)$y['title']);
        if ($yearText === '') {
            $yearText = (string)$y['year_be'];
        }
        break;
    }
}

$level = query_string('class_level');
$room = query_string('room');
$q = query_string('q');
$studentCode = query_string('student_code');
$isPrint = query_string('print') === '1';

$settings = report_settings_get();

$error = null;
$success = flash_get('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = input_string('action');

    $back = '/tracks/?route=report_statement&year_id=' . $yearId;
    if ($level !== '') $back .= '&class_level=' . rawurlencode($level);
    if ($room !== '') $back .= '&room=' . rawurlencode($room);
    if ($q !== '') $back .= '&q=' . rawurlencode($q);
    if ($studentCode !== '') $back .= '&student_code=' . rawurlencode($studentCode);

    try {
        if ($action === 'save_settings') {
            csrf_verify();

            $schoolName = input_string('school_name');
            $issuedDate = input_string('issued_date');
            $advisorName = input_string('advisor_name');
            $registrarName = input_string('registrar_name');
            $directorName = input_string('director_name');

            $issuedDate = trim($issuedDate);
            if ($issuedDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $issuedDate)) {
                throw new RuntimeException('รูปแบบวันที่ออกเอกสารไม่ถูกต้อง');
            }

            $data = [
                'school_name' => $schoolName,
                'issued_date' => $issuedDate,
                'advisor_name' => $advisorName,
                'registrar_name' => $registrarName,
                'director_name' => $directorName,
            ];

            $uploads = [
                ['key' => 'logo', 'name' => 'logo_path', 'base' => 'school_logo'],
                ['key' => 'advisor_sign', 'name' => 'advisor_sign_path', 'base' => 'sign_advisor'],
                ['key' => 'registrar_sign', 'name' => 'registrar_sign_path', 'base' => 'sign_registrar'],
                ['key' => 'director_sign', 'name' => 'director_sign_path', 'base' => 'sign_director'],
            ];

            foreach ($uploads as $u) {
                $k = (string)$u['key'];
                if (isset($_FILES[$k]) && is_array($_FILES[$k]) && (int)($_FILES[$k]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $size = (int)($_FILES[$k]['size'] ?? 0);
                    if ($size > 2 * 1024 * 1024) {
                        throw new RuntimeException('ไฟล์รูปใหญ่เกินไป (จำกัด 2MB)');
                    }
                    $path = report_image_save_from_upload($_FILES[$k], (string)$u['base']);
                    $data[(string)$u['name']] = $path;
                }
            }

            report_settings_save($data);
            flash_set('success', 'บันทึกข้อมูลรายงานแล้ว ✅');
            header('Location: ' . $back);
            exit;
        }

        if ($action === 'print_bulk') {
            csrf_verify();
            // Print many students in one A4-per-student document.
            $scope = input_string('print_scope'); // selected|all_filtered
            $codes = $_POST['student_codes'] ?? [];
            if (!is_array($codes)) {
                $codes = [];
            }

            if ($scope === 'all_filtered') {
                $list = school_students_for_track($yearId, $level, $room, $q, 2000);
                $codes = array_map(static fn($s) => (string)($s['student_code'] ?? ''), $list);
            }

            $codes = array_values(array_unique(array_filter(array_map('trim', array_map('strval', $codes)), static fn($v) => $v !== '')));
            if (count($codes) === 0) {
                throw new RuntimeException('กรุณาเลือกนักเรียนอย่างน้อย 1 คน');
            }

            $printStudents = [];
            foreach ($codes as $c) {
                $st = school_student_get($yearId, $c);
                if (!$st) {
                    continue;
                }
                $regs = track_registrations_for_student($yearId, (string)$st['student_code']);
                $printStudents[] = ['student' => $st, 'regs' => $regs];
            }

            $viewData = [
                'title' => 'ใบ Statement',
                'yearId' => $yearId,
                'yearText' => $yearText !== '' ? $yearText : (string)$yearId,
                'settings' => report_settings_get(),
                'printStudents' => $printStudents,
            ];

            header('Content-Type: text/html; charset=utf-8');
            echo render_plain('report_statement/print', $viewData);
            exit;
        }

        throw new RuntimeException('คำสั่งไม่ถูกต้อง');
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'เกิดข้อผิดพลาด';
    }
}

// Students for selecting
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

$viewData = [
    'title' => 'ใบ Statement',
    'years' => $years,
    'yearId' => $yearId,
    'yearText' => $yearText !== '' ? $yearText : (string)$yearId,
    'filters' => [
        'class_level' => $level,
        'room' => $room,
        'q' => $q,
        'student_code' => $studentCode,
    ],
    'classLevelOptions' => [
        '' => 'ทั้งหมด (ม.4-ม.6)',
        'ม.4' => 'ม.4',
        'ม.5' => 'ม.5',
        'ม.6' => 'ม.6',
    ],
    'students' => $students,
    'settings' => $settings,
    'student' => $student,
    'studentRegs' => $studentRegs,
    'error' => $error,
    'success' => $success,
];

if ($isPrint) {
    // Single-student print via GET.
    header('Content-Type: text/html; charset=utf-8');
    echo render_plain('report_statement/print', $viewData);
    exit;
}

echo render('report_statement/index', $viewData);
