<?php
require __DIR__ . '/config.php';
start_session();

$adminMode = isset($_GET['admin']) || !empty($_SESSION['admin_pin_verified']);
$returnUrl = $_GET['return'] ?? $_POST['return'] ?? 'index.php';
if ($returnUrl === '' || preg_match('/^(?:https?:)?\/\//i', $returnUrl)) {
    $returnUrl = 'index.php';
}
if (!empty($_SESSION['user_id'])) {
    redirect(!empty($_SESSION['is_admin']) ? 'dashboard.php' : $returnUrl);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if ($adminMode && empty($_SESSION['admin_pin_verified'])) {
        if (hash_equals(ADMIN_PIN, trim($_POST['pin'] ?? ''))) {
            $_SESSION['admin_pin_verified'] = true;
            redirect('Login.php?admin=1');
        }
        $error = 'PIN ไม่ถูกต้อง';
    } elseif ($adminMode) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (hash_equals(ADMIN_USERNAME, $username) && hash_equals(ADMIN_PASSWORD, $password)) {
            $stmt = db()->prepare('SELECT id FROM `17_users` WHERE email = ?');
            $stmt->execute([ADMIN_EMAIL]);
            $userId = $stmt->fetchColumn();
            if (!$userId) {
                $stmt = db()->prepare('INSERT INTO `17_users` (full_name, email, password_hash) VALUES (?, ?, ?)');
                $stmt->execute([ADMIN_USERNAME, ADMIN_EMAIL, password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT)]);
                $userId = db()->lastInsertId();
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $userId;
            $_SESSION['user_name'] = ADMIN_USERNAME;
            $_SESSION['is_admin'] = true;
            unset($_SESSION['admin_pin_verified']);
            redirect('dashboard.php');
        }
        $error = 'ชื่อหรือรหัสผ่านไม่ถูกต้อง';
    } else {
        $identity = trim($_POST['identity'] ?? '');
        $password = $_POST['password'] ?? '';
        $stmt = db()->prepare('SELECT id, full_name, password_hash, suspended_until, suspended_permanent FROM `17_users` WHERE email = ? OR full_name = ?');
        $stmt->execute([$identity, $identity]);
        $user = $stmt->fetch();
        $suspended = $user && ((int) $user['suspended_permanent'] === 1 || ($user['suspended_until'] && strtotime($user['suspended_until']) > time()));
        if ($user && $suspended) {
            $error = 'บัญชีนี้ถูกระงับการใช้งาน';
        } elseif ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['is_admin'] = false;
            redirect($returnUrl);
        }
        $error = 'ชื่อผู้ใช้/อีเมลหรือรหัสผ่านไม่ถูกต้อง';
    }
}
?>
<!doctype html>
<html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= $adminMode ? 'ผู้ดูแล' : 'เข้าสู่ระบบ' ?> | WorkCheck</title><link rel="stylesheet" href="style.css"></head>
<body class="auth-page"><main class="auth-card">
    <a class="brand" href="index.php"><span class="brand-mark"></span>WorkCheck</a>
    <h1><?= $adminMode && empty($_SESSION['admin_pin_verified']) ? 'PIN ผู้ดูแล' : 'เข้าสู่ระบบ' ?></h1>
    <p class="muted"><?= $adminMode ? 'พื้นที่สำหรับผู้ดูแลระบบ' : 'เข้าสู่ระบบเพื่อดูรูปเฉลย' ?></p>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="return" value="<?= e($returnUrl) ?>">
    <?php if ($adminMode && empty($_SESSION['admin_pin_verified'])): ?><label>PIN 4 หลัก<input name="pin" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" required autofocus></label>
    <?php elseif ($adminMode): ?><label>ชื่อผู้ใช้<input name="username" required autofocus></label><label>รหัสผ่าน<input type="password" name="password" required></label>
    <?php else: ?><label>ชื่อผู้ใช้หรืออีเมล<input name="identity" required autofocus></label><label>รหัสผ่าน<input type="password" name="password" required></label><?php endif; ?>
    <button class="button full" type="submit">เข้าสู่ระบบ</button></form>
    <?php if (!$adminMode): ?><p class="center muted">ยังไม่มีบัญชี? <a href="register.php?return=<?= urlencode($returnUrl) ?>">สมัครสมาชิก</a></p><p class="center"><a href="Login.php?admin=1">เข้าสู่ระบบผู้ดูแล</a></p><?php endif; ?>
    <p class="center"><a href="index.php">กลับหน้าหลัก</a></p>
</main></body></html>
