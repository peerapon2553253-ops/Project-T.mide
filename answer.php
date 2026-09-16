<?php
require __DIR__ . '/config.php';
require_auth();
$file = basename($_GET['file'] ?? '');
$path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $file;
if ($file === '' || !is_file($path)) {
    http_response_code(404);
    exit('ไม่พบรูปเฉลย');
}
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mime, $allowed, true)) {
    http_response_code(415);
    exit('ชนิดไฟล์ไม่รองรับ');
}
header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
if (isset($_GET['download'])) {
    header('Content-Disposition: attachment; filename="answer-' . pathinfo($file, PATHINFO_BASENAME) . '"');
}
readfile($path);
