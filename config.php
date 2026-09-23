<?php
const DB_HOST = 'localhost';
const DB_NAME = 'kasetwit_web69';
const DB_USER = 'kasetwit_web69';
const DB_PASS = 'web69_witcom';
const ADMIN_PIN = '9233';
const ADMIN_USERNAME = 'GuyandIce';
const ADMIN_PASSWORD = '1719';
const ADMIN_EMAIL = 'admin@workcheck.local';

const SUBJECTS = [
    'ภาษาไทย', 'สังคมศึกษา', 'เขียนโปรแกรม', 'ประวัติศาสตร์', 'ฟิสิกส์',
    'อังกฤษต่างชาติ', 'คณิตหลัก', 'อังกฤษไทย', 'การออกแบบเทคโนโลยี',
    'วิทยาศาสตร์กายภาพ', 'คณิตเสริม', 'ดนตรี-นาฏศิลป์', 'หุ่นยนต์',
    'พละ', 'สุขศึกษา', 'ชีววิทยา', 'การงานอาชีพ', 'ปัญญาประดิษฐ์',
];

function db()
{
    static $pdo;

    if (!$pdo instanceof PDO) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    return $pdo;
}

function e($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function start_session()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => $secure,
            ]);
        } else {
            session_set_cookie_params(0, '/', '', $secure, true);
        }
        session_start();
    }
}

function csrf_token()
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function check_csrf()
{
    start_session();
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง');
    }
}

function require_login()
{
    start_session();
    if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
        header('Location: Login.php');
        exit;
    }
}

function require_auth()
{
    start_session();
    enforce_suspension();
    if (empty($_SESSION['user_id'])) {
        $returnUrl = $_SERVER['REQUEST_URI'] ?? 'index.php';
        header('Location: Login.php?return=' . urlencode($returnUrl));
        exit;
    }

}

function enforce_suspension()
{
    if (empty($_SESSION['user_id']) || !empty($_SESSION['is_admin'])) {
        return;
    }
    $stmt = db()->prepare('SELECT suspended_until, suspended_permanent FROM `17_users` WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user && ((int) $user['suspended_permanent'] === 1 || ($user['suspended_until'] && strtotime($user['suspended_until']) > time()))) {
        $until = $user['suspended_permanent'] ? 'ถาวร' : date('d/m/Y H:i', strtotime($user['suspended_until']));
        $_SESSION = [];
        session_destroy();
        http_response_code(403);
        exit('บัญชีนี้ถูกระงับการใช้งานถึง ' . $until);
    }
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

function flash($type, $message)
{
    start_session();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function consume_flash()
{
    start_session();
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

function local_upload_path($file)
{
    if (!$file) {
        return null;
    }
    $name = basename($file);
    return __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $name;
}
