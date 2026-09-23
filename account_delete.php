<?php
require __DIR__ . '/config.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('profile.php');
}

check_csrf();

if (!empty($_SESSION['is_admin'])) {
    flash('error', 'ไม่สามารถลบบัญชีผู้ดูแลจากหน้านี้ได้');
    redirect('profile.php');
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId < 1) {
    redirect('Login.php');
}

try {
    $stmt = db()->prepare('DELETE FROM `17_users` WHERE id = ?');
    $stmt->execute([$userId]);
    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('ไม่พบบัญชีที่กำลังใช้งาน');
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: index.php?deleted=1');
    exit;
} catch (Throwable $exception) {
    error_log('WorkCheck account_delete: ' . $exception->getMessage());
    flash('error', 'ลบบัญชีไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
    redirect('profile.php');
}
