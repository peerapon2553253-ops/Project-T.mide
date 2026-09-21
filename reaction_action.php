<?php
require __DIR__ . '/config.php';
require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
check_csrf();
$commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
$reaction = $_POST['reaction'] ?? '';
$return = $_POST['return'] ?? 'index.php';
$allowed = ['👍', '❤️', '😂', '😮', '😢'];
if ($commentId && in_array($reaction, $allowed, true)) {
    $stmt = db()->prepare('SELECT c.user_id, t.title FROM `17_comments` c JOIN `17_tasks` t ON t.id = c.task_id WHERE c.id = ?');
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();
    if ($comment) {
        $existing = db()->prepare('SELECT id, reaction FROM `17_reactions` WHERE comment_id = ? AND user_id = ?');
        $existing->execute([$commentId, $_SESSION['user_id']]);
        $old = $existing->fetch();
        if ($old && $old['reaction'] === $reaction) {
            db()->prepare('DELETE FROM `17_reactions` WHERE id = ?')->execute([$old['id']]);
        } elseif ($old) {
            db()->prepare('UPDATE `17_reactions` SET reaction = ?, created_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$reaction, $old['id']]);
        } else {
            db()->prepare('INSERT INTO `17_reactions` (comment_id, user_id, reaction) VALUES (?, ?, ?)')->execute([$commentId, $_SESSION['user_id'], $reaction]);
            if ((int) $comment['user_id'] !== (int) $_SESSION['user_id']) {
                $notice = db()->prepare('INSERT INTO `17_notifications` (user_id, actor_id, comment_id, type, message) VALUES (?, ?, ?, "reaction", ?)');
                $notice->execute([$comment['user_id'], $_SESSION['user_id'], $commentId, 'มีคนแสดงความรู้สึกต่อความคิดเห็นในงาน ' . $comment['title']]);
            }
        }
    }
}
redirect($return);
