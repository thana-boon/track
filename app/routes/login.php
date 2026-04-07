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

        $ip = client_ip();
        $lockedSeconds = auth_login_throttle_locked_seconds($username, $ip);
        if ($lockedSeconds > 0) {
            $mins = (int)max(1, (int)ceil($lockedSeconds / 60));
            $error = 'พยายามเข้าสู่ระบบผิดหลายครั้ง ระบบบล็อคชั่วคราว ' . $mins . ' นาที (เหลือโอกาสอีก 0 ครั้ง)';
            activity_log_write('login_blocked', ['username' => $username, 'ip' => $ip, 'locked_seconds' => $lockedSeconds, 'remaining_attempts' => 0]);
        } else {
            if (!auth_attempt($username, $password)) {
                $st = auth_login_throttle_register_failure($username, $ip);
                $locked = (int)($st['locked_seconds'] ?? 0);
                $remainAttempts = (int)($st['remaining_attempts'] ?? 0);
                $maxAttempts = (int)($st['max_attempts'] ?? 5);
                $failCount = (int)($st['fail_count'] ?? 0);

                if ($locked > 0) {
                    $mins = (int)max(1, (int)ceil($locked / 60));
                    $error = 'พยายามเข้าสู่ระบบผิดเกิน ' . $maxAttempts . ' ครั้ง ระบบบล็อคชั่วคราว ' . $mins . ' นาที (เหลือโอกาสอีก 0 ครั้ง)';
                    activity_log_write('login_blocked', ['username' => $username, 'ip' => $ip, 'locked_seconds' => $locked, 'remaining_attempts' => 0, 'fail_count' => $failCount, 'max_attempts' => $maxAttempts]);
                } else {
                    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง (เหลือโอกาสอีก ' . $remainAttempts . ' ครั้ง)';
                    activity_log_write('login_failed', ['username' => $username, 'ip' => $ip, 'remaining_attempts' => $remainAttempts, 'fail_count' => $failCount, 'max_attempts' => $maxAttempts]);
                }
            } else {
                auth_login_throttle_clear($username, $ip);
                $_SESSION['__last_activity_at'] = time();
                activity_log_write('login_success', ['username' => $username, 'ip' => $ip]);
                redirect('dashboard');
            }
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
