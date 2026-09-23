<?php
require __DIR__ . '/config.php';
require_login();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$stmt = db()->prepare('SELECT id, full_name, email FROM `17_users` WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch() ?: redirect('dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'กรุณากรอกชื่อและอีเมลให้ถูกต้อง';
    } elseif ($password !== '' && strlen($password) < 6) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    } else {
        $check = db()->prepare('SELECT id FROM `17_users` WHERE (full_name = ? OR email = ?) AND id <> ?');
        $check->execute([$name, $email, $id]);
        if ($check->fetch()) {
            $error = 'ชื่อหรืออีเมลนี้ถูกใช้งานแล้ว';
        } else {
            try {
                if ($password !== '') {
                    $update = db()->prepare('UPDATE `17_users` SET full_name = ?, email = ?, password_hash = ? WHERE id = ?');
                    $update->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $id]);
                } else {
                    $update = db()->prepare('UPDATE `17_users` SET full_name = ?, email = ? WHERE id = ?');
                    $update->execute([$name, $email, $id]);
                }
                flash('success', 'แก้ไขบัญชีเรียบร้อยแล้ว');
                redirect('dashboard.php');
            } catch (PDOException $exception) {
                $error = 'แก้ไขบัญชีไม่สำเร็จ: ' . $exception->getMessage();
            }
        }
    }
}
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>แก้ไขบัญชี | WorkCheck</title><link rel="stylesheet" href="style.css"></head>
<body class="auth-page"><main class="auth-card"><a class="brand" href="dashboard.php"><span class="brand-mark"></span>WorkCheck</a><h1>แก้ไขบัญชี</h1><p class="muted">ผู้ดูแลสามารถแก้ไขข้อมูลผู้ใช้ได้</p><?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label>ชื่อผู้ใช้<input name="full_name" required value="<?= e($user['full_name']) ?>"></label><label>อีเมล<input type="email" name="email" required value="<?= e($user['email']) ?>"></label><label>รหัสผ่านใหม่ (ไม่เปลี่ยนเว้นว่าง)<input type="password" name="password" minlength="6"></label><button class="button full" type="submit">บันทึก</button></form><p class="center"><a href="dashboard.php">← กลับแดชบอร์ด</a></p></main></body></html>
