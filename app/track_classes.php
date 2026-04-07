<?php
declare(strict_types=1);

function track_class_tables_ensure(): void
{
    $pdo = db_app();

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS track_class_sessions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            year_id INT UNSIGNED NOT NULL,
            subject_id INT UNSIGNED NOT NULL,
            session_date DATE NOT NULL,
            note VARCHAR(255) NOT NULL DEFAULT '',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_track_class_year_date (year_id, session_date),
            KEY idx_track_class_subject_date (subject_id, session_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS track_class_students (
            session_id BIGINT UNSIGNED NOT NULL,
            student_code VARCHAR(20) NOT NULL,
            attend_status TINYINT NULL DEFAULT NULL,
            result_status VARCHAR(10) NOT NULL DEFAULT 'pending',
            checked_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (session_id, student_code),
            KEY idx_track_class_student (student_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    // Best-effort migrations for older installs
    $migrations = [
        "ALTER TABLE track_class_sessions ADD COLUMN note VARCHAR(255) NOT NULL DEFAULT ''",
        "ALTER TABLE track_class_sessions DROP INDEX uq_track_class_session",
        "ALTER TABLE track_class_students ADD COLUMN attend_status TINYINT NULL DEFAULT NULL",
        "ALTER TABLE track_class_students ADD COLUMN result_status VARCHAR(10) NOT NULL DEFAULT 'pending'",
        "ALTER TABLE track_class_students ADD COLUMN checked_at TIMESTAMP NULL DEFAULT NULL",
    ];
    foreach ($migrations as $sql) {
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
            // ignore
        }
    }
}

function thai_date_long(string $ymd): string
{
    $ymd = trim($ymd);
    if ($ymd === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
        return $ymd;
    }

    try {
        $dt = new DateTimeImmutable($ymd);
    } catch (Throwable $e) {
        return $ymd;
    }

    $weekdays = [
        'อาทิตย์',
        'จันทร์',
        'อังคาร',
        'พุธ',
        'พฤหัสบดี',
        'ศุกร์',
        'เสาร์',
    ];

    $months = [
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม',
    ];

    $w = (int)$dt->format('w');
    $d = (int)$dt->format('j');
    $m = (int)$dt->format('n');
    $y = (int)$dt->format('Y') + 543;

    $wd = $weekdays[$w] ?? '';
    $mn = $months[$m] ?? (string)$m;

    return 'วัน' . $wd . 'ที่ ' . $d . ' ' . $mn . ' ' . $y;
}

function track_class_session_create(int $yearId, int $subjectId, string $sessionDate, string $note, array $studentCodes): int
{
    track_class_tables_ensure();
    track_subjects_table_ensure();

    if ($yearId <= 0 || $subjectId <= 0) {
        throw new RuntimeException('ข้อมูลไม่ถูกต้อง');
    }

    $sessionDate = trim($sessionDate);
    if ($sessionDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sessionDate)) {
        throw new RuntimeException('กรุณาเลือกวันที่เรียน (รูปแบบ YYYY-MM-DD)');
    }

    $note = trim($note);

    $studentCodes = array_values(array_unique(array_filter(array_map('trim', array_map('strval', $studentCodes)), static fn($v) => $v !== '')));
    if (count($studentCodes) === 0) {
        throw new RuntimeException('กรุณาเลือกนักเรียนอย่างน้อย 1 คน');
    }

    $pdo = db_app();

    // Validate subject exists
    $stmt = $pdo->prepare('SELECT id FROM track_subjects WHERE id = ? LIMIT 1');
    $stmt->execute([$subjectId]);
    if (!$stmt->fetchColumn()) {
        throw new RuntimeException('ไม่พบวิชาที่เลือก');
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('INSERT INTO track_class_sessions (year_id, subject_id, session_date, note) VALUES (?, ?, ?, ?)');
        $stmt->execute([$yearId, $subjectId, $sessionDate, $note]);
        $sessionId = (int)$pdo->lastInsertId();

        $stmtIns = $pdo->prepare('INSERT IGNORE INTO track_class_students (session_id, student_code) VALUES (?, ?)');
        foreach ($studentCodes as $code) {
            $stmtIns->execute([$sessionId, $code]);
        }

        $pdo->commit();
        return $sessionId;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/** @return array<string,mixed>|null */
function track_class_session_get(int $sessionId): ?array
{
    track_class_tables_ensure();

    if ($sessionId <= 0) {
        return null;
    }

    $pdo = db_app();
    $stmt = $pdo->prepare(
        'SELECT s.id, s.year_id, s.subject_id, s.session_date, s.note, s.created_at, s.updated_at, subj.title AS subject_title, subj.description AS subject_description '
        . 'FROM track_class_sessions s '
        . 'JOIN track_subjects subj ON subj.id = s.subject_id '
        . 'WHERE s.id = ? LIMIT 1'
    );
    $stmt->execute([$sessionId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * List sessions (recent first).
 * @return array<int, array<string,mixed>>
 */
function track_class_sessions_list(int $yearId, int $limit = 50): array
{
    track_class_tables_ensure();

    $yearId = (int)$yearId;
    if ($yearId <= 0) {
        return [];
    }

    $pdo = db_app();
    $stmt = $pdo->prepare(
        'SELECT s.id, s.year_id, s.subject_id, s.session_date, s.note, s.created_at, subj.title AS subject_title '
        . 'FROM track_class_sessions s '
        . 'JOIN track_subjects subj ON subj.id = s.subject_id '
        . 'WHERE s.year_id = ? '
        . 'ORDER BY s.session_date DESC, s.id DESC '
        . 'LIMIT ' . (int)$limit
    );
    $stmt->execute([$yearId]);
    return $stmt->fetchAll();
}

/** Get latest session date for a year (YYYY-MM-DD) or null if none. */
function track_class_latest_session_date(int $yearId): ?string
{
    track_class_tables_ensure();

    $yearId = (int)$yearId;
    if ($yearId <= 0) {
        return null;
    }

    $pdo = db_app();
    $stmt = $pdo->prepare('SELECT session_date FROM track_class_sessions WHERE year_id = ? ORDER BY session_date DESC, id DESC LIMIT 1');
    $stmt->execute([$yearId]);
    $val = $stmt->fetchColumn();
    return is_string($val) && $val !== '' ? $val : null;
}

/**
 * List sessions for a given date (with student count).
 * @return array<int, array<string,mixed>>
 */
function track_class_sessions_for_date(int $yearId, string $sessionDate): array
{
    track_class_tables_ensure();

    $yearId = (int)$yearId;
    $sessionDate = trim($sessionDate);
    if ($yearId <= 0 || $sessionDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sessionDate)) {
        return [];
    }

    $pdo = db_app();
    $sql = 'SELECT s.id, s.year_id, s.subject_id, s.session_date, s.note, s.created_at, subj.title AS subject_title, '
        . 'COALESCE(cnt.c, 0) AS student_count '
        . 'FROM track_class_sessions s '
        . 'JOIN track_subjects subj ON subj.id = s.subject_id '
        . 'LEFT JOIN (SELECT session_id, COUNT(*) AS c FROM track_class_students GROUP BY session_id) cnt ON cnt.session_id = s.id '
        . 'WHERE s.year_id = ? AND s.session_date = ? '
        . 'ORDER BY subj.title, s.id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$yearId, $sessionDate]);
    return $stmt->fetchAll();
}

/**
 * List distinct session dates in a given month (YYYY-MM) for a year.
 * @return array<int, string> Dates in Y-m-d
 */
function track_class_session_dates_in_month(int $yearId, string $ym): array
{
    track_class_tables_ensure();

    $ym = trim($ym);
    if ($yearId <= 0 || !preg_match('/^\d{4}-\d{2}$/', $ym)) {
        return [];
    }

    try {
        $start = new DateTimeImmutable($ym . '-01');
        $end = $start->modify('last day of this month');
    } catch (Throwable $e) {
        return [];
    }

    $pdo = db_app();
    $stmt = $pdo->prepare('SELECT DISTINCT session_date FROM track_class_sessions WHERE year_id = ? AND session_date BETWEEN ? AND ? ORDER BY session_date');
    $stmt->execute([$yearId, $start->format('Y-m-d'), $end->format('Y-m-d')]);

    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $d = (string)($row['session_date'] ?? '');
        if ($d !== '') {
            $out[] = $d;
        }
    }
    return $out;
}

/**
 * Get roster with current attendance/result.
 * @return array<int, array<string,mixed>>
 */
function track_class_roster(int $sessionId): array
{
    track_class_tables_ensure();

    if ($sessionId <= 0) {
        return [];
    }

    $pdo = db_app();

    $sql = 'SELECT cs.session_id, cs.student_code, cs.attend_status, cs.result_status, cs.checked_at, '
        . 'st.first_name, st.last_name, st.class_level, st.class_room, st.number_in_room '
        . 'FROM track_class_students cs '
        . 'JOIN track_class_sessions sess ON sess.id = cs.session_id '
        . 'LEFT JOIN ' . DB_SCHOOL . '.students st ON st.year_id = sess.year_id AND st.student_code = cs.student_code '
        . 'WHERE cs.session_id = ? '
        . 'ORDER BY st.class_level, st.class_room, st.number_in_room, cs.student_code';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sessionId]);
    return $stmt->fetchAll();
}

/**
 * Save attendance/result for a session.
 * @param array<string, mixed> $attendMap student_code => '1'|'0'|''
 * @param array<string, mixed> $resultMap student_code => 'pending'|'pass'|'fail'
 * @return array{updated:int, passed_added:int}
 */
function track_class_save_check(int $sessionId, array $attendMap, array $resultMap): array
{
    track_class_tables_ensure();
    track_registrations_table_ensure();

    $session = track_class_session_get($sessionId);
    if (!$session) {
        throw new RuntimeException('ไม่พบรอบเรียน');
    }

    $pdo = db_app();

    $updated = 0;
    $passedCodes = [];

    $stmtUp = $pdo->prepare('UPDATE track_class_students SET attend_status = ?, result_status = ?, checked_at = NOW() WHERE session_id = ? AND student_code = ?');

    foreach ($resultMap as $code => $result) {
        $code = trim((string)$code);
        if ($code === '') {
            continue;
        }

        $attRaw = $attendMap[$code] ?? '';
        $att = null;
        if ($attRaw === '1' || $attRaw === 1 || $attRaw === true) {
            $att = 1;
        }
        if ($attRaw === '0' || $attRaw === 0 || $attRaw === false) {
            $att = 0;
        }

        $res = (string)$result;
        if (!in_array($res, ['pending', 'pass', 'fail'], true)) {
            $res = 'pending';
        }

        $stmtUp->execute([$att, $res, $sessionId, $code]);
        $updated += $stmtUp->rowCount();

        if ($res === 'pass') {
            $passedCodes[] = $code;
        }
    }

    $passedAdded = 0;
    if (count($passedCodes) > 0) {
        // Add to student record (registration) when marked as pass.
        $passedAdded = track_reg_assign_bulk((int)$session['year_id'], (int)$session['subject_id'], $passedCodes);
    }

    return ['updated' => $updated, 'passed_added' => $passedAdded];
}

function track_class_session_update(int $sessionId, int $subjectId, string $sessionDate, string $note, array $studentCodes): void
{
    track_class_tables_ensure();
    track_subjects_table_ensure();

    $session = track_class_session_get($sessionId);
    if (!$session) {
        throw new RuntimeException('ไม่พบรอบเรียน');
    }

    if ($subjectId <= 0) {
        throw new RuntimeException('กรุณาเลือกวิชา');
    }

    $sessionDate = trim($sessionDate);
    if ($sessionDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sessionDate)) {
        throw new RuntimeException('กรุณาเลือกวันที่เรียน (รูปแบบ YYYY-MM-DD)');
    }

    $note = trim($note);

    $studentCodes = array_values(array_unique(array_filter(array_map('trim', array_map('strval', $studentCodes)), static fn($v) => $v !== '')));
    if (count($studentCodes) === 0) {
        throw new RuntimeException('กรุณาเลือกนักเรียนอย่างน้อย 1 คน');
    }

    $pdo = db_app();

    // Validate subject exists
    $stmt = $pdo->prepare('SELECT id FROM track_subjects WHERE id = ? LIMIT 1');
    $stmt->execute([$subjectId]);
    if (!$stmt->fetchColumn()) {
        throw new RuntimeException('ไม่พบวิชาที่เลือก');
    }

    try {
        $pdo->beginTransaction();

        $stmtUp = $pdo->prepare('UPDATE track_class_sessions SET subject_id = ?, session_date = ?, note = ?, updated_at = NOW() WHERE id = ?');
        $stmtUp->execute([$subjectId, $sessionDate, $note, $sessionId]);

        // Diff roster: keep existing rows (and their statuses) for retained students.
        $stmtSel = $pdo->prepare('SELECT student_code FROM track_class_students WHERE session_id = ?');
        $stmtSel->execute([$sessionId]);
        $existing = array_map('strval', array_column($stmtSel->fetchAll(), 'student_code'));
        $existingSet = array_fill_keys($existing, true);
        $newSet = array_fill_keys($studentCodes, true);

        $toDelete = [];
        foreach ($existingSet as $code => $_) {
            if (!isset($newSet[$code])) {
                $toDelete[] = $code;
            }
        }

        $toAdd = [];
        foreach ($newSet as $code => $_) {
            if (!isset($existingSet[$code])) {
                $toAdd[] = $code;
            }
        }

        if (count($toDelete) > 0) {
            $in = implode(',', array_fill(0, count($toDelete), '?'));
            $stmtDel = $pdo->prepare('DELETE FROM track_class_students WHERE session_id = ? AND student_code IN (' . $in . ')');
            $stmtDel->execute(array_merge([$sessionId], $toDelete));
        }

        if (count($toAdd) > 0) {
            $stmtIns = $pdo->prepare('INSERT IGNORE INTO track_class_students (session_id, student_code) VALUES (?, ?)');
            foreach ($toAdd as $code) {
                $stmtIns->execute([$sessionId, $code]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function track_class_session_delete(int $sessionId): void
{
    track_class_tables_ensure();

    if ($sessionId <= 0) {
        throw new RuntimeException('ข้อมูลไม่ถูกต้อง');
    }

    $pdo = db_app();

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('DELETE FROM track_class_students WHERE session_id = ?');
        $stmt->execute([$sessionId]);
        $stmt2 = $pdo->prepare('DELETE FROM track_class_sessions WHERE id = ?');
        $stmt2->execute([$sessionId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
