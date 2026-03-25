<?php
declare(strict_types=1);

require_auth();
users_table_ensure();

$pdo = db_app();
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

            if ($username === '' || $password === '' || $displayname === '') {
                throw new RuntimeException('กรุณากรอกข้อมูลให้ครบ');
            }

            if ($password !== $confirm) {
                throw new RuntimeException('รหัสผ่านกับยืนยันรหัสผ่านไม่ตรงกัน');
            }

            auth_create_user($username, $password, $displayname);
            flash_set('success', 'เพิ่มผู้ใช้สำเร็จ');
            redirect('users');
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
            redirect('users');
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
            redirect('users');
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
            redirect('users');
        }

        throw new RuntimeException('คำสั่งไม่ถูกต้อง');
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'เกิดข้อผิดพลาด';
    }
}

$users = $pdo->query('SELECT id, username, displayname, created_at FROM users ORDER BY id DESC')->fetchAll();

echo render('users/index', [
    'title' => 'จัดการผู้ใช้',
    'users' => $users,
    'error' => $error,
    'success' => $success,
]);
