<?php
declare(strict_types=1);

function class_groups_table_ensure(): void
{
    $pdo = db_app();

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS class_groups (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(150) NOT NULL,
            student_codes TEXT NOT NULL DEFAULT '',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );
}

/** @return array<int, array<string, mixed>> */
function class_groups_all(): array
{
    class_groups_table_ensure();
    $pdo = db_app();
    return $pdo->query(
        'SELECT id, title, student_codes, created_at, updated_at FROM class_groups ORDER BY title ASC'
    )->fetchAll();
}

/** @return array<string, mixed>|null */
function class_group_get(int $id): ?array
{
    if ($id <= 0) return null;
    class_groups_table_ensure();
    $pdo = db_app();
    $stmt = $pdo->prepare('SELECT id, title, student_codes, created_at, updated_at FROM class_groups WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

/**
 * Returns list of student codes stored in a group.
 * @return string[]
 */
function class_group_codes(int $id): array
{
    $row = class_group_get($id);
    if ($row === null) return [];
    return class_group_parse_codes((string)($row['student_codes'] ?? ''));
}

/**
 * Parse codes from raw textarea text (one per line).
 * @return string[]
 */
function class_group_parse_codes(string $raw): array
{
    $lines = preg_split('/\R/u', $raw) ?: [];
    $codes = [];
    foreach ($lines as $line) {
        $line = trim((string)$line);
        if ($line === '') continue;
        // Support "65001" or "ชื่อ นามสกุล 65001" (extract last number sequence)
        if (preg_match_all('/\d{3,}/u', $line, $m) && !empty($m[0])) {
            $codes[] = (string)end($m[0]);
        }
    }
    // deduplicate
    return array_values(array_unique($codes));
}

function class_group_create(string $title, string $codesText): int
{
    class_groups_table_ensure();

    $title = trim($title);
    if ($title === '') {
        throw new RuntimeException('กรุณากรอกชื่อกลุ่มเรียน');
    }

    $pdo = db_app();
    $stmt = $pdo->prepare('INSERT INTO class_groups (title, student_codes) VALUES (:t, :c)');
    $stmt->execute([':t' => $title, ':c' => trim($codesText)]);
    return (int)$pdo->lastInsertId();
}

function class_group_update(int $id, string $title, string $codesText): void
{
    class_groups_table_ensure();

    if ($id <= 0) {
        throw new RuntimeException('ข้อมูลไม่ถูกต้อง');
    }

    $title = trim($title);
    if ($title === '') {
        throw new RuntimeException('กรุณากรอกชื่อกลุ่มเรียน');
    }

    $pdo = db_app();
    $stmt = $pdo->prepare('UPDATE class_groups SET title = :t, student_codes = :c WHERE id = :id');
    $stmt->execute([':id' => $id, ':t' => $title, ':c' => trim($codesText)]);
}

function class_group_delete(int $id): void
{
    class_groups_table_ensure();

    if ($id <= 0) {
        throw new RuntimeException('ข้อมูลไม่ถูกต้อง');
    }

    $pdo = db_app();
    $stmt = $pdo->prepare('DELETE FROM class_groups WHERE id = :id');
    $stmt->execute([':id' => $id]);
}
