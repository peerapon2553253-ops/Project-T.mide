<?php
require __DIR__ . '/config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}
check_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    flash('error', 'ไม่พบงานที่ต้องการลบ');
    redirect('dashboard.php');
}
try {
    $pdo = db();
    $pdo->beginTransaction();
    $find = $pdo->prepare('SELECT answer_image FROM `17_tasks` WHERE id = ?');
    $find->execute([$id]);
    $task = $find->fetch();
    if (!$task) {
        $pdo->rollBack();
        flash('error', 'ไม่พบงานนี้ หรือรายการถูกลบไปแล้ว');
        redirect('dashboard.php');
    }
    $delete = $pdo->prepare('DELETE FROM `17_tasks` WHERE id = ?');
    $delete->execute([$id]);
    $pdo->commit();
    $image = local_upload_path($task['answer_image']);
    if ($image && is_file($image)) {
        unlink($image);
    }
    flash('success', 'ลบงานเรียบร้อยแล้ว');
} catch (PDOException $exception) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash('error', 'ลบงานไม่สำเร็จ: ' . $exception->getMessage());
}
redirect('dashboard.php');
