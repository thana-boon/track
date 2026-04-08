<?php
declare(strict_types=1);

function track_subjects_table_ensure(): void
{
    $pdo = db_app();
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS track_subjects (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id INT UNSIGNED NULL DEFAULT NULL,
            subject_code VARCHAR(30) NULL DEFAULT NULL,
            title VARCHAR(150) NOT NULL,
            description TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_track_subjects_title (title),
            UNIQUE KEY uq_track_subjects_code (subject_code),
            KEY idx_track_subjects_group (group_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    // Best-effort migrations for older installs
    $migrations = [
        "ALTER TABLE track_subjects ADD COLUMN group_id INT UNSIGNED NULL DEFAULT NULL",
        "ALTER TABLE track_subjects ADD COLUMN subject_code VARCHAR(30) NULL DEFAULT NULL",
        "ALTER TABLE track_subjects ADD UNIQUE KEY uq_track_subjects_code (subject_code)",
        "ALTER TABLE track_subjects ADD KEY idx_track_subjects_group (group_id)",
    ];
    foreach ($migrations as $sql) {
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
            // ignore
        }
    }
}

/** @return array<int, array<string, mixed>> */
function track_subjects_all(): array
{
    track_subjects_table_ensure();
    $pdo = db_app();
    track_groups_table_ensure();
    return $pdo->query(
        'SELECT s.id, s.group_id, s.subject_code, s.title, s.description, s.is_active, s.created_at, s.updated_at, '
        . 'g.title AS group_title '
        . 'FROM track_subjects s '
        . 'LEFT JOIN track_groups g ON g.id = s.group_id '
        . 'ORDER BY s.is_active DESC, s.title ASC'
    )->fetchAll();
}

function track_subject_create(int $groupId, string $subjectCode, string $title, string $description, bool $isActive): void
{
    track_subjects_table_ensure();

    if ($groupId <= 0 || !track_group_exists($groupId)) {
        throw new RuntimeException('กรุณาเลือกกลุ่ม Track');
    }

    $subjectCode = trim($subjectCode);
    if ($subjectCode === '') {
        throw new RuntimeException('กรุณากรอกรหัสวิชา');
    }

    $title = trim($title);
    if ($title === '') {
        throw new RuntimeException('กรุณากรอกชื่อวิชา');
    }

    $pdo = db_app();
    $stmt = $pdo->prepare('INSERT INTO track_subjects (group_id, subject_code, title, description, is_active) VALUES (:g, :c, :t, :d, :a)');
    $stmt->execute([
        ':g' => $groupId,
        ':c' => $subjectCode,
        ':t' => $title,
        ':d' => ($description !== '') ? $description : null,
        ':a' => $isActive ? 1 : 0,
    ]);
}

function track_subject_update(int $id, int $groupId, string $subjectCode, string $title, string $description, bool $isActive): void
{
    track_subjects_table_ensure();

    if ($id <= 0) {
        throw new RuntimeException('ข้อมูลไม่ถูกต้อง');
    }

    if ($groupId <= 0 || !track_group_exists($groupId)) {
        throw new RuntimeException('กรุณาเลือกกลุ่ม Track');
    }

    $subjectCode = trim($subjectCode);
    if ($subjectCode === '') {
        throw new RuntimeException('กรุณากรอกรหัสวิชา');
    }

    $title = trim($title);
    if ($title === '') {
        throw new RuntimeException('กรุณากรอกชื่อวิชา');
    }

    $pdo = db_app();
    $stmt = $pdo->prepare('UPDATE track_subjects SET group_id = :g, subject_code = :c, title = :t, description = :d, is_active = :a WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':g' => $groupId,
        ':c' => $subjectCode,
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

    fputcsv($out, ['subject_code', 'title', 'group_title', 'description', 'is_active']);
    foreach ($subjects as $s) {
        $code = (string)($s['subject_code'] ?? '');
        $title = (string)($s['title'] ?? '');
        $groupTitle = (string)($s['group_title'] ?? '');
        $description = (string)($s['description'] ?? '');
        $isActive = (int)($s['is_active'] ?? 1);
        fputcsv($out, [$code, $title, $groupTitle, $description, (string)$isActive]);
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
    track_groups_table_ensure();

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
        $stmtInsertIgnore = $pdo->prepare('INSERT IGNORE INTO track_subjects (group_id, subject_code, title, description, is_active) VALUES (:g, :c, :t, :d, :a)');
        $stmtUpsert = $pdo->prepare(
            'INSERT INTO track_subjects (group_id, subject_code, title, description, is_active) '
            . 'VALUES (:g, :c, :t, :d, :a) '
            . 'ON DUPLICATE KEY UPDATE group_id = VALUES(group_id), title = VALUES(title), description = VALUES(description), is_active = VALUES(is_active)'
        );

        $stmtGroupInsert = $pdo->prepare('INSERT IGNORE INTO track_groups (title, is_active) VALUES (?, 1)');
        $stmtGroupGetId = $pdo->prepare('SELECT id FROM track_groups WHERE title = ? LIMIT 1');

        // Default (legacy) format: title,description,is_active
        $col = ['subject_code' => null, 'title' => 0, 'group_title' => null, 'description' => 1, 'is_active' => 2];
        $firstRow = true;
        $autoDetected = false;

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
                    $map = ['subject_code' => null, 'title' => null, 'group_title' => null, 'description' => null, 'is_active' => null];
                    foreach ($lower as $i => $name) {
                        if ($name === 'subject_code' || $name === 'code' || $name === 'รหัสวิชา') $map['subject_code'] = $i;
                        if ($name === 'title' || $name === 'ชื่อวิชา') $map['title'] = $i;
                        if ($name === 'group' || $name === 'group_title' || $name === 'กลุ่ม' || $name === 'กลุ่มtrack' || $name === 'กลุ่ม track') $map['group_title'] = $i;
                        if ($name === 'description' || $name === 'รายละเอียด') $map['description'] = $i;
                        if ($name === 'is_active' || $name === 'active' || $name === 'สถานะ') $map['is_active'] = $i;
                    }
                    if ($map['subject_code'] !== null) {
                        $col['subject_code'] = (int)$map['subject_code'];
                    }
                    if ($map['title'] !== null) {
                        $col['title'] = (int)$map['title'];
                    }
                    if ($map['group_title'] !== null) {
                        $col['group_title'] = (int)$map['group_title'];
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

            if (!$autoDetected) {
                $autoDetected = true;
                // If no header and row looks like the new format, switch mapping.
                if (count($row) >= 5) {
                    $col = ['subject_code' => 0, 'title' => 1, 'group_title' => 2, 'description' => 3, 'is_active' => 4];
                }
            }

            $idxCode = $col['subject_code'];
            $idxTitle = $col['title'];
            $idxGroup = $col['group_title'];
            $idxDesc = $col['description'];
            $idxActive = $col['is_active'];

            $code = ($idxCode !== null && isset($row[$idxCode])) ? (string)$row[$idxCode] : '';
            $title = (isset($row[$idxTitle])) ? (string)$row[$idxTitle] : '';
            $groupTitle = ($idxGroup !== null && isset($row[$idxGroup])) ? (string)$row[$idxGroup] : '';
            $description = (isset($row[$idxDesc])) ? (string)$row[$idxDesc] : '';
            $activeRaw = (isset($row[$idxActive])) ? (string)$row[$idxActive] : '1';

            if ($title === '' && $code === '') {
                $stats['skipped']++;
                continue;
            }

            if ($code === '') {
                // fallback: use title as code (best-effort)
                $code = $title;
            }

            $groupId = null;
            $groupTitle = trim($groupTitle);
            if ($groupTitle !== '') {
                $stmtGroupInsert->execute([$groupTitle]);
                $stmtGroupGetId->execute([$groupTitle]);
                $gid = (int)($stmtGroupGetId->fetchColumn() ?: 0);
                if ($gid > 0) {
                    $groupId = $gid;
                }
            }

            $isActive = 1;
            $activeLower = mb_strtolower($activeRaw);
            if ($activeLower === '0' || $activeLower === 'false' || $activeLower === 'no' || $activeLower === 'ปิด') {
                $isActive = 0;
            }
            if ($activeLower === '1' || $activeLower === 'true' || $activeLower === 'yes' || $activeLower === 'เปิด') {
                $isActive = 1;
            }

            $params = [
                ':g' => $groupId,
                ':c' => $code !== '' ? $code : null,
                ':t' => $title !== '' ? $title : $code,
                ':d' => ($description !== '' ? $description : null),
                ':a' => $isActive,
            ];

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
