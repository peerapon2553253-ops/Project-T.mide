<?php
require __DIR__ . '/config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}
check_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($id) {
    $stmt = db()->prepare('DELETE FROM `17_tasks` WHERE id = ?');
    $stmt->execute([$id]);
}
redirect('dashboard.php');
