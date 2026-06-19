<?php
declare(strict_types=1);

function auth_user(): ?array
{
    $user = $_SESSION['auth_user'] ?? null;
    if (!is_array($user)) {
        return null;
    }

    if (!isset($user['role']) && isset($user['id'])) {
        try {
            users_table_ensure();
            $pdo = db_app();
            $stmt = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => (int)$user['id']]);
            $role = (string)($stmt->fetchColumn() ?: 'teacher');
            $user['role'] = $role;
            $_SESSION['auth_user'] = $user;
        } catch (Throwable $e) {
            $user['role'] = 'teacher';
            $_SESSION['auth_user'] = $user;
        }
    }

    return $user;
}

function auth_login_throttle_table_ensure(): void
{
    $pdo = db_app();
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS auth_login_throttle (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL,
            ip VARCHAR(45) NOT NULL,
            fail_count INT UNSIGNED NOT NULL DEFAULT 0,
            first_fail_at TIMESTAMP NULL DEFAULT NULL,
            last_fail_at TIMESTAMP NULL DEFAULT NULL,
            locked_until TIMESTAMP NULL DEFAULT NULL,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_auth_login_throttle (username, ip),
            KEY idx_auth_locked_until (locked_until),
            KEY idx_auth_last_fail (last_fail_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );
}

/** Returns remaining locked seconds (0 = not locked). */
function auth_login_throttle_locked_seconds(string $username, string $ip): int
{
    auth_login_throttle_table_ensure();

    $username = trim($username);
    $ip = trim($ip);
    if ($username === '' || $ip === '') {
        return 0;
    }

    $pdo = db_app();
    $stmt = $pdo->prepare('SELECT locked_until FROM auth_login_throttle WHERE username = ? AND ip = ? LIMIT 1');
    $stmt->execute([$username, $ip]);
    $lockedUntil = $stmt->fetchColumn();
    if (!is_string($lockedUntil) || $lockedUntil === '') {
        return 0;
    }

    $ts = strtotime($lockedUntil);
    if ($ts === false) {
        return 0;
    }

    $remain = $ts - time();
    return $remain > 0 ? (int)$remain : 0;
}

function auth_login_throttle_clear(string $username, string $ip): void
{
    auth_login_throttle_table_ensure();
    $username = trim($username);
    $ip = trim($ip);
    if ($username === '' || $ip === '') {
        return;
    }

    $pdo = db_app();
    $stmt = $pdo->prepare('DELETE FROM auth_login_throttle WHERE username = ? AND ip = ?');
    $stmt->execute([$username, $ip]);
}

/**
 * Register a failed login attempt. Locks the (username, ip) temporarily after too many failures.
 * @return array{locked_seconds:int, remaining_attempts:int, max_attempts:int, fail_count:int}
 */
function auth_login_throttle_register_failure(string $username, string $ip): array
{
    auth_login_throttle_table_ensure();

    $username = trim($username);
    $ip = trim($ip);
    if ($username === '' || $ip === '') {
        return 0;
    }

    $maxFails = 5;
    $windowSeconds = 30 * 60;
    $lockSeconds = 15 * 60;

    // Best-effort cleanup (avoid table growth)
    try {
        $pdo = db_app();
        $pdo->exec("DELETE FROM auth_login_throttle WHERE last_fail_at IS NOT NULL AND last_fail_at < (NOW() - INTERVAL 7 DAY)");
    } catch (Throwable $e) {
        // ignore
    }

    $pdo = db_app();
    $stmt = $pdo->prepare('SELECT id, fail_count, first_fail_at, locked_until FROM auth_login_throttle WHERE username = ? AND ip = ? LIMIT 1');
    $stmt->execute([$username, $ip]);
    $row = $stmt->fetch();

    $now = time();

    if (!$row) {
        $stmtIns = $pdo->prepare('INSERT INTO auth_login_throttle (username, ip, fail_count, first_fail_at, last_fail_at) VALUES (?, ?, 1, NOW(), NOW())');
        $stmtIns->execute([$username, $ip]);
        return ['locked_seconds' => 0, 'remaining_attempts' => max(0, $maxFails - 1), 'max_attempts' => $maxFails, 'fail_count' => 1];
    }

    $id = (int)($row['id'] ?? 0);
    $failCount = (int)($row['fail_count'] ?? 0);
    $firstFailAt = (string)($row['first_fail_at'] ?? '');

    $firstTs = $firstFailAt !== '' ? strtotime($firstFailAt) : false;
    if ($firstTs === false || ($now - $firstTs) > $windowSeconds) {
        // Reset window
        $failCount = 0;
        $stmtReset = $pdo->prepare('UPDATE auth_login_throttle SET fail_count = 0, first_fail_at = NOW(), locked_until = NULL WHERE id = ?');
        $stmtReset->execute([$id]);
    }

    $failCount++;

    $lockedUntilSql = null;
    if ($failCount >= $maxFails) {
        $lockedUntilSql = date('Y-m-d H:i:s', $now + $lockSeconds);
    }

    $stmtUp = $pdo->prepare('UPDATE auth_login_throttle SET fail_count = ?, last_fail_at = NOW(), locked_until = ? WHERE id = ?');
    $stmtUp->execute([$failCount, $lockedUntilSql, $id]);

    if ($lockedUntilSql) {
        return ['locked_seconds' => $lockSeconds, 'remaining_attempts' => 0, 'max_attempts' => $maxFails, 'fail_count' => $failCount];
    }

    return ['locked_seconds' => 0, 'remaining_attempts' => max(0, $maxFails - $failCount), 'max_attempts' => $maxFails, 'fail_count' => $failCount];
}

function require_auth(): void
{
    if (!auth_user()) {
        redirect('login');
    }
}

function auth_logout(): void
{
    unset($_SESSION['auth_user']);
}

function users_table_ensure(): void
{
    $pdo = db_app();
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            displayname VARCHAR(100) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'teacher',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_users_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    // Best-effort migrations for older installs
    $migrations = [
        "ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'teacher'",
        // auth_source: 'local' = local password_hash, 'timetable' = verified via timetable-auth-api
        "ALTER TABLE users ADD COLUMN auth_source VARCHAR(20) NOT NULL DEFAULT 'local'",
    ];
    foreach ($migrations as $sql) {
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
            // ignore
        }
    }
}

function users_count(): int
{
    users_table_ensure();
    $pdo = db_app();
    return (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
}

function auth_attempt(string $username, string $password): bool
{
    users_table_ensure();

    $pdo = db_app();
    $stmt = $pdo->prepare('SELECT id, username, password_hash, displayname, role, auth_source FROM users WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    $row = $stmt->fetch();

    if (!$row) {
        // No local/synced account → try student self-service login against students_db.
        return student_auth_attempt($username, $password);
    }

    $authSource = (string)($row['auth_source'] ?? 'local');
    $displayname = (string)$row['displayname'];

    if ($authSource === 'timetable') {
        // Teacher accounts: verify the password live against the timetable-auth-api.
        $teacher = timetable_api_login((string)$row['username'], $password);
        if ($teacher === null) {
            return false;
        }
        // Keep displayname fresh from the source of truth (best-effort).
        $fresh = timetable_api_displayname($teacher);
        if ($fresh !== '' && $fresh !== $displayname) {
            try {
                $pdo->prepare('UPDATE users SET displayname = :d WHERE id = :id')
                    ->execute([':d' => $fresh, ':id' => (int)$row['id']]);
                $displayname = $fresh;
            } catch (Throwable $e) {
                // ignore, non-critical
            }
        }
    } else {
        // Local accounts (e.g. admin): verify against the stored hash.
        if (!is_string($row['password_hash'] ?? null) || (string)$row['password_hash'] === '') {
            return false;
        }
        if (!password_verify($password, (string)$row['password_hash'])) {
            return false;
        }
    }

    $_SESSION['auth_user'] = [
        'id' => (int)$row['id'],
        'username' => (string)$row['username'],
        'displayname' => $displayname,
        'role' => (string)($row['role'] ?? 'teacher'),
    ];

    return true;
}

/**
 * Student self-service login verified directly against students_db (no users row needed).
 * username = student_code (5-digit, zero-padded); password = "Skdw" + citizen_id.
 */
function student_auth_attempt(string $username, string $password): bool
{
    $code = student_code_normalize($username);
    if ($code === '' || $password === '') {
        return false;
    }

    try {
        $pdo = db_school();
        $stmt = $pdo->prepare(
            "SELECT student_code, first_name, last_name, citizen_id "
            . "FROM students WHERE student_code = :c AND citizen_id IS NOT NULL AND citizen_id <> '' "
            . "ORDER BY year_id DESC LIMIT 1"
        );
        $stmt->execute([':c' => $code]);
        $s = $stmt->fetch();
    } catch (Throwable $e) {
        return false;
    }

    if (!$s) {
        return false;
    }

    $citizen = trim((string)($s['citizen_id'] ?? ''));
    if ($citizen === '') {
        return false;
    }

    $expected = 'Skdw' . $citizen;
    if (!hash_equals($expected, $password)) {
        return false;
    }

    $name = trim(trim((string)($s['first_name'] ?? '')) . ' ' . trim((string)($s['last_name'] ?? '')));

    $_SESSION['auth_user'] = [
        'id' => 0, // students have no row in the users table
        'username' => $code,
        'displayname' => $name !== '' ? $name : $code,
        'role' => 'student',
    ];

    return true;
}

function auth_create_user(string $username, string $password, string $displayname, string $role = 'teacher'): void
{
    users_table_ensure();
    $pdo = db_app();

    $role = strtolower(trim($role));
    if (!in_array($role, ['admin', 'teacher', 'student'], true)) {
        $role = 'teacher';
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, displayname, role) VALUES (:u, :p, :d, :r)');
    $stmt->execute([
        ':u' => $username,
        ':p' => $hash,
        ':d' => $displayname,
        ':r' => $role,
    ]);
}
