<?php
declare(strict_types=1);

require_auth();

$pdo = db_school();

$years = $pdo->query('SELECT id, year_be, title, is_active FROM academic_years ORDER BY year_be DESC')->fetchAll();

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

// Validate yearId exists in list
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

$q = query_string('q');
$classLevel = query_string('class_level');
$room = query_string('room');
$number = query_string('number');

// Filter options: All, ม.4, ม.5, ม.6 (but match common stored variants)
$levelMap = [
    'ม.4' => ['ม.4', 'ม4', 'M4'],
    'ม.5' => ['ม.5', 'ม5', 'M5'],
    'ม.6' => ['ม.6', 'ม6', 'M6'],
];

$levelsToUse = array_merge($levelMap['ม.4'], $levelMap['ม.5'], $levelMap['ม.6']);
if ($classLevel !== '' && isset($levelMap[$classLevel])) {
    $levelsToUse = $levelMap[$classLevel];
}

$where = ['year_id = :year_id'];
$params = [':year_id' => $yearId];

$levelPlaceholders = [];
foreach ($levelsToUse as $i => $lvl) {
    $ph = ':lvl' . $i;
    $levelPlaceholders[] = $ph;
    $params[$ph] = $lvl;
}
$where[] = 'class_level IN (' . implode(',', $levelPlaceholders) . ')';

if ($room !== '' && ctype_digit($room)) {
    $where[] = 'class_room = :room';
    $params[':room'] = (int)$room;
}
if ($number !== '' && ctype_digit($number)) {
    $where[] = 'number_in_room = :number';
    $params[':number'] = (int)$number;
}
if ($q !== '') {
    $where[] = '(student_code LIKE :q OR first_name LIKE :q OR last_name LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}

$sql = 'SELECT student_code, class_level, class_room, number_in_room, first_name, last_name '
     . 'FROM students '
     . 'WHERE ' . implode(' AND ', $where) . ' '
    . 'ORDER BY class_level, class_room, number_in_room, student_code '
    . 'LIMIT 2000';

$students = [];
if ($yearId > 0) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
}

// Provide a clean dropdown for M4/M5/M6
$classLevelOptions = [
    '' => 'ทั้งหมด',
    'ม.4' => 'ม.4',
    'ม.5' => 'ม.5',
    'ม.6' => 'ม.6',
];

echo render('students/index', [
    'title' => 'รายชื่อ (ม4-ม6)',
    'years' => $years,
    'yearId' => $yearId,
    'students' => $students,
    'filters' => [
        'q' => $q,
        'class_level' => $classLevel,
        'room' => $room,
        'number' => $number,
    ],
    'classLevelOptions' => $classLevelOptions,
]);
