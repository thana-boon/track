<?php
declare(strict_types=1);

require_auth();
track_class_tables_ensure();
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

$subjects = track_subjects_all();
$subjectId = (int)(query_string('subject_id') !== '' ? query_string('subject_id') : '0');
if ($subjectId <= 0) {
    foreach ($subjects as $s) {
        if ((int)($s['is_active'] ?? 0) === 1) {
            $subjectId = (int)$s['id'];
            break;
        }
    }
}
if ($subjectId <= 0 && isset($subjects[0]['id'])) {
    $subjectId = (int)$subjects[0]['id'];
}

$level = query_string('class_level');
$room = query_string('room');
$q = query_string('q');

$classLevelOptions = [
    '' => 'ทั้งหมด (ม.4-ม.6)',
    'ม.4' => 'ม.4',
    'ม.5' => 'ม.5',
    'ม.6' => 'ม.6',
];

$students = [];
if ($yearId > 0) {
    $students = school_students_for_track($yearId, $level, $room, $q, 800);
}

$error = null;
$success = flash_get('success');

$formSessionDate = '';
$formNote = '';
$formStudentCodesText = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = input_string('action');

    try {
        if ($action === 'create_session') {
            $sessionDate = input_string('session_date');
            $note = input_string('note');
            $subjectIdPost = (int)input_string('subject_id');
            $yearIdPost = (int)input_string('year_id');

            $formSessionDate = $sessionDate;
            $formNote = $note;

            $codes = $_POST['student_codes'] ?? [];
            if (!is_array($codes)) {
                $codes = [];
            }

            $codesText = input_string('student_codes_text');
            $formStudentCodesText = $codesText;
            if ($codesText !== '') {
                $lines = preg_split('/\R/u', $codesText) ?: [];
                foreach ($lines as $line) {
                    $line = trim((string)$line);
                    if ($line === '') {
                        continue;
                    }

                    // รองรับ: "65001" หรือ "สมชาย ใจดี 65001" (ดึงเลขชุดท้ายสุดเป็นรหัส)
                    if (preg_match_all('/\d{3,}/u', $line, $m) && !empty($m[0])) {
                        $codes[] = (string)end($m[0]);
                    }
                }
            }

            // normalize + de-duplicate (preserve first-seen order)
            $uniq = [];
            foreach ($codes as $c) {
                $c = trim((string)$c);
                if ($c === '') {
                    continue;
                }
                if (!isset($uniq[$c])) {
                    $uniq[$c] = true;
                }
            }
            $codes = array_keys($uniq);

            $sessionId = track_class_session_create($yearIdPost, $term, $subjectIdPost, $sessionDate, $note, $codes);
            flash_set('success', 'สร้างรอบเรียนแล้ว ✅');
            header('Location: /tracks/class_attendance_view?id=' . $sessionId);
            exit;
        }

        throw new RuntimeException('คำสั่งไม่ถูกต้อง');
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'เกิดข้อผิดพลาด';
    }
}

$csrf = csrf_token();

echo render('class_attendance/create', [
    'title' => 'สร้างรอบเรียน',
    'csrf' => $csrf,
    'years' => $years,
    'yearId' => $yearId,
    'term' => $term,
    'subjects' => $subjects,
    'subjectId' => $subjectId,
    'classLevelOptions' => $classLevelOptions,
    'filters' => [
        'class_level' => $level,
        'room' => $room,
        'q' => $q,
    ],
    'students' => $students,
    'sessionDate' => $formSessionDate,
    'note' => $formNote,
    'studentCodesText' => $formStudentCodesText,
    'error' => $error,
    'success' => $success,
]);
