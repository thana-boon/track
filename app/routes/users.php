<?php
declare(strict_types=1);

require_auth();
users_table_ensure();

$pdo = db_app();
$q = trim(query_string('q'));
$roleFilter = strtolower(trim(query_string('role')));
$page = (int)(query_string('page') !== '' ? query_string('page') : '1');
$page = max(1, $page);
$pageSize = 20;

if (!in_array($roleFilter, ['', 'admin', 'teacher', 'student'], true)) {
    $roleFilter = '';
}

$baseQuery = http_build_query(array_filter([
    'q' => $q !== '' ? $q : null,
    'role' => $roleFilter !== '' ? $roleFilter : null,
], static fn($v) => $v !== null && $v !== ''));

$back = '/tracks/users' . ($baseQuery !== '' ? ('?' . $baseQuery) : '');
if ($page > 1) {
    $back .= ($baseQuery !== '' ? '&' : '?') . 'page=' . $page;
}

$error = null;
$success = flash_get('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = input_string('action');

    try {
        if ($action === 'create') {
            $username = input_string('username');
            $password = input_string('password');
            $confirm = input_string('confirm_password');
            $displayname = input_string('displayname');
            $role = input_string('role');

            if ($username === '' || $password === '' || $displayname === '') {
                throw new RuntimeException('กรุณากรอกข้อมูลให้ครบ');
            }

            if ($password !== $confirm) {
                throw new RuntimeException('รหัสผ่านกับยืนยันรหัสผ่านไม่ตรงกัน');
            }

            auth_create_user($username, $password, $displayname, $role);
            flash_set('success', 'เพิ่มผู้ใช้สำเร็จ');
            header('Location: ' . $back);
            exit;
        }

        if ($action === 'delete') {
            $id = (int)input_string('id');
            $me = auth_user();
            if ($me && (int)$me['id'] === $id) {
                throw new RuntimeException('ไม่สามารถลบผู้ใช้ที่กำลังใช้งานอยู่ได้');
            }
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute([':id' => $id]);
            flash_set('success', 'ลบผู้ใช้แล้ว');
            header('Location: ' . $back);
            exit;
        }

        if ($action === 'reset_password') {
            $id = (int)input_string('id');
            $newPass = input_string('new_password');
            $confirm = input_string('confirm_new_password');
            if ($newPass === '') {
                throw new RuntimeException('กรุณากรอกรหัสผ่านใหม่');
            }
            if ($newPass !== $confirm) {
                throw new RuntimeException('รหัสผ่านใหม่กับยืนยันรหัสผ่านไม่ตรงกัน');
            }
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
            $stmt->execute([':h' => $hash, ':id' => $id]);
            flash_set('success', 'ตั้งรหัสผ่านใหม่แล้ว');
            header('Location: ' . $back);
            exit;
        }

        if ($action === 'update_display') {
            $id = (int)input_string('id');
            $display = input_string('displayname');
            if ($display === '') {
                throw new RuntimeException('กรุณากรอกชื่อที่แสดง');
            }
            $stmt = $pdo->prepare('UPDATE users SET displayname = :d WHERE id = :id');
            $stmt->execute([':d' => $display, ':id' => $id]);
            flash_set('success', 'อัปเดตชื่อที่แสดงแล้ว');
            header('Location: ' . $back);
            exit;
        }

        if ($action === 'update_role') {
            $id = (int)input_string('id');
            $role = strtolower(trim(input_string('role')));
            if (!in_array($role, ['admin', 'teacher', 'student'], true)) {
                throw new RuntimeException('role ไม่ถูกต้อง');
            }
            $stmt = $pdo->prepare('UPDATE users SET role = :r WHERE id = :id');
            $stmt->execute([':r' => $role, ':id' => $id]);
            flash_set('success', 'อัปเดต role แล้ว');
            header('Location: ' . $back);
            exit;
        }

        if ($action === 'import') {
            $importText = trim(input_string('import_text'));
            $defaultRole = strtolower(trim(input_string('default_role')));
            $defaultPass = input_string('default_password');
            $confirmPass = input_string('confirm_default_password');

            if ($importText === '') {
                throw new RuntimeException('กรุณาวางรายชื่อผู้ใช้ที่ต้องการ import');
            }
            if (!in_array($defaultRole, ['admin', 'teacher', 'student'], true)) {
                $defaultRole = 'teacher';
            }
            if ($defaultPass === '') {
                throw new RuntimeException('กรุณากรอกรหัสผ่านเริ่มต้น');
            }
            if ($defaultPass !== $confirmPass) {
                throw new RuntimeException('รหัสผ่านเริ่มต้นกับยืนยันรหัสผ่านไม่ตรงกัน');
            }

            $lines = preg_split('/\R/u', $importText) ?: [];
            $created = 0;
            $skipped = 0;

            foreach ($lines as $line) {
                $line = trim((string)$line);
                if ($line === '') {
                    continue;
                }

                // Format: username[,displayname[,role]]
                $parts = array_map('trim', explode(',', $line));
                $username = $parts[0] ?? '';
                $displayname = $parts[1] ?? '';
                $role = $parts[2] ?? '';

                if ($username === '') {
                    continue;
                }
                if ($displayname === '') {
                    $displayname = $username;
                }
                if ($role === '') {
                    $role = $defaultRole;
                }

                try {
                    auth_create_user($username, $defaultPass, $displayname, $role);
                    $created++;
                } catch (Throwable $e) {
                    // likely duplicate username
                    $skipped++;
                }
            }

            flash_set('success', "Import สำเร็จ: เพิ่ม {$created} คน • ข้าม {$skipped} รายการ");
            header('Location: ' . $back);
            exit;
        }

        if ($action === 'import_csv') {
            $defaultRole = strtolower(trim(input_string('default_role')));
            if (!in_array($defaultRole, ['admin', 'teacher', 'student'], true)) {
                $defaultRole = 'teacher';
            }

            $file = $_FILES['import_file'] ?? null;
            if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('กรุณาเลือกไฟล์ CSV');
            }

            $tmp = (string)($file['tmp_name'] ?? '');
            if ($tmp === '' || !is_file($tmp)) {
                throw new RuntimeException('อ่านไฟล์ CSV ไม่ได้');
            }

            $fh = fopen($tmp, 'rb');
            if ($fh === false) {
                throw new RuntimeException('อ่านไฟล์ CSV ไม่ได้');
            }

            $created = 0;
            $skipped = 0;
            $rowIndex = 0;

            while (($row = fgetcsv($fh)) !== false) {
                $rowIndex++;
                if (!is_array($row)) {
                    continue;
                }

                $row = array_map(static fn($v) => trim((string)$v), $row);
                $row = array_values(array_filter($row, static fn($v) => $v !== ''));
                if (empty($row)) {
                    continue;
                }

                // allow header row
                if ($rowIndex === 1) {
                    $first = strtolower((string)($row[0] ?? ''));
                    if ($first === 'username') {
                        continue;
                    }
                }

                // Format (recommended): username, displayname, password, role
                // Also accepts: username, displayname, password  (role uses default)
                $username = (string)($row[0] ?? '');
                $displayname = (string)($row[1] ?? '');
                $password = (string)($row[2] ?? '');
                $role = strtolower((string)($row[3] ?? ''));

                if ($username === '') {
                    continue;
                }
                if ($displayname === '') {
                    $displayname = $username;
                }
                if ($password === '') {
                    $skipped++;
                    continue;
                }
                if (!in_array($role, ['admin', 'teacher', 'student'], true)) {
                    $role = $defaultRole;
                }

                try {
                    auth_create_user($username, $password, $displayname, $role);
                    $created++;
                } catch (Throwable $e) {
                    $skipped++;
                }
            }

            fclose($fh);

            flash_set('success', "นำเข้า CSV สำเร็จ: เพิ่ม {$created} คน • ข้าม {$skipped} รายการ");
            header('Location: ' . $back);
            exit;
        }

        throw new RuntimeException('คำสั่งไม่ถูกต้อง');
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'เกิดข้อผิดพลาด';
    }
}

// Listing: filters + pagination
$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(username LIKE ? OR displayname LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}

if ($roleFilter !== '') {
    $where[] = 'role = ?';
    $params[] = $roleFilter;
}

$sqlWhere = count($where) > 0 ? (' WHERE ' . implode(' AND ', $where)) : '';

$stmtCount = $pdo->prepare('SELECT COUNT(*) FROM users' . $sqlWhere);
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();

$totalPages = (int)max(1, (int)ceil($total / $pageSize));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $pageSize;

$stmtList = $pdo->prepare(
    'SELECT id, username, displayname, role, created_at '
    . 'FROM users '
    . $sqlWhere . ' '
    . 'ORDER BY id DESC '
    . 'LIMIT ' . (int)$pageSize . ' OFFSET ' . (int)$offset
);
$stmtList->execute($params);
$users = $stmtList->fetchAll();

echo render('users/index', [
    'title' => 'จัดการผู้ใช้',
    'users' => $users,
    'filters' => [
        'q' => $q,
        'role' => $roleFilter,
    ],
    'pagination' => [
        'page' => $page,
        'pageSize' => $pageSize,
        'total' => $total,
        'totalPages' => $totalPages,
        'baseQuery' => $baseQuery,
    ],
    'error' => $error,
    'success' => $success,
]);
