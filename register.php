<?php
require __DIR__ . '/config.php';
start_session();
$returnUrl = $_GET['return'] ?? 'index.php';
if ($returnUrl === '' || preg_match('/^(?:https?:)?\/\//i', $returnUrl)) {
    $returnUrl = 'index.php';
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        $error = 'กรุณากรอกข้อมูลให้ครบ และรหัสผ่านอย่างน้อย 6 ตัวอักษร';
    } else {
        try {
            $stmt = db()->prepare('INSERT INTO `17_users` (full_name, email, password_hash) VALUES (?, ?, ?)');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $_SESSION['user_id'] = (int) db()->lastInsertId();
            $_SESSION['user_name'] = $name;
            $_SESSION['is_admin'] = false;
            redirect($returnUrl);
        } catch (PDOException $exception) {
            $error = $exception->getCode() === '23000' ? 'อีเมลนี้ถูกใช้งานแล้ว' : 'ไม่สามารถสมัครสมาชิกได้';
        }
    }
}
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>สมัครสมาชิก | WorkCheck</title><link rel="stylesheet" href="style.css"></head>
<body class="auth-page"><main class="auth-card"><a class="brand" href="index.php"><span class="brand-mark"></span>WorkCheck</a><h1>สมัครสมาชิก</h1><p class="muted">สมัครเพื่อดูรูปเฉลย</p>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="return" value="<?= e($returnUrl) ?>"><label>ชื่อ<input name="full_name" required></label><label>อีเมล<input type="email" name="email" required></label><label>รหัสผ่าน<input type="password" name="password" minlength="6" required></label><button class="button full" type="submit">สมัครและเข้าสู่ระบบ</button></form><p class="center"><a href="Login.php">มีบัญชีแล้ว</a></p></main></body></html>
