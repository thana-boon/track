<?php
declare(strict_types=1);

require_auth();
track_subjects_table_ensure();

$error = null;
$success = flash_get('success');

// Export CSV (GET)
if (query_string('export') === '1') {
    $token = query_string('_csrf');
    if ($token === '' || !hash_equals((string)($_SESSION['_csrf'] ?? ''), $token)) {
        http_response_code(403);
        echo render('errors/419', ['title' => 'CSRF ไม่ถูกต้อง']);
        exit;
    }
    $subjects = track_subjects_all();
    track_subjects_export_csv($subjects);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = input_string('action');

    try {
        if ($action === 'import_csv') {
            if (!isset($_FILES['csv_file']) || !is_array($_FILES['csv_file'])) {
                throw new RuntimeException('กรุณาเลือกไฟล์ CSV');
            }

            $file = $_FILES['csv_file'];
            $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($err !== UPLOAD_ERR_OK) {
                throw new RuntimeException('อัปโหลดไฟล์ไม่สำเร็จ');
            }

            $size = (int)($file['size'] ?? 0);
            if ($size <= 0 || $size > 2 * 1024 * 1024) {
                throw new RuntimeException('ไฟล์ใหญ่เกินไป (จำกัด 2MB)');
            }

            $tmp = (string)($file['tmp_name'] ?? '');
            $mode = input_string('import_mode');
            $mode = ($mode === 'skip') ? 'skip' : 'upsert';

            $stats = track_subjects_import_csv($tmp, $mode);
            flash_set('success', 'นำเข้า CSV สำเร็จ: เพิ่ม ' . $stats['inserted'] . ' • แก้ไข ' . $stats['updated'] . ' • ข้าม ' . $stats['skipped']);
            redirect('track-subjects');
        }

        if ($action === 'create') {
            $title = input_string('title');
            $description = input_string('description');
            $isActive = input_string('is_active') === '1';

            track_subject_create($title, $description, $isActive);
            flash_set('success', 'เพิ่มวิชา Track แล้ว');
            redirect('track-subjects');
        }

        if ($action === 'update') {
            $id = (int)input_string('id');
            $title = input_string('title');
            $description = input_string('description');
            $isActive = input_string('is_active') === '1';

            track_subject_update($id, $title, $description, $isActive);
            flash_set('success', 'แก้ไขวิชาแล้ว');
            redirect('track-subjects');
        }

        if ($action === 'delete') {
            $id = (int)input_string('id');
            track_subject_delete($id);
            flash_set('success', 'ลบวิชาแล้ว');
            redirect('track-subjects');
        }

        throw new RuntimeException('คำสั่งไม่ถูกต้อง');
    } catch (PDOException $e) {
        $msg = 'เกิดข้อผิดพลาด';
        if (str_contains($e->getMessage(), 'uq_track_subjects_title')) {
            $msg = 'ชื่อวิชาซ้ำ กรุณาเปลี่ยนชื่อ';
        }
        $error = $msg;
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'เกิดข้อผิดพลาด';
    }
}

$subjects = track_subjects_all();

$editId = (int)query_string('edit');
$editing = null;
if ($editId > 0) {
    foreach ($subjects as $s) {
        if ((int)$s['id'] === $editId) {
            $editing = $s;
            break;
        }
    }
}

echo render('track_subjects/index', [
    'title' => 'วิชา Track',
    'subjects' => $subjects,
    'editing' => $editing,
    'error' => $error,
    'success' => $success,
]);
