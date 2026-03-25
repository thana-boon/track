<?php
declare(strict_types=1);

function report_settings_table_ensure(): void
{
    $pdo = db_app();
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS report_settings (
            id TINYINT UNSIGNED NOT NULL,
            school_name VARCHAR(255) NOT NULL DEFAULT '',
            logo_path VARCHAR(255) NOT NULL DEFAULT '',
            issued_date VARCHAR(10) NOT NULL DEFAULT '',
            advisor_name VARCHAR(255) NOT NULL DEFAULT '',
            registrar_name VARCHAR(255) NOT NULL DEFAULT '',
            director_name VARCHAR(255) NOT NULL DEFAULT '',
            advisor_sign_path VARCHAR(255) NOT NULL DEFAULT '',
            registrar_sign_path VARCHAR(255) NOT NULL DEFAULT '',
            director_sign_path VARCHAR(255) NOT NULL DEFAULT '',
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    // Best-effort migration for older installs
    $migrations = [
        "ALTER TABLE report_settings ADD COLUMN issued_date VARCHAR(10) NOT NULL DEFAULT ''",
        "ALTER TABLE report_settings ADD COLUMN advisor_name VARCHAR(255) NOT NULL DEFAULT ''",
        "ALTER TABLE report_settings ADD COLUMN registrar_name VARCHAR(255) NOT NULL DEFAULT ''",
        "ALTER TABLE report_settings ADD COLUMN director_name VARCHAR(255) NOT NULL DEFAULT ''",
        "ALTER TABLE report_settings ADD COLUMN advisor_sign_path VARCHAR(255) NOT NULL DEFAULT ''",
        "ALTER TABLE report_settings ADD COLUMN registrar_sign_path VARCHAR(255) NOT NULL DEFAULT ''",
        "ALTER TABLE report_settings ADD COLUMN director_sign_path VARCHAR(255) NOT NULL DEFAULT ''",
    ];
    foreach ($migrations as $sql) {
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
            // ignore if already exists
        }
    }

    // Ensure a single row exists
    $pdo->exec("INSERT IGNORE INTO report_settings (id, school_name, logo_path) VALUES (1, '', '')");
}

/** @return array{school_name:string, logo_path:string, issued_date:string, advisor_name:string, registrar_name:string, director_name:string, advisor_sign_path:string, registrar_sign_path:string, director_sign_path:string} */
function report_settings_get(): array
{
    report_settings_table_ensure();
    $pdo = db_app();
    $row = $pdo->query('SELECT school_name, logo_path, issued_date, advisor_name, registrar_name, director_name, advisor_sign_path, registrar_sign_path, director_sign_path FROM report_settings WHERE id = 1')->fetch();
    return [
        'school_name' => is_array($row) && isset($row['school_name']) ? (string)$row['school_name'] : '',
        'logo_path' => is_array($row) && isset($row['logo_path']) ? (string)$row['logo_path'] : '',
        'issued_date' => is_array($row) && isset($row['issued_date']) ? (string)$row['issued_date'] : '',
        'advisor_name' => is_array($row) && isset($row['advisor_name']) ? (string)$row['advisor_name'] : '',
        'registrar_name' => is_array($row) && isset($row['registrar_name']) ? (string)$row['registrar_name'] : '',
        'director_name' => is_array($row) && isset($row['director_name']) ? (string)$row['director_name'] : '',
        'advisor_sign_path' => is_array($row) && isset($row['advisor_sign_path']) ? (string)$row['advisor_sign_path'] : '',
        'registrar_sign_path' => is_array($row) && isset($row['registrar_sign_path']) ? (string)$row['registrar_sign_path'] : '',
        'director_sign_path' => is_array($row) && isset($row['director_sign_path']) ? (string)$row['director_sign_path'] : '',
    ];
}

/** @param array{school_name:string, logo_path?:string, issued_date?:string, advisor_name?:string, registrar_name?:string, director_name?:string, advisor_sign_path?:string, registrar_sign_path?:string, director_sign_path?:string} $data */
function report_settings_save(array $data): void
{
    report_settings_table_ensure();

    $schoolName = trim((string)($data['school_name'] ?? ''));
    $logoPath = isset($data['logo_path']) ? trim((string)$data['logo_path']) : null;
    $issuedDate = trim((string)($data['issued_date'] ?? ''));
    $advisorName = trim((string)($data['advisor_name'] ?? ''));
    $registrarName = trim((string)($data['registrar_name'] ?? ''));
    $directorName = trim((string)($data['director_name'] ?? ''));
    $advisorSignPath = isset($data['advisor_sign_path']) ? trim((string)$data['advisor_sign_path']) : null;
    $registrarSignPath = isset($data['registrar_sign_path']) ? trim((string)$data['registrar_sign_path']) : null;
    $directorSignPath = isset($data['director_sign_path']) ? trim((string)$data['director_sign_path']) : null;

    $pdo = db_app();

    $current = report_settings_get();
    $logoFinal = $logoPath !== null ? $logoPath : (string)$current['logo_path'];
    $advisorSignFinal = $advisorSignPath !== null ? $advisorSignPath : (string)$current['advisor_sign_path'];
    $registrarSignFinal = $registrarSignPath !== null ? $registrarSignPath : (string)$current['registrar_sign_path'];
    $directorSignFinal = $directorSignPath !== null ? $directorSignPath : (string)$current['director_sign_path'];

    $stmt = $pdo->prepare('UPDATE report_settings SET school_name = :sn, logo_path = :lp, issued_date = :id, advisor_name = :an, registrar_name = :rn, director_name = :dn, advisor_sign_path = :asp, registrar_sign_path = :rsp, director_sign_path = :dsp WHERE id = 1');
    $stmt->execute([
        ':sn' => $schoolName,
        ':lp' => $logoFinal,
        ':id' => $issuedDate,
        ':an' => $advisorName,
        ':rn' => $registrarName,
        ':dn' => $directorName,
        ':asp' => $advisorSignFinal,
        ':rsp' => $registrarSignFinal,
        ':dsp' => $directorSignFinal,
    ]);
}

function report_uploads_dir(): string
{
    return dirname(__DIR__) . '/uploads';
}

/** Save uploaded image and return relative web path like 'uploads/school_logo.png' */
function report_image_save_from_upload(array $file, string $baseName): string
{
    if (!isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('อัปโหลดโลโก้ไม่สำเร็จ');
    }

    $tmp = (string)($file['tmp_name'] ?? '');
    $name = (string)($file['name'] ?? '');
    if ($tmp === '' || !is_file($tmp)) {
        throw new RuntimeException('ไฟล์อัปโหลดไม่ถูกต้อง');
    }

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['png', 'jpg', 'jpeg', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('รองรับเฉพาะไฟล์ PNG/JPG/WEBP');
    }

    $dir = report_uploads_dir();
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('สร้างโฟลเดอร์ uploads ไม่สำเร็จ');
        }
    }

    $safeBase = preg_replace('/[^a-z0-9_\-]/i', '_', $baseName);
    $safeBase = $safeBase !== '' ? $safeBase : 'image';
    $targetFs = $dir . '/' . $safeBase . '.' . $ext;
    if (!move_uploaded_file($tmp, $targetFs)) {
        throw new RuntimeException('บันทึกไฟล์โลโก้ไม่สำเร็จ');
    }

    return 'uploads/' . basename($targetFs);
}

function report_logo_save_from_upload(array $file): string
{
    return report_image_save_from_upload($file, 'school_logo');
}

function report_signature_save_from_upload(array $file, string $who): string
{
    return report_image_save_from_upload($file, 'sign_' . $who);
}
