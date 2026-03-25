<?php
declare(strict_types=1);

function pdo_connect(string $dbName): PDO
{
    static $pool = [];
    if (isset($pool[$dbName])) {
        return $pool[$dbName];
    }

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, $dbName);

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            throw $e;
        }
        http_response_code(500);
        echo 'Database connection failed.';
        exit;
    }

    $pool[$dbName] = $pdo;
    return $pdo;
}

function db_app(): PDO
{
    return pdo_connect(DB_APP);
}

function db_school(): PDO
{
    return pdo_connect(DB_SCHOOL);
}
