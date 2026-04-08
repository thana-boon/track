<?php
declare(strict_types=1);

require_auth();
track_groups_table_ensure();

$error = null;
$success = flash_get('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = input_string('action');

    try {
        if ($action === 'create') {
            $title = input_string('title');
            $isActive = input_string('is_active') === '1';

            track_group_create($title, $isActive);
            flash_set('success', 'เพิ่มกลุ่ม Track แล้ว');
            redirect('track-groups');
        }

        if ($action === 'update') {
            $id = (int)input_string('id');
            $title = input_string('title');
            $isActive = input_string('is_active') === '1';

            track_group_update($id, $title, $isActive);
            flash_set('success', 'แก้ไขกลุ่มแล้ว');
            redirect('track-groups');
        }

        if ($action === 'delete') {
            $id = (int)input_string('id');
            track_group_delete($id);
            flash_set('success', 'ลบกลุ่มแล้ว');
            redirect('track-groups');
        }

        throw new RuntimeException('คำสั่งไม่ถูกต้อง');
    } catch (PDOException $e) {
        $msg = 'เกิดข้อผิดพลาด';
        if (str_contains($e->getMessage(), 'uq_track_groups_title')) {
            $msg = 'ชื่อกลุ่มซ้ำ กรุณาเปลี่ยนชื่อ';
        }
        $error = $msg;
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'เกิดข้อผิดพลาด';
    }
}

$groups = track_groups_all();

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

echo render('track_groups/index', [
    'title' => 'กลุ่ม Track',
    'groups' => $groups,
    'editing' => $editing,
    'error' => $error,
    'success' => $success,
]);
