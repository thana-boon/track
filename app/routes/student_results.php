<?php
declare(strict_types=1);

require_auth();
track_registrations_table_ensure();
track_subjects_table_ensure();
track_class_tables_ensure();

$me = auth_user();
$role = is_array($me) ? (string)($me['role'] ?? 'teacher') : '';
if ($role !== 'student') {
    // This page is intended for student self-service.
    redirect('class_attendance');
}

$studentCode = trim((string)($me['username'] ?? ''));

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
        $yearText = trim((string)$y['title']);
        if ($yearText === '') {
            $yearText = (string)$y['year_be'];
        }
        break;
    }
}

$error = null;

$student = null;
if ($yearId > 0 && $studentCode !== '') {
    $student = school_student_get($yearId, $studentCode);
}

$assignedRegs = [];
if ($yearId > 0 && $studentCode !== '') {
    // In this project, registrations are used as the “assigned/recorded subjects” list for a student.
    $assignedRegs = track_registrations_for_student($yearId, $studentCode);
}

// Aggregate attendance/result per subject for this student in this year.
$statsBySubject = []; // subject_id => stats
$latestBySubject = []; // subject_id => ['attend_status'=>?, 'result_status'=>?, 'checked_at'=>?]

if ($yearId > 0 && $studentCode !== '') {
    $pdoApp = db_app();

    $stmt = $pdoApp->prepare(
        'SELECT sess.subject_id, '
        . 'COUNT(*) AS sessions_total, '
        . 'SUM(cs.attend_status = 1) AS attend_yes, '
        . 'SUM(cs.attend_status = 0) AS attend_no, '
        . 'SUM(cs.attend_status IS NULL) AS attend_unknown, '
        . 'SUM(cs.result_status = \'pass\') AS pass_count, '
        . 'SUM(cs.result_status = \'fail\') AS fail_count, '
        . 'SUM(cs.result_status = \'pending\') AS pending_count, '
        . 'MAX(sess.session_date) AS last_session_date, '
        . 'MAX(cs.checked_at) AS last_checked_at '
        . 'FROM track_class_students cs '
        . 'JOIN track_class_sessions sess ON sess.id = cs.session_id '
        . 'WHERE sess.year_id = ? AND cs.student_code = ? '
        . 'GROUP BY sess.subject_id'
    );
    $stmt->execute([$yearId, $studentCode]);

    foreach ($stmt->fetchAll() as $row) {
        $sid = (int)($row['subject_id'] ?? 0);
        if ($sid <= 0) continue;
        $statsBySubject[$sid] = $row;
    }

    // Latest checked status per subject (best-effort)
    $stmt2 = $pdoApp->prepare(
        'SELECT sess.subject_id, cs.attend_status, cs.result_status, cs.checked_at '
        . 'FROM track_class_students cs '
        . 'JOIN track_class_sessions sess ON sess.id = cs.session_id '
        . 'WHERE sess.year_id = ? AND cs.student_code = ? AND cs.checked_at IS NOT NULL '
        . 'ORDER BY sess.subject_id ASC, cs.checked_at DESC, cs.session_id DESC'
    );
    $stmt2->execute([$yearId, $studentCode]);

    foreach ($stmt2->fetchAll() as $row) {
        $sid = (int)($row['subject_id'] ?? 0);
        if ($sid <= 0) continue;
        if (isset($latestBySubject[$sid])) continue;
        $latestBySubject[$sid] = [
            'attend_status' => $row['attend_status'] ?? null,
            'result_status' => (string)($row['result_status'] ?? 'pending'),
            'checked_at' => (string)($row['checked_at'] ?? ''),
        ];
    }
}

// Build subject rows to show.
$subjects = []; // each row: title, status, counts...
$passedSubjects = [];
$missingSubjects = [];

foreach ($assignedRegs as $r) {
    $sid = (int)($r['subject_id'] ?? 0);
    if ($sid <= 0) continue;

    $stats = $statsBySubject[$sid] ?? null;
    $latest = $latestBySubject[$sid] ?? null;

    $sessionsTotal = (int)($stats['sessions_total'] ?? 0);
    $attYes = (int)($stats['attend_yes'] ?? 0);
    $attNo = (int)($stats['attend_no'] ?? 0);

    $passCount = (int)($stats['pass_count'] ?? 0);
    $failCount = (int)($stats['fail_count'] ?? 0);

    $passed = $passCount > 0;

    $resultLabel = 'รอดำเนินการ';
    $resultTone = 'bg-sand-100/70 text-ink-900 ring-black/5';
    if ($passed) {
        $resultLabel = 'ผ่าน';
        $resultTone = 'bg-emerald-50 text-emerald-900 ring-emerald-200';
    } elseif ($failCount > 0) {
        $resultLabel = 'ไม่ผ่าน';
        $resultTone = 'bg-red-50 text-red-900 ring-red-200';
    }

    $attLabel = 'ยังไม่มีข้อมูลเช็คชื่อ';
    if ($sessionsTotal > 0) {
        $attLabel = 'เข้าเรียน ' . $attYes . '/' . $sessionsTotal;
        if ($attNo > 0) {
            $attLabel .= ' (ไม่เข้าเรียน ' . $attNo . ')';
        }
    }

    // Use latest checked row if available.
    $latestAttend = null;
    $latestResult = null;
    $checkedAt = '';
    if (is_array($latest)) {
        $latestAttend = $latest['attend_status'];
        $latestResult = (string)($latest['result_status'] ?? 'pending');
        $checkedAt = (string)($latest['checked_at'] ?? '');
    }

    $subjects[] = [
        'subject_id' => $sid,
        'title' => (string)($r['subject_title'] ?? ''),
        'description' => (string)($r['subject_description'] ?? ''),
        'sessions_total' => $sessionsTotal,
        'attend_yes' => $attYes,
        'attend_no' => $attNo,
        'attend_label' => $attLabel,
        'result_label' => $resultLabel,
        'result_tone' => $resultTone,
        'passed' => $passed,
        'latest_attend_status' => $latestAttend,
        'latest_result_status' => $latestResult,
        'checked_at' => $checkedAt,
        'last_session_date' => (string)($stats['last_session_date'] ?? ''),
    ];

    if ($passed) {
        $passedSubjects[] = (string)($r['subject_title'] ?? '');
    } else {
        $missingSubjects[] = (string)($r['subject_title'] ?? '');
    }
}

$summary = [
    'assigned_total' => count($subjects),
    'passed_total' => count($passedSubjects),
    'passed_titles' => array_values(array_filter(array_map('trim', $passedSubjects), static fn($v) => $v !== '')),
    'missing_titles' => array_values(array_filter(array_map('trim', $missingSubjects), static fn($v) => $v !== '')),
];

if ($studentCode === '') {
    $error = 'ไม่พบรหัสนักเรียนในบัญชีผู้ใช้ (username ต้องเป็นรหัสนักเรียน)';
}

echo render('student_results/index', [
    'title' => 'ผลการเรียนของฉัน',
    'years' => $years,
    'yearId' => $yearId,
    'yearText' => $yearText !== '' ? $yearText : (string)$yearId,
    'studentCode' => $studentCode,
    'student' => $student,
    'subjects' => $subjects,
    'summary' => $summary,
    'error' => $error,
]);
