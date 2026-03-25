<?php
declare(strict_types=1);

if (auth_user()) {
    redirect('dashboard');
}

users_table_ensure();

$error = null;
$success = flash_get('success');
$hasUsers = users_count() > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = input_string('action');

    if (!$hasUsers && $action === 'setup') {
        $username = input_string('username');
        $password = input_string('password');
        $confirm = input_string('confirm_password');
        $displayname = input_string('displayname');

        if ($username === '' || $password === '' || $displayname === '') {
            $error = 'กรุณากรอกข้อมูลให้ครบ';
        } elseif ($password !== $confirm) {
            $error = 'รหัสผ่านกับยืนยันรหัสผ่านไม่ตรงกัน';
        } else {
            try {
                auth_create_user($username, $password, $displayname);
                auth_attempt($username, $password);
                redirect('dashboard');
            } catch (PDOException $e) {
                $error = 'ไม่สามารถสร้างผู้ใช้ได้ (username อาจซ้ำ)';
            }
        }
    } else {
        $username = input_string('username');
        $password = input_string('password');

        if (!auth_attempt($username, $password)) {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        } else {
            redirect('dashboard');
        }
    }

    $hasUsers = users_count() > 0;
}

echo render('auth/login', [
    'title' => 'เข้าสู่ระบบ',
    'error' => $error,
    'success' => $success,
    'hasUsers' => $hasUsers,
]);
