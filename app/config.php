<?php
declare(strict_types=1);

// ---- Load .env file ----
(static function (): void {
    $envFile = dirname(__DIR__) . '/.env';
    if (!is_file($envFile)) {
        return;
    }
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $eqPos = strpos($line, '=');
        if ($eqPos === false) {
            continue;
        }
        $key   = trim(substr($line, 0, $eqPos));
        $value = trim(substr($line, $eqPos + 1));
        // Strip surrounding quotes
        if (strlen($value) >= 2 && (
            ($value[0] === '"'  && $value[-1] === '"') ||
            ($value[0] === "'"  && $value[-1] === "'")
        )) {
            $value = substr($value, 1, -1);
        }
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
})();

// ---- Helper: read env (required, no fallback) ----
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === '') {
        if ($default !== null) {
            return $default;
        }
        throw new \RuntimeException("Missing required environment variable: $key");
    }
    return match (strtolower((string) $value)) {
        'true'  => true,
        'false' => false,
        'null'  => null,
        default => $value,
    };
}

// ---- App ----
define('APP_NAME',     env('APP_NAME'));
define('APP_TIMEZONE', env('APP_TIMEZONE'));
define('APP_DEBUG',    env('APP_DEBUG'));

// ---- Database ----
define('DB_HOST',   env('DB_HOST'));
define('DB_PORT',   (int) env('DB_PORT'));
define('DB_USER',   env('DB_USER'));
define('DB_PASS',   env('DB_PASS', ''));   // allow empty string for passwordless setups

// App DB for this system (users/auth etc.)
define('DB_APP',    env('DB_APP'));

// School data DB (provided sample schema)
define('DB_SCHOOL', env('DB_SCHOOL'));

// ---- Timetable Auth API (teacher accounts source) ----
// Base URL of the timetable-auth-api service, e.g. http://localhost:4001
define('TIMETABLE_API_BASE', rtrim((string) env('TIMETABLE_API_BASE', ''), '/'));
// API keys (tta_...). Each key holds one scope:
//   TIMETABLE_API_KEY       -> scope teachers:read  (used for sync)
//   TIMETABLE_API_KEY_LOGIN -> scope auth:login     (used for teacher login; falls back to main key)
define('TIMETABLE_API_KEY',       (string) env('TIMETABLE_API_KEY', ''));
define('TIMETABLE_API_KEY_LOGIN', (string) env('TIMETABLE_API_KEY_LOGIN', ''));
