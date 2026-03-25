<?php
declare(strict_types=1);

require_auth();

$pdo = db_school();

// Pick active academic year if exists; otherwise latest
$yearStmt = $pdo->query('SELECT id, year_be, title, is_active FROM academic_years ORDER BY is_active DESC, year_be DESC LIMIT 1');
$activeYear = $yearStmt->fetch() ?: null;

$yearId = $activeYear ? (int)$activeYear['id'] : 0;

$counts = [
    'students_m456' => 0,
];

// Correct counts with proper fetchColumn
if ($yearId > 0) {
    $allowed = [
        'ม4','ม5','ม6',
        'ม.4','ม.5','ม.6',
        'M4','M5','M6',
    ];
    $in = implode(',', array_fill(0, count($allowed), '?'));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE year_id = ? AND class_level IN ($in)");
    $stmt->execute(array_merge([$yearId], $allowed));
    $counts['students_m456'] = (int)$stmt->fetchColumn();
}

echo render('dashboard', [
    'title' => 'Dashboard',
    'activeYear' => $activeYear,
    'counts' => $counts,
]);
