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

$term = term_from_request(track_active_term());

$level = query_string('class_level'); // '', 'ม.4','ม.5','ม.6'
$room = query_string('room');
$q = query_string('q');
$page = (int)(query_string('page') !== '' ? query_string('page') : '1');
$page = max(1, $page);
$pageSize = 50;

$subjects = db_app()->query('SELECT id, title, description, is_active FROM track_subjects ORDER BY is_active DESC, title ASC')->fetchAll();

$error = null;
$success = flash_get('success');

// Ajax drag-drop assign (returns JSON)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && input_string('action') === 'assign_ajax') {
    csrf_verify();
    $subjectId = (int)input_string('subject_id');
    $studentCode = input_string('student_code');

    try {
        $inserted = track_reg_assign_bulk_for_term($yearId, $term, $subjectId, [$studentCode], 'pass');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'inserted' => $inserted], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = input_string('action');

    $back = '/tracks/register_track?year_id=' . $yearId . '&term=' . $term;
    if ($level !== '') {
        $back .= '&class_level=' . rawurlencode($level);
    }
    if ($room !== '') {
        $back .= '&room=' . rawurlencode($room);
    }
    if ($q !== '') {
        $back .= '&q=' . rawurlencode($q);
    }

    try {
        if ($action === 'bulk_assign') {
            $subjectId = (int)input_string('subject_id');
            $codes = $_POST['student_codes'] ?? [];
            if (!is_array($codes)) {
                $codes = [];
            }
            $inserted = track_reg_assign_bulk_for_term($yearId, $term, $subjectId, array_map('strval', $codes), 'pass');
            flash_set('success', 'ลงทะเบียนแล้ว ✅ เพิ่ม ' . $inserted . ' รายการ');
            header('Location: ' . $back);
            exit;
        }

        if ($action === 'bulk_unassign') {
            $subjectId = (int)input_string('subject_id');
            $codes = $_POST['student_codes'] ?? [];
            if (!is_array($codes)) {
                $codes = [];
            }
            $deleted = track_reg_unassign_bulk_for_term($yearId, $term, $subjectId, array_map('strval', $codes));
            flash_set('success', 'ลบออกแล้ว 🗑️ ' . $deleted . ' รายการ');
            header('Location: ' . $back);
            exit;
        }

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

$students = [];
$regs = [];
$regsTotal = 0;
if ($yearId > 0) {
    $students = school_students_for_track($yearId, $level, $room, $q, 300);
    $regsTotal = track_registrations_count($yearId, $term, $level, $room, $q);
    $offset = ($page - 1) * $pageSize;
    if ($offset >= $regsTotal && $regsTotal > 0) {
        $page = (int)max(1, (int)ceil($regsTotal / $pageSize));
        $offset = ($page - 1) * $pageSize;
    }
    $regs = track_registrations_list($yearId, $term, $level, $room, $q, $pageSize, $offset);
}

// HTML fragment for AJAX refresh (no layout)
if (query_string('fragment') === 'regs') {
    $filterQuery = http_build_query(array_filter([
        'year_id' => $yearId,
        'term' => $term,
        'class_level' => $level !== '' ? $level : null,
        'room' => $room !== '' ? $room : null,
        'q' => $q !== '' ? $q : null,
    ], static fn($v) => $v !== null));
    $csrf = csrf_token();
    $regsPage = $page;
    $regsPageSize = $pageSize;
    $regsTotalCount = $regsTotal;
    header('Content-Type: text/html; charset=utf-8');
    require __DIR__ . '/../views/register_track/_regs_table.php';
    exit;
}

$classLevelOptions = [
    '' => 'ทั้งหมด (ม.4-ม.6)',
    'ม.4' => 'ม.4',
    'ม.5' => 'ม.5',
    'ม.6' => 'ม.6',
];

echo render('register_track/index', [
    'title' => 'ลงทะเบียน Track',
    'years' => $years,
    'yearId' => $yearId,
    'term' => $term,
    'subjects' => $subjects,
    'students' => $students,
    'regs' => $regs,
    'regsPage' => $page,
    'regsPageSize' => $pageSize,
    'regsTotal' => $regsTotal,
    'filters' => [
        'class_level' => $level,
        'room' => $room,
        'q' => $q,
    ],
    'classLevelOptions' => $classLevelOptions,
    'error' => $error,
    'success' => $success,
]);
