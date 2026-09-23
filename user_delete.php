<?php
require __DIR__ . '/config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}
check_csrf();
$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
if (!$userId || $userId === (int) $_SESSION['user_id']) {
    flash('error', 'ไม่สามารถลบบัญชีผู้ดูแลที่กำลังใช้งานได้');
    redirect('dashboard.php');
}
try {
    $stmt = db()->prepare('DELETE FROM `17_users` WHERE id = ?');
    $stmt->execute([$userId]);
    flash($stmt->rowCount() ? 'success' : 'error', $stmt->rowCount() ? 'ลบบัญชีเรียบร้อยแล้ว' : 'ไม่พบบัญชีนี้');
} catch (PDOException $exception) {
    flash('error', 'ลบบัญชีไม่สำเร็จ: ' . $exception->getMessage());
}
redirect('dashboard.php');
