<?php
require __DIR__ . '/config.php';
require_login();
$flash = consume_flash();
$stmt = db()->query('SELECT * FROM `17_tasks` ORDER BY subject, COALESCE(due_date, "9999-12-31"), created_at DESC');
$tasks = $stmt->fetchAll();
$users = db()->query('SELECT id, full_name, email, suspended_until, suspended_permanent FROM `17_users` ORDER BY full_name')->fetchAll();
$grouped = array_fill_keys(SUBJECTS, []);
foreach ($tasks as $task) {
    if (isset($grouped[$task['subject']])) {
        $grouped[$task['subject']][] = $task;
    }
}
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>แดชบอร์ด | WorkCheck</title><link rel="stylesheet" href="style.css"></head>
<body><header class="topbar"><a class="brand" href="index.php"><span class="brand-mark"></span>WorkCheck</a><nav><a href="index.php">หน้าหลัก</a><a href="task_form.php">เพิ่มงาน</a><span class="muted">สวัสดี <?= e($_SESSION['user_name']) ?></span><a href="logout.php">ออกจากระบบ</a></nav></header>
<main class="container section"><div class="section-heading"><div><p class="eyebrow">พื้นที่ผู้ดูแล</p><h2>จัดการงานรายวิชา</h2></div><a class="button" href="task_form.php">+ เพิ่มงาน</a></div>
<?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<?php if (!$tasks): ?><div class="empty">ยังไม่มีงาน <a href="task_form.php">เพิ่มรายการแรก</a></div><?php else: ?>
<?php foreach (SUBJECTS as $subject): if (!$grouped[$subject]) { continue; } ?><section class="panel subject-table"><h3><?= e($subject) ?></h3><div class="table-wrap"><table><thead><tr><th>งาน</th><th>กำหนดส่ง</th><th>เฉลย</th><th></th></tr></thead><tbody>
<?php foreach ($grouped[$subject] as $task): ?><tr><td><strong><?= e($task['title']) ?></strong><?php if ($task['description'] !== ''): ?><br><small class="muted"><?= e($task['description']) ?></small><?php endif; ?></td><td><?= $task['due_date'] ? e(date('d/m/Y', strtotime($task['due_date']))) : '-' ?></td><td><?= $task['answer_image'] ? 'มีรูปเฉลย' : 'ไม่มีรูปเฉลย' ?></td><td class="actions"><a href="task_form.php?id=<?= (int) $task['id'] ?>">แก้ไข</a><form method="post" action="task_delete.php" onsubmit="return confirm('ยืนยันการลบงานนี้?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $task['id'] ?>"><button class="link-button" type="submit">ลบ</button></form></td></tr><?php endforeach; ?>
</tbody></table></div></section><?php endforeach; endif; ?>
<section class="container section"><div class="panel subject-table"><h3>จัดการบัญชีผู้ใช้</h3><div class="table-wrap"><table><thead><tr><th>ผู้ใช้</th><th>สถานะ</th><th>ระงับบัญชี</th></tr></thead><tbody><?php foreach ($users as $user): ?><tr><td><?= e($user['full_name']) ?><br><small class="muted"><?= e($user['email']) ?></small></td><td><?php if ($user['suspended_permanent']): ?>ระงับถาวร<?php elseif ($user['suspended_until'] && strtotime($user['suspended_until']) > time()): ?>ถึง <?= e($user['suspended_until']) ?><?php else: ?>ปกติ<?php endif; ?></td><td><?php if ((int) $user['id'] !== (int) $_SESSION['user_id']): ?><div class="moderate-actions"><a href="user_edit.php?id=<?= (int) $user['id'] ?>">แก้ไข</a><form method="post" action="moderate_user.php" class="moderate-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>"><select name="days"><option value="7">7 วัน</option><option value="30">30 วัน</option><option value="permanent">ถาวร</option><option value="unsuspend">ยกเลิกการระงับ</option></select><button class="button small" type="submit">บันทึก</button></form><form method="post" action="user_delete.php" onsubmit="return confirm('ยืนยันลบบัญชีนี้และข้อมูลที่เกี่ยวข้อง?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>"><button class="danger-button" type="submit">ลบบัญชี</button></form></div><?php else: ?>บัญชีผู้ดูแล<?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></div></section></body></html>
