<?php
declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $route = ''): never
{
    $url = '/tracks/';
    if ($route !== '') {
        $url .= rawurlencode($route);
    }
    header('Location: ' . $url);
    exit;
}

function csrf_token(): string
{
    if (!isset($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['_csrf'];
}

function csrf_verify(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals((string)($_SESSION['_csrf'] ?? ''), $token)) {
        // Best-effort security log (do not block request if logging fails)
        try {
            activity_logs_table_ensure();
            activity_log_write('csrf_invalid', [
                'path' => (string)($_SERVER['REQUEST_URI'] ?? ''),
                'post_action' => is_string($_POST['action'] ?? null) ? (string)$_POST['action'] : '',
            ]);
        } catch (Throwable $e) {
            // ignore
        }
        http_response_code(419);
        echo render('errors/419', ['title' => 'CSRF ไม่ถูกต้อง']);
        exit;
    }
}

function flash_set(string $key, string $message): void
{
    $_SESSION['_flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return is_string($value) ? $value : null;
}

function input_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function query_string(string $key): string
{
    $value = $_GET[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function client_ip(): string
{
    $ip = '';

    // In case of proxies, try X-Forwarded-For (first public-ish entry)
    $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if (is_string($xff) && $xff !== '') {
        $parts = array_map('trim', explode(',', $xff));
        if (isset($parts[0]) && $parts[0] !== '') {
            $ip = $parts[0];
        }
    }

    if ($ip === '') {
        $ra = $_SERVER['REMOTE_ADDR'] ?? '';
        if (is_string($ra)) {
            $ip = $ra;
        }
    }

    $ip = trim($ip);
    if ($ip === '' || strlen($ip) > 45) {
        return '';
    }
    return $ip;
}

function activity_logs_table_ensure(): void
{
    $pdo = db_app();
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS activity_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            user_id INT UNSIGNED NULL,
            username VARCHAR(50) NOT NULL DEFAULT '',
            role VARCHAR(20) NOT NULL DEFAULT '',
            ip VARCHAR(45) NOT NULL DEFAULT '',
            method VARCHAR(10) NOT NULL DEFAULT '',
            route VARCHAR(80) NOT NULL DEFAULT '',
            event VARCHAR(80) NOT NULL DEFAULT '',
            message VARCHAR(255) NOT NULL DEFAULT '',
            context_json LONGTEXT NULL,
            user_agent VARCHAR(255) NOT NULL DEFAULT '',
            PRIMARY KEY (id),
            KEY idx_activity_created (created_at),
            KEY idx_activity_user (user_id),
            KEY idx_activity_route (route),
            KEY idx_activity_event (event)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );
}

/**
 * Best-effort activity logger (never throws to callers).
 * @param array<string,mixed> $context
 */
function activity_log_write(string $event, array $context = [], string $message = ''): void
{
    try {
        activity_logs_table_ensure();

        $me = auth_user();
        $userId = null;
        $username = '';
        $role = '';
        if (is_array($me)) {
            $userId = isset($me['id']) ? (int)$me['id'] : null;
            $username = (string)($me['username'] ?? '');
            $role = (string)($me['role'] ?? '');
        }

        $ip = client_ip();
        $method = is_string($_SERVER['REQUEST_METHOD'] ?? null) ? (string)$_SERVER['REQUEST_METHOD'] : '';
        $route = is_string($_GET['route'] ?? null) ? trim((string)$_GET['route'], "/") : '';
        $ua = is_string($_SERVER['HTTP_USER_AGENT'] ?? null) ? (string)$_SERVER['HTTP_USER_AGENT'] : '';
        if (strlen($ua) > 255) {
            $ua = substr($ua, 0, 255);
        }

        $ctx = null;
        if (!empty($context)) {
            $ctx = json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        $pdo = db_app();
        $stmt = $pdo->prepare(
            'INSERT INTO activity_logs (user_id, username, role, ip, method, route, event, message, context_json, user_agent) '
            . 'VALUES (:uid, :un, :role, :ip, :m, :r, :e, :msg, :ctx, :ua)'
        );
        $stmt->execute([
            ':uid' => $userId && $userId > 0 ? $userId : null,
            ':un' => $username,
            ':role' => $role,
            ':ip' => $ip,
            ':m' => $method,
            ':r' => $route,
            ':e' => trim($event),
            ':msg' => trim($message),
            ':ctx' => $ctx,
            ':ua' => $ua,
        ]);
    } catch (Throwable $e) {
        // ignore
    }
}
