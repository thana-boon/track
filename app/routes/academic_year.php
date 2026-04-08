<?php
declare(strict_types=1);

require_auth();

$pdo = db_school();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = input_string('action');

    if ($action === 'set_active') {
        $yearIdRaw = input_string('year_id');
        if ($yearIdRaw === '' || !ctype_digit($yearIdRaw)) {
            $error = 'กรุณาเลือกปีการศึกษาที่ถูกต้อง';
        } else {
            $yearId = (int)$yearIdRaw;

            // Ensure the selected year exists
            $stmt = $pdo->prepare('SELECT id FROM academic_years WHERE id = ? LIMIT 1');
            $stmt->execute([$yearId]);
            $exists = $stmt->fetchColumn();

            if (!$exists) {
                $error = 'ไม่พบปีการศึกษาที่เลือก';
            } else {
                try {
                    $pdo->beginTransaction();
                    $pdo->exec('UPDATE academic_years SET is_active = 0');
                    $stmt = $pdo->prepare('UPDATE academic_years SET is_active = 1 WHERE id = ?');
                    $stmt->execute([$yearId]);
                    $pdo->commit();

                    flash_set('success', 'ตั้งค่า “ปีการศึกษาปัจจุบัน” เรียบร้อยแล้ว');
                    redirect('academic_year');
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = APP_DEBUG ? ('บันทึกไม่สำเร็จ: ' . $e->getMessage()) : 'บันทึกไม่สำเร็จ';
                }
            }
        }
    }

    if ($action === 'set_active_term') {
        $termRaw = input_string('term');
        $termRaw = trim($termRaw);
        if (!in_array($termRaw, ['1', '2'], true)) {
            $error = 'กรุณาเลือกเทอมที่ถูกต้อง';
        } else {
            try {
                track_settings_set_active_term((int)$termRaw);
                flash_set('success', 'ตั้งค่า “เทอมปัจจุบัน (Track)” เรียบร้อยแล้ว');
                redirect('academic_year');
            } catch (Throwable $e) {
                $error = APP_DEBUG ? ('บันทึกไม่สำเร็จ: ' . $e->getMessage()) : 'บันทึกไม่สำเร็จ';
            }
        }
    }
}

$years = $pdo->query('SELECT id, year_be, title, is_active FROM academic_years ORDER BY year_be DESC')->fetchAll();

$activeYear = null;
foreach ($years as $y) {
    if ((int)($y['is_active'] ?? 0) === 1) {
        $activeYear = $y;
        break;
    }
}
if (!$activeYear && isset($years[0])) {
    $activeYear = $years[0];
}

$success = flash_get('success');
$activeTerm = track_active_term();

echo render('academic_year/index', [
    'title' => 'ตั้งค่าปีการศึกษา',
    'years' => $years,
    'activeYear' => $activeYear,
    'activeTerm' => $activeTerm,
    'success' => $success,
    'error' => $error,
]);
