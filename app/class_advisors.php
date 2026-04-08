<?php
declare(strict_types=1);

function track_class_advisors_table_ensure(): void
{
    $pdo = db_app();
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS track_class_advisors (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            year_id INT UNSIGNED NOT NULL,
            class_level VARCHAR(20) NOT NULL,
            class_room INT NOT NULL,
            teacher_user_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_year_class (year_id, class_level, class_room),
            KEY idx_teacher (teacher_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );
}

function track_class_level_normalize(string $level): string
{
    $level = trim($level);
    $u = strtoupper(str_replace([' ', '.'], '', $level));
    if (in_array($u, ['ม4', 'M4'], true)) return 'ม.4';
    if (in_array($u, ['ม5', 'M5'], true)) return 'ม.5';
    if (in_array($u, ['ม6', 'M6'], true)) return 'ม.6';
    return $level;
}

/**
 * @return array<string, array<string,mixed>> keyed by "class_level|class_room"
 */
function track_class_advisors_map(int $yearId): array
{
    track_class_advisors_table_ensure();

    $yearId = (int)$yearId;
    if ($yearId <= 0) {
        return [];
    }

    $pdo = db_app();
    $stmt = $pdo->prepare(
        'SELECT a.year_id, a.class_level, a.class_room, a.teacher_user_id, u.displayname, u.username '
        . 'FROM track_class_advisors a '
        . 'LEFT JOIN users u ON u.id = a.teacher_user_id '
        . 'WHERE a.year_id = ?'
    );
    $stmt->execute([$yearId]);

    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $lvl = track_class_level_normalize((string)($row['class_level'] ?? ''));
        $key = $lvl . '|' . (string)($row['class_room'] ?? '');
        $row['class_level'] = $lvl;
        $map[$key] = $row;
    }
    return $map;
}

function track_class_advisor_set(int $yearId, string $classLevel, int $classRoom, ?int $teacherUserId): void
{
    track_class_advisors_table_ensure();

    $yearId = (int)$yearId;
    $classLevel = track_class_level_normalize($classLevel);
    $classRoom = (int)$classRoom;

    if ($yearId <= 0 || $classLevel === '' || $classRoom <= 0) {
        throw new RuntimeException('ข้อมูลชั้นเรียนไม่ถูกต้อง');
    }

    $pdo = db_app();

    if ($teacherUserId === null || (int)$teacherUserId <= 0) {
        $stmt = $pdo->prepare('DELETE FROM track_class_advisors WHERE year_id = ? AND class_level = ? AND class_room = ?');
        $stmt->execute([$yearId, $classLevel, $classRoom]);
        return;
    }

    $teacherUserId = (int)$teacherUserId;
    $stmt = $pdo->prepare(
        'INSERT INTO track_class_advisors (year_id, class_level, class_room, teacher_user_id) '
        . 'VALUES (?, ?, ?, ?) '
        . 'ON DUPLICATE KEY UPDATE teacher_user_id = VALUES(teacher_user_id), updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([$yearId, $classLevel, $classRoom, $teacherUserId]);
}

/** @return array{year_id:int,class_level:string,class_room:int,teacher_user_id:int}|null */
function track_class_advisor_for_teacher(int $yearId, int $teacherUserId): ?array
{
    track_class_advisors_table_ensure();

    $yearId = (int)$yearId;
    $teacherUserId = (int)$teacherUserId;
    if ($yearId <= 0 || $teacherUserId <= 0) {
        return null;
    }

    $pdo = db_app();
    $stmt = $pdo->prepare('SELECT year_id, class_level, class_room, teacher_user_id FROM track_class_advisors WHERE year_id = ? AND teacher_user_id = ? LIMIT 1');
    $stmt->execute([$yearId, $teacherUserId]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    return [
        'year_id' => (int)$row['year_id'],
        'class_level' => track_class_level_normalize((string)($row['class_level'] ?? '')),
        'class_room' => (int)$row['class_room'],
        'teacher_user_id' => (int)$row['teacher_user_id'],
    ];
}

function track_class_advisor_name(int $yearId, string $classLevel, int $classRoom): string
{
    track_class_advisors_table_ensure();

    $yearId = (int)$yearId;
    $classLevel = track_class_level_normalize($classLevel);
    $classRoom = (int)$classRoom;

    if ($yearId <= 0 || $classLevel === '' || $classRoom <= 0) {
        return '';
    }

    $pdo = db_app();
    $stmt = $pdo->prepare(
        'SELECT u.displayname, u.username '
        . 'FROM track_class_advisors a '
        . 'LEFT JOIN users u ON u.id = a.teacher_user_id '
        . 'WHERE a.year_id = ? AND a.class_level = ? AND a.class_room = ? '
        . 'LIMIT 1'
    );
    $stmt->execute([$yearId, $classLevel, $classRoom]);
    $row = $stmt->fetch();
    if (!is_array($row)) {
        return '';
    }

    $name = trim((string)($row['displayname'] ?? ''));
    if ($name !== '') {
        return $name;
    }
    return trim((string)($row['username'] ?? ''));
}
