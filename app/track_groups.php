<?php
declare(strict_types=1);

function track_groups_table_ensure(): void
{
    $pdo = db_app();

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS track_groups (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(150) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_track_groups_title (title)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );
}

/** @return array<int, array<string, mixed>> */
function track_groups_all(): array
{
    track_groups_table_ensure();
    $pdo = db_app();
    return $pdo->query('SELECT id, title, is_active, created_at, updated_at FROM track_groups ORDER BY is_active DESC, title ASC')->fetchAll();
}

function track_group_create(string $title, bool $isActive): void
{
    track_groups_table_ensure();

    $title = trim($title);
    if ($title === '') {
        throw new RuntimeException('กรุณากรอกชื่อกลุ่ม');
    }

    $pdo = db_app();
    $stmt = $pdo->prepare('INSERT INTO track_groups (title, is_active) VALUES (:t, :a)');
    $stmt->execute([
        ':t' => $title,
        ':a' => $isActive ? 1 : 0,
    ]);
}

function track_group_update(int $id, string $title, bool $isActive): void
{
    track_groups_table_ensure();

    if ($id <= 0) {
        throw new RuntimeException('ข้อมูลไม่ถูกต้อง');
    }

    $title = trim($title);
    if ($title === '') {
        throw new RuntimeException('กรุณากรอกชื่อกลุ่ม');
    }

    $pdo = db_app();
    $stmt = $pdo->prepare('UPDATE track_groups SET title = :t, is_active = :a WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':t' => $title,
        ':a' => $isActive ? 1 : 0,
    ]);
}

function track_group_delete(int $id): void
{
    track_groups_table_ensure();

    if ($id <= 0) {
        throw new RuntimeException('ข้อมูลไม่ถูกต้อง');
    }

    $pdo = db_app();

    // Prevent deletion if used by subjects.
    try {
        track_subjects_table_ensure();
        $stmt = $pdo->prepare('SELECT 1 FROM track_subjects WHERE group_id = ? LIMIT 1');
        $stmt->execute([$id]);
        if ((bool)$stmt->fetchColumn()) {
            throw new RuntimeException('ลบไม่ได้: มีกลุ่มนี้ถูกใช้อยู่ในวิชา');
        }
    } catch (RuntimeException $e) {
        throw $e;
    } catch (Throwable $e) {
        // ignore best-effort check
    }

    $stmt = $pdo->prepare('DELETE FROM track_groups WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function track_group_exists(int $id): bool
{
    if ($id <= 0) return false;
    track_groups_table_ensure();
    $pdo = db_app();
    $stmt = $pdo->prepare('SELECT 1 FROM track_groups WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return (bool)$stmt->fetchColumn();
}
