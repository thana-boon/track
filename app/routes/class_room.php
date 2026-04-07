<?php
declare(strict_types=1);

require_auth();

$me = auth_user();
$role = is_array($me) ? (string)($me['role'] ?? 'teacher') : 'teacher';

if (!in_array($role, ['admin', 'teacher'], true)) {
    redirect('class_attendance');
}

track_class_tables_ensure();
track_class_advisors_table_ensure();

$pdoSchool = db_school();
$years = $pdoSchool->query('SELECT id, year_be, title, is_active FROM academic_years ORDER BY year_be DESC')->fetchAll();
$activeYearId = (int)($pdoSchool->query('SELECT id FROM academic_years WHERE is_active = 1 ORDER BY id DESC LIMIT 1')->fetchColumn() ?: 0);

$defaultYearId = $activeYearId;
if ($defaultYearId === 0 && isset($years[0]['id'])) {
    $defaultYearId = (int)$years[0]['id'];
}

$yearId = (int)query_string('year_id');
if ($yearId <= 0) {
    $yearId = $defaultYearId;
}

// Validate yearId exists
$yearIdValid = false;
foreach ($years as $y) {
    if ((int)($y['id'] ?? 0) === $yearId) {
        $yearIdValid = true;
        break;
    }
}
if (!$yearIdValid) {
    $yearId = $defaultYearId;
}

$teacherAssignment = null;
if ($role === 'teacher') {
    $teacherId = (int)($me['id'] ?? 0);
    $teacherAssignment = track_class_advisor_for_teacher($yearId, $teacherId);
    if (!$teacherAssignment && $activeYearId > 0 && $yearId !== $activeYearId) {
        $yearId = $activeYearId;
        $teacherAssignment = track_class_advisor_for_teacher($yearId, $teacherId);
    }
    if (!$teacherAssignment) {
        http_response_code(404);
        echo render('errors/404', [
            'title' => 'ไม่พบหน้า',
            'path' => 'class_room',
        ]);
        exit;
    }
}

// Available rooms (M4-M6 only)
$rooms = [];
if ($yearId > 0) {
    $stmt = $pdoSchool->prepare(
        'SELECT '
        . "CASE "
        . " WHEN class_level IN ('ม.4','ม4','M4') THEN 'ม.4' "
        . " WHEN class_level IN ('ม.5','ม5','M5') THEN 'ม.5' "
        . " WHEN class_level IN ('ม.6','ม6','M6') THEN 'ม.6' "
        . " ELSE class_level END AS class_level, "
        . 'class_room, COUNT(*) AS student_count '
        . 'FROM students '
        . 'WHERE year_id = :year_id AND class_room IS NOT NULL AND class_level IN (\'ม.4\',\'ม4\',\'M4\',\'ม.5\',\'ม5\',\'M5\',\'ม.6\',\'ม6\',\'M6\') '
        . 'GROUP BY class_level, class_room '
        . 'ORDER BY class_level, class_room'
    );
    $stmt->execute([':year_id' => $yearId]);
    $rooms = $stmt->fetchAll();
}

$selectedLevel = (string)query_string('class_level');
$selectedRoom = (int)query_string('class_room');

if ($role === 'teacher' && is_array($teacherAssignment)) {
    $selectedLevel = (string)$teacherAssignment['class_level'];
    $selectedRoom = (int)$teacherAssignment['class_room'];
}

if ($selectedLevel === '' || $selectedRoom <= 0) {
    if (!empty($rooms)) {
        $selectedLevel = (string)($rooms[0]['class_level'] ?? '');
        $selectedRoom = (int)($rooms[0]['class_room'] ?? 0);
    }
}

$ym = (string)query_string('ym');
if ($ym === '' || !preg_match('/^\d{4}-\d{2}$/', $ym)) {
    $ym = date('Y-m');
}

$levelVariants = match ($selectedLevel) {
    'ม.4' => ['ม.4', 'ม4', 'M4'],
    'ม.5' => ['ม.5', 'ม5', 'M5'],
    'ม.6' => ['ม.6', 'ม6', 'M6'],
    default => [$selectedLevel],
};

// Students in the selected room
$students = [];
if ($yearId > 0 && $selectedLevel !== '' && $selectedRoom > 0) {
    $ph = [];
    $params = [':year_id' => $yearId, ':room' => $selectedRoom];
    foreach ($levelVariants as $i => $lvl) {
        $k = ':lvl' . $i;
        $ph[] = $k;
        $params[$k] = $lvl;
    }

    $stmt = $pdoSchool->prepare(
        'SELECT student_code, first_name, last_name, class_level, class_room, number_in_room '
        . 'FROM students '
        . 'WHERE year_id = :year_id AND class_room = :room AND class_level IN (' . implode(',', $ph) . ') '
        . 'ORDER BY number_in_room, student_code'
    );
    $stmt->execute($params);
    $students = $stmt->fetchAll();
}

// Date range for month
try {
    $start = new DateTimeImmutable($ym . '-01');
    $end = $start->modify('last day of this month');
} catch (Throwable $e) {
    $start = new DateTimeImmutable(date('Y-m-01'));
    $end = $start->modify('last day of this month');
}

$dates = [];
$dailyMap = []; // [student_code][Y-m-d] => ['attend'=>present|absent|pending, 'result'=>pass|fail|pending]
$totals = []; // [student_code] => ['present'=>int,'absent'=>int]

if ($yearId > 0 && $selectedLevel !== '' && $selectedRoom > 0) {
    $pdoApp = db_app();

    // Dates that have sessions for this room in the selected month
    // PDO doesn't allow mixing named and positional easily; use positional only
    $sqlDates =
        'SELECT DISTINCT sess.session_date '
        . 'FROM track_class_sessions sess '
        . 'JOIN track_class_students cs ON cs.session_id = sess.id '
        . 'JOIN ' . DB_SCHOOL . '.students st ON st.year_id = sess.year_id AND st.student_code = cs.student_code '
        . 'WHERE sess.year_id = ? AND sess.session_date BETWEEN ? AND ? '
        . 'AND st.class_room = ? AND st.class_level IN (' . implode(',', array_fill(0, count($levelVariants), '?')) . ') '
        . 'ORDER BY sess.session_date';
    $stmtDates = $pdoApp->prepare($sqlDates);
    $stmtDates->execute(array_merge([$yearId, $start->format('Y-m-d'), $end->format('Y-m-d'), $selectedRoom], $levelVariants));
    foreach ($stmtDates->fetchAll() as $row) {
        $d = (string)($row['session_date'] ?? '');
        if ($d !== '') $dates[] = $d;
    }

    // Attendance + result aggregated per student per date
    $sqlAgg =
        'SELECT cs.student_code, sess.session_date, '
        . 'SUM(CASE WHEN cs.attend_status = 1 THEN 1 ELSE 0 END) AS present_count, '
        . 'SUM(CASE WHEN cs.attend_status = 0 THEN 1 ELSE 0 END) AS absent_count, '
        . 'SUM(CASE WHEN cs.attend_status IS NULL THEN 1 ELSE 0 END) AS pending_count, '
        . 'SUM(CASE WHEN cs.result_status = \'pass\' THEN 1 ELSE 0 END) AS pass_count, '
        . 'SUM(CASE WHEN cs.result_status = \'fail\' THEN 1 ELSE 0 END) AS fail_count, '
        . 'SUM(CASE WHEN cs.result_status = \'pending\' THEN 1 ELSE 0 END) AS res_pending_count '
        . 'FROM track_class_sessions sess '
        . 'JOIN track_class_students cs ON cs.session_id = sess.id '
        . 'JOIN ' . DB_SCHOOL . '.students st ON st.year_id = sess.year_id AND st.student_code = cs.student_code '
        . 'WHERE sess.year_id = ? AND sess.session_date BETWEEN ? AND ? '
        . 'AND st.class_room = ? AND st.class_level IN (' . implode(',', array_fill(0, count($levelVariants), '?')) . ') '
        . 'GROUP BY cs.student_code, sess.session_date';

    $stmtAgg = $pdoApp->prepare($sqlAgg);
    $stmtAgg->execute(array_merge([$yearId, $start->format('Y-m-d'), $end->format('Y-m-d'), $selectedRoom], $levelVariants));

    foreach ($stmtAgg->fetchAll() as $row) {
        $code = (string)($row['student_code'] ?? '');
        $d = (string)($row['session_date'] ?? '');
        if ($code === '' || $d === '') continue;

        $present = (int)($row['present_count'] ?? 0);
        $absent = (int)($row['absent_count'] ?? 0);

        $pass = (int)($row['pass_count'] ?? 0);
        $fail = (int)($row['fail_count'] ?? 0);

        $status = 'pending';
        if ($absent > 0) $status = 'absent';
        else if ($present > 0) $status = 'present';

        $resStatus = 'pending';
        if ($fail > 0) $resStatus = 'fail';
        else if ($pass > 0) $resStatus = 'pass';

        $dailyMap[$code][$d] = ['attend' => $status, 'result' => $resStatus];

        if (!isset($totals[$code])) $totals[$code] = ['present' => 0, 'absent' => 0];
        $totals[$code]['present'] += $present;
        $totals[$code]['absent'] += $absent;
    }
}

$csrf = csrf_token();

echo render('class_room/index', [
    'title' => 'ดูนักเรียนรายห้อง',
    'csrf' => $csrf,
    'role' => $role,
    'years' => $years,
    'yearId' => $yearId,
    'rooms' => $rooms,
    'selectedLevel' => $selectedLevel,
    'selectedRoom' => $selectedRoom,
    'ym' => $ym,
    'students' => $students,
    'dates' => $dates,
    'dailyMap' => $dailyMap,
    'totals' => $totals,
]);
