<?php
require __DIR__ . '/config.php';
start_session();
enforce_suspension();
$search = trim($_GET['q'] ?? '');
$subjectFilter = $_GET['subject'] ?? '';
$params = [];
$where = [];
if ($search !== '') {
    $where[] = '(title LIKE ? OR description LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if (in_array($subjectFilter, SUBJECTS, true)) {
    $where[] = 'subject = ?';
    $params[] = $subjectFilter;
}
$stmt = db()->prepare('SELECT id, title, subject, description, due_date, answer_image FROM `17_tasks`' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY COALESCE(due_date, "9999-12-31"), created_at DESC');
$stmt->execute($params);
$tasks = $stmt->fetchAll();
$grouped = array_fill_keys(SUBJECTS, []);
foreach ($tasks as $task) $grouped[$task['subject']][] = $task;

$commentsBySubject = [];
if (!empty($_SESSION['user_id'])) {
    $comments = db()->query('SELECT c.id, c.task_id, c.parent_id, c.body, c.created_at, c.user_id, u.full_name, t.subject FROM `17_comments` c JOIN `17_users` u ON u.id = c.user_id JOIN `17_tasks` t ON t.id = c.task_id ORDER BY c.created_at ASC')->fetchAll();
    foreach ($comments as $comment) {
        $commentsBySubject[$comment['subject']][(int) $comment['id']] = $comment;
    }
}
$unreadCount = 0;
if (!empty($_SESSION['user_id'])) {
    $n = db()->prepare('SELECT COUNT(*) FROM `17_notifications` WHERE user_id = ? AND is_read = 0');
    $n->execute([$_SESSION['user_id']]);
    $unreadCount = (int) $n->fetchColumn();
}
$returnUrl = $_SERVER['REQUEST_URI'] ?? 'index.php';
function render_room_comments($comments, $parentId, $returnUrl)
{
    foreach ($comments as $comment) {
        if (($comment['parent_id'] === null ? null : (int) $comment['parent_id']) !== $parentId) continue;
        $avatar = mb_strtoupper(mb_substr($comment['full_name'], 0, 1));
        $replyCount = 0;
        foreach ($comments as $child) if ((int) ($child['parent_id'] ?? 0) === (int) $comment['id']) $replyCount++;
        echo '<div class="comment" id="comment-' . (int) $comment['id'] . '"><div class="comment-profile"><span class="avatar">' . e($avatar) . '</span><strong>' . e($comment['full_name']) . '</strong><small>' . e($comment['created_at']) . '</small></div>';
        if ($parentId !== null) echo '<div class="reply-context">↳ ตอบกลับ ' . e($comments[$parentId]['full_name'] ?? 'ผู้ใช้') . '</div>';
        echo '<p class="comment-text">' . nl2br(e($comment['body'])) . '</p><div class="comment-actions">';
        echo '<details class="reply-box"><summary>ตอบกลับ' . ($replyCount ? ' (' . $replyCount . ')' : '') . '</summary><form method="post" action="comment_action.php"><input type="hidden" name="csrf" value="' . e(csrf_token()) . '"><input type="hidden" name="task_id" value="' . (int) $comment['task_id'] . '"><input type="hidden" name="subject" value="' . e($comment['subject']) . '"><input type="hidden" name="parent_id" value="' . (int) $comment['id'] . '"><input type="hidden" name="return" value="' . e($returnUrl) . '"><textarea name="body" required maxlength="2000" placeholder="เขียนคำตอบ..."></textarea><button class="button small" type="submit">ส่งคำตอบ</button></form></details>';
        $mine = (int) $comment['user_id'] === (int) ($_SESSION['user_id'] ?? 0);
        if ($mine || !empty($_SESSION['is_admin'])) echo '<details class="comment-edit"><summary>แก้ไข</summary><form method="post" action="comment_action.php"><input type="hidden" name="csrf" value="' . e(csrf_token()) . '"><input type="hidden" name="action" value="edit"><input type="hidden" name="comment_id" value="' . (int) $comment['id'] . '"><input type="hidden" name="return" value="' . e($returnUrl) . '"><textarea name="body" required maxlength="2000">' . e($comment['body']) . '</textarea><button class="button small edit-button" type="submit">บันทึก</button></form></details>';
        if ($mine || !empty($_SESSION['is_admin'])) echo '<form method="post" action="comment_action.php" onsubmit="return confirm(\'ลบความคิดเห็นนี้?\')"><input type="hidden" name="csrf" value="' . e(csrf_token()) . '"><input type="hidden" name="action" value="delete"><input type="hidden" name="comment_id" value="' . (int) $comment['id'] . '"><input type="hidden" name="return" value="' . e($returnUrl) . '"><button class="comment-delete" type="submit" aria-label="ลบความคิดเห็น">×</button></form>';
        echo '</div>';
        render_room_comments($comments, (int) $comment['id'], $returnUrl);
        echo '</div>';
    }
}
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>รายวิชา | WorkCheck</title><link rel="stylesheet" href="style.css"></head>
<body><header class="topbar"><a class="brand" href="index.php"><span class="brand-mark"></span>WorkCheck</a><nav><a href="#tasks">รายวิชา</a><?php if (!empty($_SESSION['user_id'])): ?><a href="profile.php">โปรไฟล์</a><a class="nav-icon" href="notifications.php">🔔<?php if ($unreadCount): ?><span class="notification-badge"><?= $unreadCount ?></span><?php endif; ?></a><?php if (!empty($_SESSION['is_admin'])): ?><a href="dashboard.php">แดชบอร์ด</a><?php endif; ?><a href="logout.php">ออกจากระบบ</a><?php else: ?><a href="Login.php?return=<?= urlencode($returnUrl) ?>">เข้าสู่ระบบ</a><?php endif; ?><a class="button small" href="Login.php?admin=1">ผู้ดูแล</a></nav></header>
<main class="container"><section class="hero"><p class="eyebrow">ระบบตรวจสอบงานรายวิชา</p><h1>ค้นหางาน<br><em>แยกตามรายวิชา</em></h1><p class="lead">เลือกวิชาเพื่อดูงานและกำหนดส่ง รูปเฉลยดูได้เมื่อเข้าสู่ระบบ</p></section>
<section id="tasks" class="section"><div class="section-heading"><div><p class="eyebrow">ทั้งหมด 18 วิชา</p><h2>รายวิชา</h2></div></div><form class="search-form" method="get"><input type="search" name="q" placeholder="ค้นหาชื่องานหรือรายละเอียด..." value="<?= e($search) ?>"><button class="button small" type="submit">ค้นหา</button></form>
<div class="subject-links"><?php foreach (SUBJECTS as $subject): ?><a class="<?= $subjectFilter === $subject ? 'active' : '' ?>" href="?subject=<?= urlencode($subject) ?>#subject-<?= md5($subject) ?>"><?= e($subject) ?></a><?php endforeach; ?></div>
<?php $visible = $subjectFilter && in_array($subjectFilter, SUBJECTS, true) ? [$subjectFilter] : SUBJECTS; foreach ($visible as $subject): ?><section class="subject-section" id="subject-<?= md5($subject) ?>"><div class="subject-heading"><h3><?= e($subject) ?></h3><span class="count"><?= count($grouped[$subject]) ?> งาน</span></div><?php if (!$grouped[$subject]): ?><p class="muted subject-empty">ยังไม่มีงานในรายวิชานี้</p><?php else: ?><div class="task-grid"><?php foreach ($grouped[$subject] as $task): ?><article class="task-card"><h3><?= e($task['title']) ?></h3><?php if ($task['description'] !== ''): ?><p><?= nl2br(e($task['description'])) ?></p><?php endif; ?><?php if ($task['due_date']): ?><small>กำหนดส่ง <?= e(date('d/m/Y', strtotime($task['due_date']))) ?></small><?php endif; ?><?php if ($task['answer_image'] && !empty($_SESSION['user_id'])): ?><a class="button small answer-button" href="answer.php?file=<?= urlencode($task['answer_image']) ?>" target="_blank">กดที่นี่เพื่อดูเฉลย</a><?php elseif ($task['answer_image']): ?><p class="answer-locked"><a href="Login.php?return=<?= urlencode($returnUrl) ?>">เข้าสู่ระบบเพื่อดูเฉลย</a></p><?php endif; ?></article><?php endforeach; ?></div><?php endif; ?><?php if (!empty($_SESSION['user_id'])): ?><button class="comment-trigger" type="button" data-chat="chat-<?= md5($subject) ?>">💬 ห้องแชท <?= e($subject) ?></button><div class="chat-modal" id="chat-<?= md5($subject) ?>"><div class="chat-window"><div class="chat-header"><strong>ห้องแชท <?= e($subject) ?></strong><button class="chat-close" type="button" data-close-chat>×</button></div><div class="chat-body"><form method="post" action="comment_action.php" class="chat-compose"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="task_id" value="<?= (int) ($grouped[$subject][0]['id'] ?? 0) ?>"><input type="hidden" name="subject" value="<?= e($subject) ?>"><input type="hidden" name="return" value="<?= e($returnUrl) ?>"><textarea name="body" required maxlength="2000" placeholder="เขียนข้อความในห้องนี้..."></textarea><button class="button small" type="submit">ส่ง</button></form><?php render_room_comments(array_values($commentsBySubject[$subject] ?? []), null, $returnUrl); ?></div></div></div><?php endif; ?></section><?php endforeach; ?></section></main><footer class="container footer">© 2026 WorkCheck · ระบบบันทึกงานรายวิชา</footer>
<script>document.querySelectorAll('[data-chat]').forEach(function(b){b.onclick=function(){document.getElementById(b.dataset.chat).classList.add('open')}});document.querySelectorAll('[data-close-chat]').forEach(function(b){b.onclick=function(){b.closest('.chat-modal').classList.remove('open')}});document.querySelectorAll('.chat-modal').forEach(function(m){m.onclick=function(e){if(e.target===m)m.classList.remove('open')}});document.addEventListener('keydown',function(e){if(e.key==='Escape')document.querySelectorAll('.chat-modal.open').forEach(function(m){m.classList.remove('open')})});var c=new URLSearchParams(location.search).get('comment');if(c){var t=document.getElementById('comment-'+c);if(t){var m=t.closest('.chat-modal');if(m)m.classList.add('open');t.scrollIntoView({block:'center'})}}</script></body></html>
