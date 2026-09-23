<?php
require __DIR__ . '/config.php';
require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
check_csrf();
$taskId = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
$subject = trim($_POST['subject'] ?? '');
$parentId = filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT) ?: null;
$commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT) ?: 0;
$action = $_POST['action'] ?? 'create';
$body = trim($_POST['body'] ?? '');
$return = $_POST['return'] ?? 'index.php';
try {
    if ($action === 'delete' && $commentId) {
        $sql = !empty($_SESSION['is_admin']) ? 'DELETE FROM `17_comments` WHERE id = ?' : 'DELETE FROM `17_comments` WHERE id = ? AND user_id = ?';
        $delete = db()->prepare($sql);
        $delete->execute(!empty($_SESSION['is_admin']) ? [$commentId] : [$commentId, $_SESSION['user_id']]);
        flash('success', 'ลบความคิดเห็นแล้ว');
    } elseif ($action === 'edit' && $commentId && $body !== '' && strlen($body) <= 2000) {
        if (!empty($_SESSION['is_admin'])) {
            $update = db()->prepare('UPDATE `17_comments` SET body = ? WHERE id = ?');
            $update->execute([$body, $commentId]);
        } else {
            $update = db()->prepare('UPDATE `17_comments` SET body = ? WHERE id = ? AND user_id = ?');
            $update->execute([$body, $commentId, $_SESSION['user_id']]);
        }
        flash('success', 'แก้ไขความคิดเห็นแล้ว');
    } elseif ($taskId && $body !== '' && strlen($body) <= 2000) {
        $stmt = db()->prepare('SELECT id, title FROM `17_tasks` WHERE id = ?');
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        if ($task) {
            $parentComment = null;
            if ($parentId) {
                $parent = db()->prepare('SELECT c.id, c.user_id FROM `17_comments` c JOIN `17_tasks` t ON t.id = c.task_id WHERE c.id = ? AND t.subject = ?');
                $parent->execute([$parentId, $subject]);
                $parentComment = $parent->fetch();
            }
            $insert = db()->prepare('INSERT INTO `17_comments` (task_id, user_id, parent_id, body) VALUES (?, ?, ?, ?)');
            $insert->execute([$taskId, $_SESSION['user_id'], $parentId, $body]);
            if (!empty($parentComment) && (int) $parentComment['user_id'] !== (int) $_SESSION['user_id']) {
                $notice = db()->prepare('INSERT INTO `17_notifications` (user_id, actor_id, comment_id, type, message) VALUES (?, ?, ?, "reply", ?)');
                $notice->execute([$parentComment['user_id'], $_SESSION['user_id'], db()->lastInsertId(), 'ตอบกลับความคิดเห็นในงาน ' . $task['title']]);
            }
            flash('success', 'ส่งความคิดเห็นแล้ว');
        }
    } else {
        flash('error', 'ข้อมูลความคิดเห็นไม่ถูกต้อง');
    }
} catch (PDOException $exception) {
    error_log('WorkCheck comment_action: ' . $exception->getMessage());
    flash('error', 'บันทึกความคิดเห็นไม่สำเร็จ กรุณาตรวจสอบโครงสร้างฐานข้อมูลบนเซิร์ฟเวอร์');
}
redirect($return);
