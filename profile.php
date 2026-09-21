<?php
require __DIR__ . '/config.php';
require_auth();

$stmt = db()->prepare('SELECT id, full_name, email FROM `17_users` WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch() ?: redirect('logout.php');
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'กรุณากรอกชื่อและอีเมลให้ถูกต้อง';
    } elseif ($password !== '' && strlen($password) < 6) {
        $error = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร';
    } else {
        $check = db()->prepare('SELECT id FROM `17_users` WHERE (full_name = ? OR email = ?) AND id <> ?');
        $check->execute([$name, $email, $_SESSION['user_id']]);
        if ($check->fetch()) {
            $error = 'ชื่อหรืออีเมลนี้ถูกใช้งานแล้ว';
        } else {
            if ($password !== '') {
                $update = db()->prepare('UPDATE `17_users` SET full_name = ?, email = ?, password_hash = ? WHERE id = ?');
                $update->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $_SESSION['user_id']]);
            } else {
                $update = db()->prepare('UPDATE `17_users` SET full_name = ?, email = ? WHERE id = ?');
                $update->execute([$name, $email, $_SESSION['user_id']]);
            }
            $_SESSION['user_name'] = $name;
            $user['full_name'] = $name;
            $user['email'] = $email;
            $success = 'บันทึกโปรไฟล์แล้ว';
        }
    }
}
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>โปรไฟล์ | WorkCheck</title><link rel="stylesheet" href="style.css"></head>
<body class="auth-page"><main class="auth-card"><a class="brand" href="index.php"><span class="brand-mark"></span>WorkCheck</a><h1>โปรไฟล์</h1><p class="muted">แก้ไขชื่อ อีเมล หรือรหัสผ่าน</p>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?><?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label>ชื่อผู้ใช้<input name="full_name" required value="<?= e($user['full_name']) ?>"></label><label>อีเมล<input type="email" name="email" required value="<?= e($user['email']) ?>"></label><label>รหัสผ่านใหม่ (เว้นว่างถ้าไม่เปลี่ยน)<input type="password" name="password" minlength="6"></label><button class="button full" type="submit">บันทึกการแก้ไข</button></form><p class="center"><a href="index.php">← กลับหน้าหลัก</a></p></main></body></html>
