<?php
declare(strict_types=1);

require_auth();

$me = auth_user();
$role = is_array($me) ? (string)($me['role'] ?? 'teacher') : '';
if ($role !== 'admin') {
    redirect('dashboard');
}

$csrf = csrf_token();

$error = null;
$success = flash_get('success');

// Only allow backup/restore for this app database.
$dbName = DB_APP;

$backupDir = __DIR__ . '/../../uploads/backups';

/** @return array<int,array{file:string,path:string,size:int,mtime:int}> */
$listBackups = static function () use ($backupDir): array {
    if (!is_dir($backupDir)) {
        return [];
    }

    $files = glob($backupDir . '/*.sql') ?: [];
    $out = [];
    foreach ($files as $path) {
        $base = basename((string)$path);
        if ($base === '' || !is_file($path)) {
            continue;
        }
        $out[] = [
            'file' => $base,
            'path' => (string)$path,
            'size' => (int)@filesize($path),
            'mtime' => (int)@filemtime($path),
        ];
    }

    usort($out, static fn($a, $b) => ($b['mtime'] <=> $a['mtime']) ?: strcmp($b['file'], $a['file']));
    return $out;
};

$ensureBackupDir = static function () use ($backupDir): void {
    if (!is_dir($backupDir)) {
        if (!@mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
            throw new RuntimeException('สร้างโฟลเดอร์ backups ไม่สำเร็จ: ' . $backupDir);
        }
    }
};

$safeBackupPath = static function (string $file) use ($backupDir): string {
    $file = basename(trim($file));
    if ($file === '' || !preg_match('/\A[0-9A-Za-z_\-\.]+\.sql\z/', $file)) {
        throw new RuntimeException('ชื่อไฟล์ไม่ถูกต้อง');
    }
    $path = $backupDir . '/' . $file;
    if (!is_file($path)) {
        throw new RuntimeException('ไม่พบไฟล์ backup');
    }
    return $path;
};

// Download saved backup (GET) with CSRF token in query
if ($_SERVER['REQUEST_METHOD'] === 'GET' && query_string('download') !== '') {
    $token = query_string('_csrf');
    if ($token === '' || !hash_equals((string)($_SESSION['_csrf'] ?? ''), $token)) {
        http_response_code(419);
        echo render('errors/419', ['title' => 'CSRF ไม่ถูกต้อง']);
        exit;
    }

    try {
        $file = query_string('download');
        $path = $safeBackupPath($file);

        activity_log_write('backup_download', [
            'db' => $dbName,
            'file' => $file,
            'bytes' => (int)@filesize($path),
        ]);

        @set_time_limit(0);
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'เกิดข้อผิดพลาด';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = input_string('action');

    try {
        if ($action === 'create_backup') {
            @set_time_limit(0);
            $ensureBackupDir();

            $pdo = db_app();
            $sql = backup_dump_database($pdo, $dbName);

            $filename = 'backup_' . $dbName . '_' . date('Ymd_His') . '.sql';
            $path = $backupDir . '/' . $filename;

            $bytes = @file_put_contents($path, $sql, LOCK_EX);
            if (!is_int($bytes) || $bytes <= 0) {
                throw new RuntimeException('บันทึกไฟล์ backup ไม่สำเร็จ');
            }

            activity_log_write('backup_created', [
                'db' => $dbName,
                'file' => $filename,
                'bytes' => $bytes,
            ]);

            flash_set('success', 'สร้างไฟล์ backup แล้ว: ' . $filename);
            header('Location: /tracks/backup_restore');
            exit;
        }

        if ($action === 'restore_saved') {
            @set_time_limit(0);
            $pdo = db_app();
            $file = input_string('file');
            $path = $safeBackupPath($file);

            $sql = (string)file_get_contents($path);
            if (trim($sql) === '') {
                throw new RuntimeException('ไฟล์ว่างหรืออ่านไม่สำเร็จ');
            }

            $executed = backup_restore_apply_sql($pdo, $sql, $dbName);

            activity_log_write('backup_restore', [
                'db' => $dbName,
                'source' => 'saved',
                'file' => $file,
                'executed' => $executed,
                'bytes' => strlen($sql),
            ]);

            flash_set('success', 'กู้คืนข้อมูลจากไฟล์ที่บันทึกไว้สำเร็จ (' . $executed . ' statements)');
            header('Location: /tracks/backup_restore');
            exit;
        }

        if ($action === 'restore_upload') {
            @set_time_limit(0);
            $pdo = db_app();

            $file = $_FILES['sql_file'] ?? null;
            if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('กรุณาเลือกไฟล์ .sql');
            }

            $tmp = (string)($file['tmp_name'] ?? '');
            if ($tmp === '' || !is_file($tmp)) {
                throw new RuntimeException('อ่านไฟล์ .sql ไม่ได้');
            }

            $sql = (string)file_get_contents($tmp);
            if (trim($sql) === '') {
                throw new RuntimeException('ไฟล์ว่างหรืออ่านไม่สำเร็จ');
            }

            $executed = backup_restore_apply_sql($pdo, $sql, $dbName);

            activity_log_write('backup_restore', [
                'db' => $dbName,
                'source' => 'upload',
                'original_name' => is_string($file['name'] ?? null) ? (string)$file['name'] : '',
                'executed' => $executed,
                'bytes' => strlen($sql),
            ]);

            flash_set('success', 'กู้คืนข้อมูลจากไฟล์ที่อัปโหลดสำเร็จ (' . $executed . ' statements)');
            header('Location: /tracks/backup_restore');
            exit;
        }

        throw new RuntimeException('คำสั่งไม่ถูกต้อง');
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'เกิดข้อผิดพลาด';
    }
}

$backups = $listBackups();

echo render('backup_restore/index', [
    'title' => 'Backup / Restore',
    'csrf' => $csrf,
    'error' => $error,
    'success' => $success,
    'backups' => $backups,
]);
