<?php
declare(strict_types=1);

require_auth();

$me = auth_user();
$role = is_array($me) ? (string)($me['role'] ?? 'teacher') : 'teacher';
if ($role !== 'admin') {
    redirect('class_attendance');
}

users_table_ensure();
track_class_advisors_table_ensure();

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

$yearIdValid = false;
foreach ($years as $y) {
    if ((int)$y['id'] === $yearId) {
        $yearIdValid = true;
        break;
    }
}
if (!$yearIdValid) {
    $yearId = $defaultYearId;
}

$error = null;
$success = flash_get('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = input_string('action');

    try {
        if ($action === 'set_advisor') {
            $postYearId = (int)input_string('year_id');
            $classLevel = input_string('class_level');
            $classRoom = (int)input_string('class_room');
            $teacherUserId = (int)input_string('teacher_user_id');

            $teacherIdOrNull = $teacherUserId > 0 ? $teacherUserId : null;
            track_class_advisor_set($postYearId, $classLevel, $classRoom, $teacherIdOrNull);

            flash_set('success', 'บันทึกครูประจำชั้นแล้ว');
            header('Location: /tracks/class_advisors?year_id=' . (int)$postYearId);
            exit;
        }

        throw new RuntimeException('คำสั่งไม่ถูกต้อง');
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'เกิดข้อผิดพลาด';
    }
}

$classes = [];
if ($yearId > 0) {
    $levels = ['ม.4', 'ม4', 'M4', 'ม.5', 'ม5', 'M5', 'ม.6', 'ม6', 'M6'];
    $ph = [];
    $params = [':year_id' => $yearId];
    foreach ($levels as $i => $lvl) {
        $k = ':lvl' . $i;
        $ph[] = $k;
        $params[$k] = $lvl;
    }

    $stmt = $pdoSchool->prepare(
        'SELECT '
        . "CASE "
        . " WHEN class_level IN ('ม.4','ม4','M4') THEN 'ม.4' "
        . " WHEN class_level IN ('ม.5','ม5','M5') THEN 'ม.5' "
        . " WHEN class_level IN ('ม.6','ม6','M6') THEN 'ม.6' "
        . " ELSE class_level END AS class_level, "
        . 'class_room, COUNT(*) AS student_count '
        . 'FROM students '
        . 'WHERE year_id = :year_id AND class_room IS NOT NULL AND class_level IN (' . implode(',', $ph) . ') '
        . 'GROUP BY class_level, class_room '
        . 'ORDER BY class_level, class_room'
    );
    $stmt->execute($params);
    $classes = $stmt->fetchAll();
}

$pdoApp = db_app();
$teachers = $pdoApp->query("SELECT id, displayname, username FROM users WHERE role = 'teacher' ORDER BY displayname, username")->fetchAll();

$advisorMap = track_class_advisors_map($yearId);

$csrf = csrf_token();

echo render('class_advisors/index', [
    'title' => 'กำหนดครูประจำชั้น',
    'csrf' => $csrf,
    'years' => $years,
    'yearId' => $yearId,
    'classes' => $classes,
    'teachers' => $teachers,
    'advisorMap' => $advisorMap,
    'error' => $error,
    'success' => $success,
]);
