<?php
declare(strict_types=1);

/**
 * Client for the timetable-auth-api service (source of truth for teacher accounts).
 * - Teacher passwords are NOT stored in tracks; login is verified live via this API.
 * - Teacher master data (code + name) is pulled in via "sync".
 */

function timetable_api_configured(): bool
{
    return TIMETABLE_API_BASE !== '' && TIMETABLE_API_KEY !== '';
}

/** Key used for login requests (scope auth:login); falls back to the main key. */
function timetable_api_login_key(): string
{
    return TIMETABLE_API_KEY_LOGIN !== '' ? TIMETABLE_API_KEY_LOGIN : TIMETABLE_API_KEY;
}

/**
 * Build the displayname from API teacher fields, e.g. "นายธนา บุญชู".
 * @param array<string,mixed> $t
 */
function timetable_api_displayname(array $t): string
{
    $title = trim((string)($t['title'] ?? ''));
    $first = trim((string)($t['first_name'] ?? ''));
    $last  = trim((string)($t['last_name'] ?? ''));

    $name = trim($title . $first);
    if ($last !== '') {
        $name = trim($name . ' ' . $last);
    }
    if ($name === '') {
        $name = trim((string)($t['teacher_code'] ?? ''));
    }
    return $name;
}

/**
 * Low-level request helper.
 * @param array<string,mixed>|null $jsonBody
 * @return array{status:int, body:mixed, error:string}
 */
function timetable_api_request(string $method, string $path, ?array $jsonBody = null, string $apiKey = ''): array
{
    if ($apiKey === '') {
        $apiKey = TIMETABLE_API_KEY;
    }
    if (TIMETABLE_API_BASE === '' || $apiKey === '') {
        return ['status' => 0, 'body' => null, 'error' => 'ยังไม่ได้ตั้งค่า TIMETABLE_API_BASE / API key ใน .env'];
    }

    $url = TIMETABLE_API_BASE . $path;
    $headers = ['x-api-key: ' . $apiKey];

    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 15,
    ];

    if (strtoupper($method) === 'POST') {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = json_encode($jsonBody ?? [], JSON_UNESCAPED_UNICODE);
        $headers[] = 'Content-Type: application/json';
    }

    $opts[CURLOPT_HTTPHEADER] = $headers;
    curl_setopt_array($ch, $opts);

    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['status' => 0, 'body' => null, 'error' => 'เชื่อมต่อ API ไม่ได้: ' . $err];
    }

    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $body = json_decode((string)$raw, true);
    return ['status' => $status, 'body' => $body, 'error' => ''];
}

/**
 * Verify a teacher login against the API.
 * @return array<string,mixed>|null Teacher data on success, null on failure.
 */
function timetable_api_login(string $teacherCode, string $password): ?array
{
    $teacherCode = trim($teacherCode);
    if ($teacherCode === '' || $password === '') {
        return null;
    }

    $res = timetable_api_request('POST', '/api/auth/login', [
        'teacher_code' => $teacherCode,
        'password'     => $password,
    ], timetable_api_login_key());

    if ($res['status'] === 200 && is_array($res['body'])) {
        return $res['body'];
    }
    return null;
}

/**
 * Fetch all teachers.
 * @return array{ok:bool, teachers:array<int,array<string,mixed>>, error:string}
 */
function timetable_api_teachers(): array
{
    $res = timetable_api_request('GET', '/api/teachers');

    if ($res['status'] === 200 && is_array($res['body'])) {
        return ['ok' => true, 'teachers' => $res['body'], 'error' => ''];
    }

    $msg = 'ดึงรายชื่อครูไม่สำเร็จ';
    if ($res['error'] !== '') {
        $msg .= ' (' . $res['error'] . ')';
    } elseif (is_array($res['body']) && isset($res['body']['error'])) {
        $msg .= ' (HTTP ' . $res['status'] . ': ' . (string)$res['body']['error'] . ')';
    } else {
        $msg .= ' (HTTP ' . $res['status'] . ')';
    }
    return ['ok' => false, 'teachers' => [], 'error' => $msg];
}

/**
 * Mirror teacher accounts from the API into the local users table.
 * - Insert new (role=teacher, auth_source=timetable, no local password)
 * - Update displayname of existing timetable users (role/password untouched)
 * - Delete timetable users that no longer exist in the API
 * Local accounts (auth_source=local, e.g. admin) are never touched.
 *
 * @return array{created:int, updated:int, deleted:int}
 */
function timetable_api_sync_users(array $teachers): array
{
    users_table_ensure();
    $pdo = db_app();

    $apiCodes = [];
    $created = 0;
    $updated = 0;

    $selStmt = $pdo->prepare('SELECT id, displayname, auth_source FROM users WHERE username = ? LIMIT 1');
    $insStmt = $pdo->prepare(
        "INSERT INTO users (username, password_hash, displayname, role, auth_source) "
        . "VALUES (:u, '', :d, 'teacher', 'timetable')"
    );
    $updStmt = $pdo->prepare("UPDATE users SET displayname = :d, auth_source = 'timetable' WHERE id = :id");

    foreach ($teachers as $t) {
        if (!is_array($t)) {
            continue;
        }
        $code = trim((string)($t['teacher_code'] ?? ''));
        if ($code === '') {
            continue;
        }
        // Skip resigned teachers (code starts with 'A') — they have no password and cannot log in.
        if (strtoupper($code[0]) === 'A') {
            continue;
        }
        $apiCodes[$code] = true;
        $display = timetable_api_displayname($t);

        $selStmt->execute([$code]);
        $existing = $selStmt->fetch();

        if (!$existing) {
            $insStmt->execute([':u' => $code, ':d' => $display]);
            $created++;
        } else {
            // Only update displayname; preserve manually-set role. Keep local accounts as local.
            $updStmt->execute([':d' => $display, ':id' => (int)$existing['id']]);
            $updated++;
        }
    }

    // Delete timetable users no longer present in the API (never touch local accounts).
    $deleted = 0;
    $timetableUsers = $pdo->query("SELECT id, username FROM users WHERE auth_source = 'timetable'")->fetchAll();
    $me = function_exists('auth_user') ? auth_user() : null;
    $myId = is_array($me) ? (int)($me['id'] ?? 0) : 0;

    $delStmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    foreach ($timetableUsers as $row) {
        $uname = (string)$row['username'];
        if (isset($apiCodes[$uname])) {
            continue;
        }
        if ((int)$row['id'] === $myId) {
            continue; // never delete the current session user
        }
        $delStmt->execute([(int)$row['id']]);
        $deleted++;
    }

    return ['created' => $created, 'updated' => $updated, 'deleted' => $deleted];
}
