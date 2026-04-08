<?php
declare(strict_types=1);

function track_settings_table_ensure(): void
{
    $pdo = db_app();
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS track_settings ('
        . 'id TINYINT UNSIGNED NOT NULL PRIMARY KEY, '
        . 'active_term TINYINT UNSIGNED NOT NULL DEFAULT 1, '
        . 'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec('INSERT IGNORE INTO track_settings (id, active_term) VALUES (1, 1)');
}

/** @return array{active_term:int, updated_at:string} */
function track_settings_get(): array
{
    track_settings_table_ensure();
    $pdo = db_app();
    $row = $pdo->query('SELECT active_term, updated_at FROM track_settings WHERE id = 1')->fetch();

    return [
        'active_term' => is_array($row) && isset($row['active_term']) ? (int)$row['active_term'] : 1,
        'updated_at' => is_array($row) && isset($row['updated_at']) ? (string)$row['updated_at'] : '',
    ];
}

function track_active_term(): int
{
    $t = (int)(track_settings_get()['active_term'] ?? 1);
    return $t === 2 ? 2 : 1;
}

function track_settings_set_active_term(int $term): void
{
    track_settings_table_ensure();
    $term = $term === 2 ? 2 : 1;

    $pdo = db_app();
    $stmt = $pdo->prepare('UPDATE track_settings SET active_term = ? WHERE id = 1');
    $stmt->execute([$term]);
}
