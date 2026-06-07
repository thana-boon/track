<?php
declare(strict_types=1);

require_auth();

$me   = auth_user();
$role = is_array($me) ? (string)($me['role'] ?? 'teacher') : 'teacher';

if (!in_array($role, ['admin', 'teacher'], true)) {
    redirect('class_attendance');
}

track_class_tables_ensure();
track_class_advisors_table_ensure();

$pdoSchool    = db_school();
$years        = $pdoSchool->query('SELECT id, year_be, title, is_active FROM academic_years ORDER BY year_be DESC')->fetchAll();
$activeYearId = (int)($pdoSchool->query('SELECT id FROM academic_years WHERE is_active = 1 ORDER BY id DESC LIMIT 1')->fetchColumn() ?: 0);

$yearId = (int)query_string('year_id');
if ($yearId <= 0) $yearId = $activeYearId;

$yearStmt = $pdoSchool->prepare('SELECT id, title, year_be FROM academic_years WHERE id = ?');
$yearStmt->execute([$yearId]);
$yearData = $yearStmt->fetch() ?: [];

$term = term_from_request(track_active_term());

// Load rooms available for this user
$rooms = [];
if ($yearId > 0) {
    $s = $pdoSchool->prepare(
        'SELECT CASE '
        . " WHEN class_level IN ('ม.4','ม4','M4') THEN 'ม.4' "
        . " WHEN class_level IN ('ม.5','ม5','M5') THEN 'ม.5' "
        . " WHEN class_level IN ('ม.6','ม6','M6') THEN 'ม.6' "
        . ' ELSE class_level END AS class_level, '
        . 'class_room, COUNT(*) AS student_count '
        . "FROM students WHERE year_id = :year_id AND class_room IS NOT NULL "
        . "AND class_level IN ('ม.4','ม4','M4','ม.5','ม5','M5','ม.6','ม6','M6') "
        . 'GROUP BY class_level, class_room ORDER BY class_level, class_room'
    );
    $s->execute([':year_id' => $yearId]);
    $rooms = $s->fetchAll();
}

if ($role === 'teacher') {
    $teacherId  = (int)($me['id'] ?? 0);
    $assignment = track_class_advisor_for_teacher($yearId, $teacherId);
    if (!$assignment) {
        http_response_code(404);
        echo render('errors/404', ['title' => 'ไม่พบห้อง', 'path' => 'class_room_print']);
        exit;
    }
    $assignedLevel = (string)$assignment['class_level'];
    $assignedRoom  = (int)$assignment['class_room'];
    $rooms = array_values(array_filter($rooms, static function ($r) use ($assignedLevel, $assignedRoom) {
        return (string)($r['class_level'] ?? '') === $assignedLevel && (int)($r['class_room'] ?? 0) === $assignedRoom;
    }));
}

// Defaults from referrer params
$defaultLevel = (string)query_string('class_level');
$defaultRoom  = (int)query_string('class_room');
$defaultYm    = (string)query_string('ym');
if ($defaultYm === '' || !preg_match('/^\d{4}-\d{2}$/', $defaultYm)) {
    $defaultYm = date('Y-m');
}

// Parse rooms[] from GET
$rawRooms = isset($_GET['rooms']) && is_array($_GET['rooms']) ? $_GET['rooms'] : [];
$ymStart  = (string)query_string('ym_start');
$ymEnd    = (string)query_string('ym_end');

if (!preg_match('/^\d{4}-\d{2}$/', $ymStart)) $ymStart = $defaultYm;
if (!preg_match('/^\d{4}-\d{2}$/', $ymEnd))   $ymEnd   = $ymStart;
if ($ymEnd < $ymStart) $ymEnd = $ymStart;

// ── Show form if no rooms selected ──────────────────────────────────────────
if (empty($rawRooms)) {
    echo render('class_room/print_form', [
        'title'        => 'รายงาน PDF การเข้าเรียน',
        'yearId'       => $yearId,
        'years'        => $years,
        'term'         => $term,
        'rooms'        => $rooms,
        'role'         => $role,
        'defaultLevel' => $defaultLevel,
        'defaultRoom'  => $defaultRoom,
        'defaultYm'    => $defaultYm,
    ]);
    exit;
}

// ── Parse & validate room specs ─────────────────────────────────────────────
$roomSpecs = [];
foreach ($rawRooms as $raw) {
    $parts = explode('|', (string)$raw, 2);
    if (count($parts) === 2) {
        $lvl = trim($parts[0]);
        $rm  = (int)trim($parts[1]);
        if ($lvl !== '' && $rm > 0) {
            $roomSpecs[] = ['level' => $lvl, 'room' => $rm];
        }
    }
}
if (empty($roomSpecs)) {
    header('Location: /tracks/class_room_print');
    exit;
}

// Teacher role: limit to assigned room only
if ($role === 'teacher') {
    $teacherId  = (int)($me['id'] ?? 0);
    $assignment = track_class_advisor_for_teacher($yearId, $teacherId);
    if ($assignment) {
        $al = (string)$assignment['class_level'];
        $ar = (int)$assignment['class_room'];
        $roomSpecs = array_values(array_filter($roomSpecs, static function ($rs) use ($al, $ar) {
            return (string)$rs['level'] === $al && (int)$rs['room'] === $ar;
        }));
    }
    if (empty($roomSpecs)) {
        http_response_code(403);
        echo render('errors/404', ['title' => 'ไม่มีสิทธิ์', 'path' => 'class_room_print']);
        exit;
    }
}

// ── Date range ───────────────────────────────────────────────────────────────
try {
    $dateStart = new DateTimeImmutable($ymStart . '-01');
    $dateEnd   = (new DateTimeImmutable($ymEnd . '-01'))->modify('last day of this month');
} catch (Throwable $e) {
    $dateStart = new DateTimeImmutable(date('Y-m-01'));
    $dateEnd   = $dateStart->modify('last day of this month');
}

// ── Build room WHERE clauses ─────────────────────────────────────────────────
// For queries joining track_class with school.students aliased as 'st'
$orTrack = [];
$paramsTrack = [];
// For direct school.students query (no alias)
$orSchool = [];
$paramsSchool = [];

$levelVariantsMap = [
    'ม.4' => ['ม.4', 'ม4', 'M4'],
    'ม.5' => ['ม.5', 'ม5', 'M5'],
    'ม.6' => ['ม.6', 'ม6', 'M6'],
];

foreach ($roomSpecs as $rs) {
    $lvl      = (string)$rs['level'];
    $rm       = (int)$rs['room'];
    $variants = $levelVariantsMap[$lvl] ?? [$lvl];
    $ph       = implode(',', array_fill(0, count($variants), '?'));

    $orTrack[]  = '(st.class_room = ? AND st.class_level IN (' . $ph . '))';
    $paramsTrack[] = $rm;
    foreach ($variants as $v) $paramsTrack[] = $v;

    $orSchool[] = '(class_room = ? AND class_level IN (' . $ph . '))';
    $paramsSchool[] = $rm;
    foreach ($variants as $v) $paramsSchool[] = $v;
}

$whereTrack  = implode(' OR ', $orTrack);
$whereSchool = implode(' OR ', $orSchool);

$pdoApp      = db_app();
$baseParams  = [$yearId, $term, $dateStart->format('Y-m-d'), $dateEnd->format('Y-m-d')];

// ── 1. Columns: distinct (date, subject) pairs ───────────────────────────────
$colStmt = $pdoApp->prepare(
    'SELECT DISTINCT sess.session_date, sess.subject_id, subj.title AS subject_title '
    . 'FROM track_class_sessions sess '
    . 'JOIN track_class_students cs ON cs.session_id = sess.id '
    . 'JOIN track_subjects subj ON subj.id = sess.subject_id '
    . 'JOIN ' . DB_SCHOOL . '.students st ON st.year_id = sess.year_id AND st.student_code = cs.student_code '
    . 'WHERE sess.year_id = ? AND sess.term = ? AND sess.session_date BETWEEN ? AND ? '
    . 'AND (' . $whereTrack . ') '
    . 'ORDER BY sess.session_date, subj.title'
);
$colStmt->execute(array_merge($baseParams, $paramsTrack));
$columns = $colStmt->fetchAll();

// ── 2. Result per (student, date, subject) ───────────────────────────────────
$resStmt = $pdoApp->prepare(
    'SELECT cs.student_code, sess.session_date, sess.subject_id, '
    . 'MAX(CASE WHEN cs.result_status = \'excellent\' THEN 1 ELSE 0 END) AS has_excellent, '
    . 'MAX(CASE WHEN cs.result_status = \'pass\'      THEN 1 ELSE 0 END) AS has_pass, '
    . 'MAX(CASE WHEN cs.result_status = \'fail\'      THEN 1 ELSE 0 END) AS has_fail '
    . 'FROM track_class_sessions sess '
    . 'JOIN track_class_students cs ON cs.session_id = sess.id '
    . 'JOIN ' . DB_SCHOOL . '.students st ON st.year_id = sess.year_id AND st.student_code = cs.student_code '
    . 'WHERE sess.year_id = ? AND sess.term = ? AND sess.session_date BETWEEN ? AND ? '
    . 'AND (' . $whereTrack . ') '
    . 'GROUP BY cs.student_code, sess.session_date, sess.subject_id'
);
$resStmt->execute(array_merge($baseParams, $paramsTrack));

$resultMap = [];
foreach ($resStmt->fetchAll() as $row) {
    $code = (string)($row['student_code'] ?? '');
    $d    = (string)($row['session_date'] ?? '');
    $sid  = (int)($row['subject_id'] ?? 0);
    if ($code === '' || $d === '' || $sid === 0) continue;

    if ((int)($row['has_fail'] ?? 0) === 1)      $result = 'fail';
    elseif ((int)($row['has_excellent'] ?? 0) === 1) $result = 'excellent';
    elseif ((int)($row['has_pass'] ?? 0) === 1)     $result = 'pass';
    else                                             $result = 'pending';

    $resultMap[$code][$d . '_' . $sid] = $result;
}

// ── 3. Student list from all selected rooms ──────────────────────────────────
$stuStmt = $pdoSchool->prepare(
    'SELECT student_code, first_name, last_name, class_level, class_room, number_in_room '
    . 'FROM students WHERE year_id = ? AND (' . $whereSchool . ') '
    . 'ORDER BY class_level, class_room, number_in_room, student_code'
);
$stuStmt->execute(array_merge([$yearId], $paramsSchool));
$allStudents = $stuStmt->fetchAll();

// Normalize level
$normMap = ['ม.4'=>'ม.4','ม4'=>'ม.4','M4'=>'ม.4','ม.5'=>'ม.5','ม5'=>'ม.5','M5'=>'ม.5','ม.6'=>'ม.6','ม6'=>'ม.6','M6'=>'ม.6'];
foreach ($allStudents as &$st) {
    $raw = (string)($st['class_level'] ?? '');
    $st['norm_level'] = $normMap[$raw] ?? $raw;
    $st['room_key']   = $st['norm_level'] . '/' . (string)($st['class_room'] ?? '');
}
unset($st);

$settings      = report_settings_get();
$isMultiRoom   = count($roomSpecs) > 1;
$returnHref    = '/tracks/class_room_print?' . http_build_query(array_filter([
    'year_id'      => $yearId,
    'term'         => $term,
    'class_level'  => $defaultLevel,
    'class_room'   => $defaultRoom,
    'ym'           => $defaultYm,
], static fn($v) => $v !== '' && $v !== 0));

header('Content-Type: text/html; charset=utf-8');
echo render_plain('class_room/print', [
    'title'        => 'รายงานการเข้าเรียน',
    'settings'     => $settings,
    'yearData'     => $yearData,
    'term'         => $term,
    'ymStart'      => $ymStart,
    'ymEnd'        => $ymEnd,
    'roomSpecs'    => $roomSpecs,
    'columns'      => $columns,
    'allStudents'  => $allStudents,
    'resultMap'    => $resultMap,
    'isMultiRoom'  => $isMultiRoom,
    'returnHref'   => $returnHref,
]);
exit;
