<?php
require __DIR__ . '/config.php';
require_auth();
$stmt = db()->prepare('SELECT n.*, u.full_name AS actor_name FROM `17_notifications` n LEFT JOIN `17_users` u ON u.id = n.actor_id WHERE n.user_id = ? ORDER BY n.created_at DESC LIMIT 50');
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();
db()->prepare('UPDATE `17_notifications` SET is_read = 1 WHERE user_id = ?')->execute([$_SESSION['user_id']]);
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>การแจ้งเตือน | WorkCheck</title><link rel="stylesheet" href="style.css"></head>
<body><header class="topbar"><a class="brand" href="index.php"><span class="brand-mark"></span>WorkCheck</a><nav><a href="index.php">หน้าหลัก</a><a href="profile.php">โปรไฟล์</a></nav></header><main class="container section"><h1>การแจ้งเตือน</h1><div class="notification-list"><?php if (!$notifications): ?><div class="empty">ยังไม่มีการแจ้งเตือน</div><?php else: ?><?php foreach ($notifications as $notification): ?><a class="notification-item" href="index.php?comment=<?= (int) $notification['comment_id'] ?>#comment-<?= (int) $notification['comment_id'] ?>"><strong><?= e($notification['actor_name'] ?? 'ผู้ใช้') ?></strong> <?= e($notification['message']) ?><small><?= e($notification['created_at']) ?></small></a><?php endforeach; ?><?php endif; ?></div></main></body></html>
