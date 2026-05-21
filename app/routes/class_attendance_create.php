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

// Load saved class groups for the group picker
class_groups_table_ensure();
$classGroups = class_groups_all();

// If a group was requested via use_group param, pre-fill student_codes_text
$useGroupId = (int)(query_string('use_group') !== '' ? query_string('use_group') : '0');
$prefilledFromGroup = '';
if ($useGroupId > 0) {
    $groupRow = class_group_get($useGroupId);
    if ($groupRow !== null) {
        $prefilledFromGroup = (string)($groupRow['student_codes'] ?? '');
        $formStudentCodesText = $prefilledFromGroup;
    }
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
            $sessionDatesRaw = $_POST['session_dates'] ?? [];
            if (!is_array($sessionDatesRaw)) {
                $sessionDatesRaw = [];
            }

            // Validate and deduplicate dates
            $sessionDates = [];
            foreach ($sessionDatesRaw as $d) {
                $d = trim((string)$d);
                if ($d !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                    $sessionDates[] = $d;
                }
            }
            $sessionDates = array_values(array_unique($sessionDates));

            if (count($sessionDates) === 0) {
                throw new RuntimeException('กรุณาเลือกวันที่เรียนอย่างน้อย 1 วัน');
            }

            $note = input_string('note');
            $subjectIdPost = (int)input_string('subject_id');
            $yearIdPost = (int)input_string('year_id');

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

            // Create one session per date
            $firstSessionId = 0;
            foreach ($sessionDates as $date) {
                $sid = track_class_session_create($yearIdPost, $term, $subjectIdPost, $date, $note, $codes);
                if ($firstSessionId === 0) {
                    $firstSessionId = $sid;
                }
            }

            $daysCount = count($sessionDates);
            flash_set('success', 'สร้างรอบเรียนแล้ว ' . $daysCount . ' วัน ✅');
            $qs = http_build_query(array_filter([
                'year_id' => $yearIdPost,
                'term' => $term,
                'session_date' => $sessionDates[0],
            ], static fn($v) => $v !== '' && $v !== 0));
            header('Location: /tracks/class_attendance' . ($qs !== '' ? ('?' . $qs) : ''));
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
    'classGroups' => $classGroups,
    'sessionDate' => $formSessionDate,
    'note' => $formNote,
    'studentCodesText' => $formStudentCodesText,
    'error' => $error,
    'success' => $success,
]);
