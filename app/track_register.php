<?php
declare(strict_types=1);

function track_registrations_table_ensure(): void
{
    $pdo = db_app();
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS track_registrations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            year_id INT UNSIGNED NOT NULL,
            student_code VARCHAR(20) NOT NULL,
            subject_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_track_reg (year_id, student_code, subject_id),
            KEY idx_track_reg_year_subject (year_id, subject_id),
            KEY idx_track_reg_year_student (year_id, student_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );
}

/** @return array<string, array<int, string>> */
function class_level_variants_map(): array
{
    return [
        'ม.4' => ['ม.4', 'ม4', 'M4'],
        'ม.5' => ['ม.5', 'ม5', 'M5'],
        'ม.6' => ['ม.6', 'ม6', 'M6'],
    ];
}

/** @return array<int, string> */
function class_levels_for_filter(string $level): array
{
    $map = class_level_variants_map();
    if ($level !== '' && isset($map[$level])) {
        return $map[$level];
    }
    return array_merge($map['ม.4'], $map['ม.5'], $map['ม.6']);
}

/**
 * Bulk assign subject to students.
 * @param array<int, string> $studentCodes
 */
function track_reg_assign_bulk(int $yearId, int $subjectId, array $studentCodes): int
{
    track_registrations_table_ensure();

    if ($yearId <= 0 || $subjectId <= 0) {
        throw new RuntimeException('ข้อมูลไม่ถูกต้อง');
    }

    $studentCodes = array_values(array_unique(array_filter(array_map('trim', $studentCodes), static fn($v) => $v !== '')));
    if (count($studentCodes) === 0) {
        throw new RuntimeException('กรุณาเลือกนักเรียน');
    }

    $pdo = db_app();
    $stmt = $pdo->prepare('INSERT IGNORE INTO track_registrations (year_id, student_code, subject_id) VALUES (:y, :c, :s)');

    $inserted = 0;
    foreach ($studentCodes as $code) {
        $stmt->execute([':y' => $yearId, ':c' => $code, ':s' => $subjectId]);
        $inserted += $stmt->rowCount();
    }

    return $inserted;
}

/**
 * Bulk unassign subject from students.
 * @param array<int, string> $studentCodes
 */
function track_reg_unassign_bulk(int $yearId, int $subjectId, array $studentCodes): int
{
    track_registrations_table_ensure();

    if ($yearId <= 0 || $subjectId <= 0) {
        throw new RuntimeException('ข้อมูลไม่ถูกต้อง');
    }

    $studentCodes = array_values(array_unique(array_filter(array_map('trim', $studentCodes), static fn($v) => $v !== '')));
    if (count($studentCodes) === 0) {
        throw new RuntimeException('กรุณาเลือกนักเรียน');
    }

    $pdo = db_app();

    $placeholders = implode(',', array_fill(0, count($studentCodes), '?'));
    $sql = "DELETE FROM track_registrations WHERE year_id = ? AND subject_id = ? AND student_code IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$yearId, $subjectId], $studentCodes));
    return $stmt->rowCount();
}

function track_reg_delete_by_id(int $id): void
{
    track_registrations_table_ensure();

    if ($id <= 0) {
        throw new RuntimeException('ข้อมูลไม่ถูกต้อง');
    }

    $pdo = db_app();
    $stmt = $pdo->prepare('DELETE FROM track_registrations WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

/** @return array<string, mixed>|null */
function school_student_get(int $yearId, string $studentCode): ?array
{
    $studentCode = trim($studentCode);
    if ($yearId <= 0 || $studentCode === '') {
        return null;
    }

    $pdo = db_school();
    $stmt = $pdo->prepare('SELECT year_id, student_code, first_name, last_name, class_level, class_room, number_in_room FROM students WHERE year_id = :y AND student_code = :c LIMIT 1');
    $stmt->execute([':y' => $yearId, ':c' => $studentCode]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Fetch students for selection.
 * @return array<int, array<string, mixed>>
 */
function school_students_for_track(int $yearId, string $level, string $room, string $q, int $limit = 300): array
{
    $pdo = db_school();

    $levels = class_levels_for_filter($level);

    // Use positional placeholders to avoid PDO native prepare issues with repeated named params.
    $where = ['year_id = ?'];
    $params = [$yearId];

    $lvlPlaceholders = implode(',', array_fill(0, count($levels), '?'));
    $where[] = 'class_level IN (' . $lvlPlaceholders . ')';
    foreach ($levels as $lvl) {
        $params[] = $lvl;
    }

    if ($room !== '' && ctype_digit($room)) {
        $where[] = 'class_room = ?';
        $params[] = (int)$room;
    }

    if ($q !== '') {
        $where[] = '(student_code LIKE ? OR first_name LIKE ? OR last_name LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $sql = 'SELECT year_id, student_code, first_name, last_name, class_level, class_room, number_in_room '
        . 'FROM students '
        . 'WHERE ' . implode(' AND ', $where) . ' '
        . 'ORDER BY class_level, class_room, number_in_room, student_code '
        . 'LIMIT ' . (int)$limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * List registrations for current filtered student set.
 * @return array<int, array<string, mixed>>
 */
function track_registrations_list(int $yearId, string $level, string $room, string $q, int $limit = 500, int $offset = 0): array
{
    track_registrations_table_ensure();
    track_subjects_table_ensure();

    $pdo = db_app();

    // Build join across databases
    $levels = class_levels_for_filter($level);

    // Use positional placeholders to avoid repeating named parameters (PDO native prepares).
    $where = ['r.year_id = ?', 's.year_id = ?'];
    $params = [$yearId, $yearId];

    $lvlPlaceholders = implode(',', array_fill(0, count($levels), '?'));
    $where[] = 's.class_level IN (' . $lvlPlaceholders . ')';
    foreach ($levels as $lvl) {
        $params[] = $lvl;
    }

    if ($room !== '' && ctype_digit($room)) {
        $where[] = 's.class_room = ?';
        $params[] = (int)$room;
    }

    if ($q !== '') {
        $where[] = '(s.student_code LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR subj.title LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $offset = max(0, $offset);

    $sql = 'SELECT r.id, r.year_id, r.student_code, r.subject_id, r.created_at, '
        . 's.first_name, s.last_name, s.class_level, s.class_room, s.number_in_room, '
        . 'subj.title AS subject_title '
        . 'FROM track_registrations r '
        . 'JOIN ' . DB_SCHOOL . '.students s ON s.year_id = r.year_id AND s.student_code = r.student_code '
        . 'JOIN track_subjects subj ON subj.id = r.subject_id '
        . 'WHERE ' . implode(' AND ', $where) . ' '
        . 'ORDER BY s.class_level, s.class_room, s.number_in_room, s.student_code, subj.title '
        . 'LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function track_registrations_count(int $yearId, string $level, string $room, string $q): int
{
    track_registrations_table_ensure();
    track_subjects_table_ensure();

    $pdo = db_app();
    $levels = class_levels_for_filter($level);

    $where = ['r.year_id = ?', 's.year_id = ?'];
    $params = [$yearId, $yearId];

    $lvlPlaceholders = implode(',', array_fill(0, count($levels), '?'));
    $where[] = 's.class_level IN (' . $lvlPlaceholders . ')';
    foreach ($levels as $lvl) {
        $params[] = $lvl;
    }

    if ($room !== '' && ctype_digit($room)) {
        $where[] = 's.class_room = ?';
        $params[] = (int)$room;
    }

    if ($q !== '') {
        $where[] = '(s.student_code LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR subj.title LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $sql = 'SELECT COUNT(*) AS c '
        . 'FROM track_registrations r '
        . 'JOIN ' . DB_SCHOOL . '.students s ON s.year_id = r.year_id AND s.student_code = r.student_code '
        . 'JOIN track_subjects subj ON subj.id = r.subject_id '
        . 'WHERE ' . implode(' AND ', $where);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

/**
 * List registrations for a single student.
 * @return array<int, array<string, mixed>>
 */
function track_registrations_for_student(int $yearId, string $studentCode): array
{
    track_registrations_table_ensure();
    track_subjects_table_ensure();

    $studentCode = trim($studentCode);
    if ($yearId <= 0 || $studentCode === '') {
        return [];
    }

    $pdo = db_app();
    $stmt = $pdo->prepare(
        'SELECT r.id, r.year_id, r.student_code, r.subject_id, r.created_at, subj.title AS subject_title, subj.description AS subject_description '
        . 'FROM track_registrations r '
        . 'JOIN track_subjects subj ON subj.id = r.subject_id '
        . 'WHERE r.year_id = ? AND r.student_code = ? '
        . 'ORDER BY subj.title'
    );
    $stmt->execute([$yearId, $studentCode]);
    return $stmt->fetchAll();
}
