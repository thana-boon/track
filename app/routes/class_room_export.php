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

$pdoSchool   = db_school();
$years       = $pdoSchool->query('SELECT id, year_be, title, is_active FROM academic_years ORDER BY year_be DESC')->fetchAll();
$activeYearId = (int)($pdoSchool->query('SELECT id FROM academic_years WHERE is_active = 1 ORDER BY id DESC LIMIT 1')->fetchColumn() ?: 0);

$yearId = (int)query_string('year_id');
if ($yearId <= 0) $yearId = $activeYearId;

$term = term_from_request(track_active_term());

// Load all available rooms
$rooms = [];
if ($yearId > 0) {
    $s = $pdoSchool->prepare(
        'SELECT CASE '
        . " WHEN class_level IN ('ม.4','ม4','M4') THEN 'ม.4' "
        . " WHEN class_level IN ('ม.5','ม5','M5') THEN 'ม.5' "
        . " WHEN class_level IN ('ม.6','ม6','M6') THEN 'ม.6' "
        . " ELSE class_level END AS class_level, "
        . 'class_room, COUNT(*) AS student_count '
        . "FROM students WHERE year_id = :year_id AND class_room IS NOT NULL "
        . "AND class_level IN ('ม.4','ม4','M4','ม.5','ม5','M5','ม.6','ม6','M6') "
        . 'GROUP BY class_level, class_room ORDER BY class_level, class_room'
    );
    $s->execute([':year_id' => $yearId]);
    $rooms = $s->fetchAll();
}

// Teacher can only see their room
if ($role === 'teacher') {
    $teacherId = (int)($me['id'] ?? 0);
    $assignment = track_class_advisor_for_teacher($yearId, $teacherId);
    if (!$assignment) {
        http_response_code(404);
        echo render('errors/404', ['title' => 'ไม่พบห้อง', 'path' => 'class_room_export']);
        exit;
    }
    $assignedLevel = (string)$assignment['class_level'];
    $assignedRoom  = (int)$assignment['class_room'];
    $rooms = array_values(array_filter($rooms, static function ($r) use ($assignedLevel, $assignedRoom) {
        return (string)($r['class_level'] ?? '') === $assignedLevel && (int)($r['class_room'] ?? 0) === $assignedRoom;
    }));
}

// Default selections from referrer (class_room index)
$defaultLevel = (string)query_string('class_level');
$defaultRoom  = (int)query_string('class_room');
$defaultYm    = (string)query_string('ym');
if ($defaultYm === '' || !preg_match('/^\d{4}-\d{2}$/', $defaultYm)) {
    $defaultYm = date('Y-m');
}

// ── Export CSV ──────────────────────────────────────────────────────────────
if (query_string('export') === '1') {
    $rawRooms = isset($_GET['rooms']) && is_array($_GET['rooms']) ? $_GET['rooms'] : [];
    $ymStart  = (string)query_string('ym_start');
    $ymEnd    = (string)query_string('ym_end');

    if (!preg_match('/^\d{4}-\d{2}$/', $ymStart)) $ymStart = $defaultYm;
    if (!preg_match('/^\d{4}-\d{2}$/', $ymEnd))   $ymEnd   = $ymStart;
    if ($ymEnd < $ymStart) $ymEnd = $ymStart;

    try {
        $dateStart = new DateTimeImmutable($ymStart . '-01');
        $dateEnd   = (new DateTimeImmutable($ymEnd . '-01'))->modify('last day of this month');
    } catch (Throwable $e) {
        $dateStart = new DateTimeImmutable(date('Y-m-01'));
        $dateEnd   = $dateStart->modify('last day of this month');
    }

    // Parse room specs: "ม.5|1" → {level, room}
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

    if (!empty($roomSpecs)) {
        $pdoApp   = db_app();
        $orParts  = [];
        $params   = [$yearId, $term, $dateStart->format('Y-m-d'), $dateEnd->format('Y-m-d')];

        foreach ($roomSpecs as $rs) {
            $lvl = (string)$rs['level'];
            $rm  = (int)$rs['room'];
            $variants = match ($lvl) {
                'ม.4' => ['ม.4', 'ม4', 'M4'],
                'ม.5' => ['ม.5', 'ม5', 'M5'],
                'ม.6' => ['ม.6', 'ม6', 'M6'],
                default => [$lvl],
            };
            $ph = implode(',', array_fill(0, count($variants), '?'));
            $orParts[] = '(st.class_room = ? AND st.class_level IN (' . $ph . '))';
            $params[]  = $rm;
            foreach ($variants as $v) $params[] = $v;
        }

        $sql =
            'SELECT '
            . 'CASE '
            . " WHEN st.class_level IN ('ม.4','ม4','M4') THEN 'ม.4' "
            . " WHEN st.class_level IN ('ม.5','ม5','M5') THEN 'ม.5' "
            . " WHEN st.class_level IN ('ม.6','ม6','M6') THEN 'ม.6' "
            . ' ELSE st.class_level END AS class_level, '
            . 'st.class_room, st.number_in_room, st.first_name, st.last_name, st.student_code, '
            . 'subj.title AS subject_title, '
            . 'MAX(CASE WHEN cs.result_status = \'excellent\' THEN 1 ELSE 0 END) AS has_excellent, '
            . 'MAX(CASE WHEN cs.result_status = \'pass\'      THEN 1 ELSE 0 END) AS has_pass, '
            . 'MAX(CASE WHEN cs.result_status = \'fail\'      THEN 1 ELSE 0 END) AS has_fail, '
            . 'MAX(sess.session_date) AS last_date '
            . 'FROM track_class_students cs '
            . 'JOIN track_class_sessions sess ON sess.id = cs.session_id '
            . 'JOIN track_subjects subj ON subj.id = sess.subject_id '
            . 'JOIN ' . DB_SCHOOL . '.students st ON st.year_id = sess.year_id AND st.student_code = cs.student_code '
            . 'WHERE sess.year_id = ? AND sess.term = ? AND sess.session_date BETWEEN ? AND ? '
            . 'AND (' . implode(' OR ', $orParts) . ') '
            . 'GROUP BY st.class_level, st.class_room, st.number_in_room, st.student_code, st.first_name, st.last_name, subj.id, subj.title '
            . 'ORDER BY class_level, st.class_room, st.number_in_room, st.student_code, subj.title';

        $stmt = $pdoApp->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $filename = 'ผลการเรียน_' . $ymStart . '_ถึง_' . $ymEnd . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        if ($out !== false) {
            fputcsv($out, ['ห้อง', 'เลขที่', 'ชื่อ', 'นามสกุล', 'รหัสนักเรียน', 'วิชา', 'ผล', 'วันที่ล่าสุด']);
            foreach ($rows as $r) {
                $lvl  = (string)($r['class_level'] ?? '');
                $rm   = (string)($r['class_room'] ?? '');
                $no   = (string)($r['number_in_room'] ?? '');
                $fn   = (string)($r['first_name'] ?? '');
                $ln   = (string)($r['last_name'] ?? '');
                $code = (string)($r['student_code'] ?? '');
                $subj = (string)($r['subject_title'] ?? '');
                $ld   = (string)($r['last_date'] ?? '');

                $hasExcellent = (int)($r['has_excellent'] ?? 0) === 1;
                $hasPass      = (int)($r['has_pass'] ?? 0) === 1;
                $hasFail      = (int)($r['has_fail'] ?? 0) === 1;

                if ($hasFail)           $result = 'ไม่ผ่าน';
                elseif ($hasExcellent)  $result = 'ยอดเยี่ยม';
                elseif ($hasPass)       $result = 'ผ่าน';
                else                    $result = 'รอดำเนินการ';

                fputcsv($out, [$lvl . '/' . $rm, $no, $fn, $ln, $code, $subj, $result, $ld]);
            }
            fclose($out);
        }
        exit;
    }
}

// ── Show form ────────────────────────────────────────────────────────────────
echo render('class_room/export_form', [
    'title'        => 'Export ผลการเรียน (Excel/CSV)',
    'yearId'       => $yearId,
    'years'        => $years,
    'term'         => $term,
    'rooms'        => $rooms,
    'role'         => $role,
    'defaultLevel' => $defaultLevel,
    'defaultRoom'  => $defaultRoom,
    'defaultYm'    => $defaultYm,
]);
