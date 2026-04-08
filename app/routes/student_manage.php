<?php
declare(strict_types=1);

require_auth();
track_registrations_table_ensure();
track_subjects_table_ensure();
track_groups_table_ensure();
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

$level = query_string('class_level');
$room = query_string('room');
$q = query_string('q');
$studentCode = query_string('student_code');

$term = term_from_request(track_active_term());

$error = null;
$success = flash_get('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = input_string('action');

    $back = '/tracks/student_manage?year_id=' . $yearId . '&term=' . $term;
    if ($level !== '') $back .= '&class_level=' . rawurlencode($level);
    if ($room !== '') $back .= '&room=' . rawurlencode($room);
    if ($q !== '') $back .= '&q=' . rawurlencode($q);
    if ($studentCode !== '') $back .= '&student_code=' . rawurlencode($studentCode);

    try {
        if ($action === 'delete_one') {
            $id = (int)input_string('id');
            track_reg_delete_by_id($id);
            flash_set('success', 'ลบรายการลงทะเบียนแล้ว');
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
$rows = []; // merged subject rows

$yearMap = array_column($years, null, 'id');
$yearBeMap = [];
foreach ($years as $y) {
    $yearBeMap[(int)($y['id'] ?? 0)] = (int)($y['year_be'] ?? 0);
}

if ($yearId > 0 && $studentCode !== '') {
    $student = school_student_get($yearId, $studentCode);
    if (!$student) {
        // If user typed code manually but filtered year doesn't match, fall back.
        $student = school_student_get_any_year($studentCode);
    }

    if ($student) {
        $pdoApp = db_app();
        $sid = (string)$student['student_code'];

        // Registrations across all years/terms
        $stmtReg = $pdoApp->prepare(
            'SELECT r.id AS reg_id, r.subject_id, r.created_at AS reg_created_at, '
            . 'r.year_id, r.term, r.result_status AS reg_status, '
            . 'subj.group_id, g.title AS group_title, subj.subject_code, subj.title AS subject_title, subj.description AS subject_description '
            . 'FROM track_registrations r '
            . 'JOIN track_subjects subj ON subj.id = r.subject_id '
            . 'LEFT JOIN track_groups g ON g.id = subj.group_id '
            . 'WHERE r.student_code = ?'
        );
        $stmtReg->execute([$sid]);
        $regRows = $stmtReg->fetchAll();

        // Learning results across all years/terms (pass/fail/pending)
        $stmtRes = $pdoApp->prepare(
            'SELECT sess.year_id, sess.term, subj.id AS subject_id, subj.group_id, g.title AS group_title, subj.subject_code, subj.title AS subject_title, subj.description AS subject_description, '
            . 'MAX(CASE WHEN cs.result_status = \'pass\' THEN 1 ELSE 0 END) AS has_pass, '
            . 'MAX(CASE WHEN cs.result_status = \'fail\' THEN 1 ELSE 0 END) AS has_fail, '
            . 'MAX(sess.session_date) AS last_date '
            . 'FROM track_class_students cs '
            . 'JOIN track_class_sessions sess ON sess.id = cs.session_id '
            . 'JOIN track_subjects subj ON subj.id = sess.subject_id '
            . 'LEFT JOIN track_groups g ON g.id = subj.group_id '
            . 'WHERE cs.student_code = ? '
            . 'GROUP BY sess.year_id, sess.term, subj.id, subj.group_id, g.title, subj.subject_code, subj.title, subj.description'
        );
        $stmtRes->execute([$sid]);
        $resRows = $stmtRes->fetchAll();

        $bySubject = [];

        foreach ($regRows as $r) {
            $subjectId = (int)($r['subject_id'] ?? 0);
            if ($subjectId <= 0) continue;
            $yr = (int)($r['year_id'] ?? 0);
            $tr = (int)($r['term'] ?? 1);
            $tr = ($tr === 2) ? 2 : 1;
            $k = $yr . '-' . $tr . '-' . $subjectId;

            $regStatus = (string)($r['reg_status'] ?? 'registered');
            $regHasPass = ($regStatus === 'pass') ? 1 : 0;

            $bySubject[$k] = [
                'subject_id' => $subjectId,
                'year_id' => $yr,
                'term' => $tr,
                'group_title' => (string)($r['group_title'] ?? ''),
                'subject_code' => (string)($r['subject_code'] ?? ''),
                'subject_title' => (string)($r['subject_title'] ?? ''),
                'subject_description' => (string)($r['subject_description'] ?? ''),
                'reg_id' => (int)($r['reg_id'] ?? 0),
                'reg_created_at' => (string)($r['reg_created_at'] ?? ''),
                'has_pass' => $regHasPass,
                'has_fail' => 0,
                'last_date' => '',
            ];
        }

        foreach ($resRows as $r) {
            $subjectId = (int)($r['subject_id'] ?? 0);
            if ($subjectId <= 0) continue;

            $yr = (int)($r['year_id'] ?? 0);
            $tr = (int)($r['term'] ?? 1);
            $tr = ($tr === 2) ? 2 : 1;
            $k = $yr . '-' . $tr . '-' . $subjectId;

            if (!isset($bySubject[$k])) {
                $bySubject[$k] = [
                    'subject_id' => $subjectId,
                    'year_id' => $yr,
                    'term' => $tr,
                    'group_title' => (string)($r['group_title'] ?? ''),
                    'subject_code' => (string)($r['subject_code'] ?? ''),
                    'subject_title' => (string)($r['subject_title'] ?? ''),
                    'subject_description' => (string)($r['subject_description'] ?? ''),
                    'reg_id' => 0,
                    'reg_created_at' => '',
                    'has_pass' => 0,
                    'has_fail' => 0,
                    'last_date' => '',
                ];
            }

            $bySubject[$k]['has_pass'] = (int)($r['has_pass'] ?? 0);
            $bySubject[$k]['has_fail'] = (int)($r['has_fail'] ?? 0);
            $bySubject[$k]['last_date'] = (string)($r['last_date'] ?? '');
        }

        $rows = array_values($bySubject);

        usort($rows, function (array $a, array $b) use ($yearBeMap): int {
            $ya = (int)($a['year_id'] ?? 0);
            $yb = (int)($b['year_id'] ?? 0);
            $yba = (int)($yearBeMap[$ya] ?? 0);
            $ybb = (int)($yearBeMap[$yb] ?? 0);
            $ay = ($yba > 0 && $ybb > 0) ? ($yba <=> $ybb) : ($ya <=> $yb);
            if ($ay !== 0) return $ay;

            $ta = (int)($a['term'] ?? 1);
            $tb = (int)($b['term'] ?? 1);
            if ($ta !== $tb) return $ta <=> $tb;

            $ga = (string)($a['group_title'] ?? '');
            $gb = (string)($b['group_title'] ?? '');
            $c = strcmp($ga, $gb);
            if ($c !== 0) return $c;

            $ca = (string)($a['subject_code'] ?? '');
            $cb = (string)($b['subject_code'] ?? '');
            $c2 = strcmp($ca, $cb);
            if ($c2 !== 0) return $c2;

            $ta2 = (string)($a['subject_title'] ?? '');
            $tb2 = (string)($b['subject_title'] ?? '');
            return strcmp($ta2, $tb2);
        });
    }
}

echo render('student_manage/index', [
    'title' => 'จัดการนักเรียนรายคน',
    'years' => $years,
    'yearMap' => $yearMap,
    'yearId' => $yearId,
    'term' => $term,
    'filters' => [
        'class_level' => $level,
        'room' => $room,
        'q' => $q,
        'student_code' => $studentCode,
    ],
    'classLevelOptions' => $classLevelOptions,
    'students' => $students,
    'student' => $student,
    'subjectRows' => $rows,
    'error' => $error,
    'success' => $success,
]);
