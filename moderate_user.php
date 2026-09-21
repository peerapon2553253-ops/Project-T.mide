<?php
require __DIR__ . '/config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('dashboard.php');
check_csrf();
$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$days = trim($_POST['days'] ?? '');
if ($userId && $userId !== (int) $_SESSION['user_id']) {
    if ($days === 'permanent') {
        $stmt = db()->prepare('UPDATE `17_users` SET suspended_permanent = 1, suspended_until = NULL WHERE id = ?');
        $stmt->execute([$userId]);
    } elseif (ctype_digit($days) && (int) $days > 0) {
        $until = date('Y-m-d H:i:s', strtotime('+' . (int) $days . ' days'));
        $stmt = db()->prepare('UPDATE `17_users` SET suspended_permanent = 0, suspended_until = ? WHERE id = ?');
        $stmt->execute([$until, $userId]);
    } elseif ($days === 'unsuspend') {
        $stmt = db()->prepare('UPDATE `17_users` SET suspended_permanent = 0, suspended_until = NULL WHERE id = ?');
        $stmt->execute([$userId]);
    }
}
redirect('dashboard.php');
