<?php
declare(strict_types=1);

function track_subjects_table_ensure(): void
{
    $pdo = db_app();
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS track_subjects (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(150) NOT NULL,
            description TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_track_subjects_title (title)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );
}

/** @return array<int, array<string, mixed>> */
function track_subjects_all(): array
{
    track_subjects_table_ensure();
    $pdo = db_app();
    return $pdo->query('SELECT id, title, description, is_active, created_at, updated_at FROM track_subjects ORDER BY is_active DESC, title ASC')->fetchAll();
}

function track_subject_create(string $title, string $description, bool $isActive): void
{
    track_subjects_table_ensure();

    $title = trim($title);
    if ($title === '') {
        throw new RuntimeException('กรุณากรอกชื่อวิชา');
    }

    $pdo = db_app();
    $stmt = $pdo->prepare('INSERT INTO track_subjects (title, description, is_active) VALUES (:t, :d, :a)');
    $stmt->execute([
        ':t' => $title,
        ':d' => ($description !== '') ? $description : null,
        ':a' => $isActive ? 1 : 0,
    ]);
}

function track_subject_update(int $id, string $title, string $description, bool $isActive): void
{
    track_subjects_table_ensure();

    if ($id <= 0) {
        throw new RuntimeException('ข้อมูลไม่ถูกต้อง');
    }

    $title = trim($title);
    if ($title === '') {
        throw new RuntimeException('กรุณากรอกชื่อวิชา');
    }

    $pdo = db_app();
    $stmt = $pdo->prepare('UPDATE track_subjects SET title = :t, description = :d, is_active = :a WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':t' => $title,
        ':d' => ($description !== '') ? $description : null,
        ':a' => $isActive ? 1 : 0,
    ]);
}

function track_subject_delete(int $id): void
{
    track_subjects_table_ensure();

    if ($id <= 0) {
        throw new RuntimeException('ข้อมูลไม่ถูกต้อง');
    }

    $pdo = db_app();
    $stmt = $pdo->prepare('DELETE FROM track_subjects WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function track_subjects_export_csv(array $subjects, string $filename = 'track_subjects.csv'): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // UTF-8 BOM for Excel compatibility (Thai)
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    if ($out === false) {
        exit;
    }

    fputcsv($out, ['title', 'description', 'is_active']);
    foreach ($subjects as $s) {
        $title = (string)($s['title'] ?? '');
        $description = (string)($s['description'] ?? '');
        $isActive = (int)($s['is_active'] ?? 1);
        fputcsv($out, [$title, $description, (string)$isActive]);
    }
    fclose($out);
    exit;
}

/**
 * Import track subjects from CSV file.
 *
 * @return array{inserted:int,updated:int,skipped:int,errors:int}
 */
function track_subjects_import_csv(string $csvPath, string $mode = 'upsert'): array
{
    track_subjects_table_ensure();

    if (!is_file($csvPath)) {
        throw new RuntimeException('ไม่พบไฟล์ CSV');
    }

    $handle = fopen($csvPath, 'r');
    if ($handle === false) {
        throw new RuntimeException('ไม่สามารถเปิดไฟล์ CSV');
    }

    $stats = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

    $pdo = db_app();
    $pdo->beginTransaction();

    try {
        $stmtInsertIgnore = $pdo->prepare('INSERT IGNORE INTO track_subjects (title, description, is_active) VALUES (:t, :d, :a)');
        $stmtUpsert = $pdo->prepare('INSERT INTO track_subjects (title, description, is_active) VALUES (:t, :d, :a) ON DUPLICATE KEY UPDATE description = VALUES(description), is_active = VALUES(is_active)');

        $col = ['title' => 0, 'description' => 1, 'is_active' => 2];
        $firstRow = true;

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === false) {
                continue;
            }

            // Trim all fields
            $row = array_map(static fn($v) => is_string($v) ? trim($v) : '', $row);

            if ($firstRow) {
                $firstRow = false;
                $lower = array_map(static fn($v) => mb_strtolower((string)$v), $row);

                // Detect header row (english/thai)
                $hasTitleHeader = in_array('title', $lower, true) || in_array('ชื่อวิชา', $lower, true);
                if ($hasTitleHeader) {
                    $map = ['title' => null, 'description' => null, 'is_active' => null];
                    foreach ($lower as $i => $name) {
                        if ($name === 'title' || $name === 'ชื่อวิชา') $map['title'] = $i;
                        if ($name === 'description' || $name === 'รายละเอียด') $map['description'] = $i;
                        if ($name === 'is_active' || $name === 'active' || $name === 'สถานะ') $map['is_active'] = $i;
                    }
                    if ($map['title'] !== null) {
                        $col['title'] = (int)$map['title'];
                    }
                    if ($map['description'] !== null) {
                        $col['description'] = (int)$map['description'];
                    }
                    if ($map['is_active'] !== null) {
                        $col['is_active'] = (int)$map['is_active'];
                    }
                    continue;
                }
                // otherwise treat as data row
            }

            $title = (string)($row[$col['title']] ?? '');
            $description = (string)($row[$col['description']] ?? '');
            $activeRaw = (string)($row[$col['is_active']] ?? '1');

            if ($title === '') {
                $stats['skipped']++;
                continue;
            }

            $isActive = 1;
            $activeLower = mb_strtolower($activeRaw);
            if ($activeLower === '0' || $activeLower === 'false' || $activeLower === 'no' || $activeLower === 'ปิด') {
                $isActive = 0;
            }
            if ($activeLower === '1' || $activeLower === 'true' || $activeLower === 'yes' || $activeLower === 'เปิด') {
                $isActive = 1;
            }

            $params = [':t' => $title, ':d' => ($description !== '' ? $description : null), ':a' => $isActive];

            if ($mode === 'skip') {
                $stmtInsertIgnore->execute($params);
                $affected = $stmtInsertIgnore->rowCount();
                if ($affected === 1) {
                    $stats['inserted']++;
                } else {
                    $stats['skipped']++;
                }
            } else {
                $stmtUpsert->execute($params);
                $affected = $stmtUpsert->rowCount();
                if ($affected === 1) {
                    $stats['inserted']++;
                } elseif ($affected >= 2) {
                    $stats['updated']++;
                }
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        fclose($handle);
        throw $e;
    }

    fclose($handle);
    return $stats;
}
