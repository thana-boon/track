<?php
declare(strict_types=1);

require_auth();

$me = auth_user();
$role = is_array($me) ? (string)($me['role'] ?? 'teacher') : 'teacher';

if (!in_array($role, ['admin', 'teacher'], true)) {
    redirect('class_attendance');
}

track_class_tables_ensure();
track_subjects_table_ensure();
track_class_advisors_table_ensure();

$pdoSchool = db_school();
$activeYearId = (int)($pdoSchool->query('SELECT id FROM academic_years WHERE is_active = 1 ORDER BY id DESC LIMIT 1')->fetchColumn() ?: 0);

$yearId = (int)query_string('year_id');
if ($yearId <= 0) {
    $yearId = $activeYearId;
}

$term = term_from_request(track_active_term());

$studentCode = (string)query_string('student_code');

$return = [
    'year_id' => (int)query_string('return_year_id'),
    'term' => (int)query_string('return_term'),
    'class_level' => (string)query_string('return_class_level'),
    'class_room' => (int)query_string('return_class_room'),
    'ym' => (string)query_string('return_ym'),
];
$returnQs = http_build_query(array_filter($return, static fn($v) => $v !== '' && $v !== 0));
$returnHref = '/tracks/class_room' . ($returnQs !== '' ? ('?' . $returnQs) : '');

// Load student + role guard
$student = null;
if ($yearId > 0 && $studentCode !== '') {
    $stmt = $pdoSchool->prepare('SELECT student_code, first_name, last_name, class_level, class_room, number_in_room FROM students WHERE year_id = ? AND student_code = ? LIMIT 1');
    $stmt->execute([$yearId, $studentCode]);
    $student = $stmt->fetch();
}

if (!$student) {
    http_response_code(404);
    echo render('errors/404', ['title' => 'ไม่พบนักเรียน', 'path' => 'class_room_student']);
    exit;
}

if ($role === 'teacher') {
    $teacherId = (int)($me['id'] ?? 0);
    $assignment = track_class_advisor_for_teacher($yearId, $teacherId);
    if (!$assignment && $activeYearId > 0 && $yearId !== $activeYearId) {
        $yearId = $activeYearId;
        $assignment = track_class_advisor_for_teacher($yearId, $teacherId);
    }
    if (!$assignment) {
        http_response_code(404);
        echo render('errors/404', ['title' => 'ไม่พบหน้า', 'path' => 'class_room_student']);
        exit;
    }

    $normStudentLevel = track_class_level_normalize((string)($student['class_level'] ?? ''));
    if ($normStudentLevel !== (string)$assignment['class_level'] || (int)($student['class_room'] ?? 0) !== (int)$assignment['class_room']) {
        http_response_code(404);
        echo render('errors/404', ['title' => 'ไม่พบหน้า', 'path' => 'class_room_student']);
        exit;
    }
}

$pdoApp = db_app();
$stmt = $pdoApp->prepare(
    'SELECT subj.id AS subject_id, subj.title AS subject_title, '
    . 'MAX(CASE WHEN cs.result_status = \'pass\' THEN 1 ELSE 0 END) AS has_pass, '
    . 'MAX(CASE WHEN cs.result_status = \'fail\' THEN 1 ELSE 0 END) AS has_fail, '
    . 'MAX(sess.session_date) AS last_date '
    . 'FROM track_class_students cs '
    . 'JOIN track_class_sessions sess ON sess.id = cs.session_id '
    . 'JOIN track_subjects subj ON subj.id = sess.subject_id '
    . 'WHERE sess.year_id = ? AND sess.term = ? AND cs.student_code = ? '
    . 'GROUP BY subj.id, subj.title '
    . 'ORDER BY subj.title'
);
$stmt->execute([$yearId, $term, $studentCode]);
$rows = $stmt->fetchAll();

$passed = [];
$failed = [];
$pending = [];
foreach ($rows as $r) {
    $hasPass = (int)($r['has_pass'] ?? 0) === 1;
    $hasFail = (int)($r['has_fail'] ?? 0) === 1;
    if ($hasPass) $passed[] = $r;
    else if ($hasFail) $failed[] = $r;
    else $pending[] = $r;
}

echo render('class_room/student', [
    'title' => 'ผลการเรียน (ผ่าน/ไม่ผ่าน)',
    'student' => $student,
    'yearId' => $yearId,
    'term' => $term,
    'passed' => $passed,
    'failed' => $failed,
    'pending' => $pending,
    'returnHref' => $returnHref,
]);
