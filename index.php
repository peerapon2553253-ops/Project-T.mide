<?php
require __DIR__ . '/config.php';
start_session();
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
$sql = 'SELECT title, subject, description, due_date, answer_image FROM `17_tasks`' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY COALESCE(due_date, "9999-12-31"), created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();
$grouped = array_fill_keys(SUBJECTS, []);
foreach ($tasks as $task) {
    $grouped[$task['subject']][] = $task;
}
?>
<!doctype html>
<html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>รายวิชา | WorkCheck</title><link rel="stylesheet" href="style.css"></head>
<body><header class="topbar"><a class="brand" href="index.php"><span class="brand-mark"></span>WorkCheck</a><nav><a href="#tasks">รายวิชา</a><?php if (!empty($_SESSION['user_id'])): ?><?php if (!empty($_SESSION['is_admin'])): ?><a href="dashboard.php">แดชบอร์ด</a><?php endif; ?><a href="logout.php">ออกจากระบบ</a><?php else: ?><a href="Login.php?return=<?= urlencode($_SERVER['REQUEST_URI'] ?? 'index.php') ?>">เข้าสู่ระบบ</a><?php endif; ?><a class="button small" href="Login.php?admin=1">ผู้ดูแล</a></nav></header>
<main class="container"><section class="hero"><p class="eyebrow">ระบบตรวจสอบงานรายวิชา</p><h1>ค้นหางาน<br><em>แยกตามรายวิชา</em></h1><p class="lead">เลือกวิชาเพื่อดูงานและกำหนดส่ง รูปเฉลยดูได้เมื่อเข้าสู่ระบบ</p></section>
<section id="tasks" class="section"><div class="section-heading"><div><p class="eyebrow">ทั้งหมด 18 วิชา</p><h2>รายวิชา</h2></div></div>
<form class="search-form" method="get"><input type="search" name="q" placeholder="ค้นหาชื่องานหรือรายละเอียด..." value="<?= e($search) ?>"><button class="button small" type="submit">ค้นหา</button></form>
<div class="subject-links"><?php foreach (SUBJECTS as $subject): ?><a class="<?= $subjectFilter === $subject ? 'active' : '' ?>" href="?subject=<?= urlencode($subject) ?>#subject-<?= md5($subject) ?>"><?= e($subject) ?></a><?php endforeach; ?></div>
<?php $visibleSubjects = $subjectFilter && in_array($subjectFilter, SUBJECTS, true) ? [$subjectFilter] : SUBJECTS; foreach ($visibleSubjects as $subject): ?><section class="subject-section" id="subject-<?= md5($subject) ?>"><div class="subject-heading"><h3><?= e($subject) ?></h3><span class="count"><?= count($grouped[$subject]) ?> งาน</span></div>
<?php if (!$grouped[$subject]): ?><p class="muted subject-empty">ยังไม่มีงานในรายวิชานี้</p><?php else: ?><div class="task-grid"><?php foreach ($grouped[$subject] as $task): ?><article class="task-card"><h3><?= e($task['title']) ?></h3><?php if ($task['description'] !== ''): ?><p><?= nl2br(e($task['description'])) ?></p><?php endif; ?><?php if ($task['due_date']): ?><small>กำหนดส่ง <?= e(date('d/m/Y', strtotime($task['due_date']))) ?></small><?php endif; ?><?php if ($task['answer_image']): ?><?php if (!empty($_SESSION['user_id'])): ?><a class="button small answer-button" href="answer.php?file=<?= urlencode($task['answer_image']) ?>" target="_blank" rel="noopener">กดที่นี่เพื่อดูเฉลย</a><a class="button small download-button" href="answer.php?file=<?= urlencode($task['answer_image']) ?>&download=1">ดาวน์โหลดรูป</a><?php else: ?><p class="answer-locked"><a href="Login.php?return=<?= urlencode($_SERVER['REQUEST_URI'] ?? 'index.php') ?>">เข้าสู่ระบบเพื่อดูเฉลย</a></p><?php endif; ?><?php endif; ?></article><?php endforeach; ?></div><?php endif; ?></section><?php endforeach; ?>
</section></main><footer class="container footer">© 2026 WorkCheck · ระบบบันทึกงานรายวิชา</footer></body></html>
