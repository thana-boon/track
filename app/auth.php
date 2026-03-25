<?php
declare(strict_types=1);

function auth_user(): ?array
{
    $user = $_SESSION['auth_user'] ?? null;
    return is_array($user) ? $user : null;
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
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_users_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );
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
    $stmt = $pdo->prepare('SELECT id, username, password_hash, displayname FROM users WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    $row = $stmt->fetch();

    if (!$row || !is_string($row['password_hash'] ?? null)) {
        return false;
    }

    if (!password_verify($password, (string)$row['password_hash'])) {
        return false;
    }

    $_SESSION['auth_user'] = [
        'id' => (int)$row['id'],
        'username' => (string)$row['username'],
        'displayname' => (string)$row['displayname'],
    ];

    return true;
}

function auth_create_user(string $username, string $password, string $displayname): void
{
    users_table_ensure();
    $pdo = db_app();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, displayname) VALUES (:u, :p, :d)');
    $stmt->execute([
        ':u' => $username,
        ':p' => $hash,
        ':d' => $displayname,
    ]);
}
