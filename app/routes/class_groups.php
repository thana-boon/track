<?php
declare(strict_types=1);

require_auth();
class_groups_table_ensure();

$error = null;
$success = flash_get('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = input_string('action');

    try {
        if ($action === 'create') {
            $title = input_string('title');
            $codesText = (string)($_POST['student_codes_text'] ?? '');
            class_group_create($title, $codesText);
            flash_set('success', 'สร้างกลุ่มเรียน "' . $title . '" แล้ว ✅');
            redirect('class-groups');
        }

        if ($action === 'update') {
            $id = (int)input_string('id');
            $title = input_string('title');
            $codesText = (string)($_POST['student_codes_text'] ?? '');
            class_group_update($id, $title, $codesText);
            flash_set('success', 'แก้ไขกลุ่มเรียนแล้ว ✅');
            redirect('class-groups');
        }

        if ($action === 'delete') {
            $id = (int)input_string('id');
            class_group_delete($id);
            flash_set('success', 'ลบกลุ่มเรียนแล้ว');
            redirect('class-groups');
        }

        throw new RuntimeException('คำสั่งไม่ถูกต้อง');
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'เกิดข้อผิดพลาด';
    }
}

$groups = class_groups_all();

$editId = (int)query_string('edit');
$editing = null;
if ($editId > 0) {
    foreach ($groups as $g) {
        if ((int)$g['id'] === $editId) {
            $editing = $g;
            break;
        }
    }
}

$csrf = csrf_token();

echo render('class_groups/index', [
    'title' => 'กลุ่มเรียน',
    'csrf' => $csrf,
    'groups' => $groups,
    'editing' => $editing,
    'error' => $error,
    'success' => $success,
]);
