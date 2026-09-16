<?php
require __DIR__ . '/config.php';
require_login();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$task = ['title' => '', 'subject' => SUBJECTS[0], 'description' => '', 'due_date' => '', 'answer_image' => ''];
if ($id) {
    $stmt = db()->prepare('SELECT * FROM `17_tasks` WHERE id = ?');
    $stmt->execute([$id]);
    $task = $stmt->fetch() ?: redirect('dashboard.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $title = trim($_POST['title'] ?? '');
    $subject = $_POST['subject'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $dueDate = $_POST['due_date'] ?: null;
    $answerImage = $task['answer_image'] ?? null;
    if ($title === '' || !in_array($subject, SUBJECTS, true)) {
        $error = 'กรุณากรอกชื่องานและเลือกรายวิชา';
    } elseif (!empty($_FILES['answer_image']['name'])) {
        $file = $_FILES['answer_image'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if ($file['error'] !== UPLOAD_ERR_OK || !isset($allowed[$mime]) || $file['size'] > 5 * 1024 * 1024) {
            $error = 'รูปเฉลยต้องเป็น JPG, PNG, GIF หรือ WEBP และมีขนาดไม่เกิน 5MB';
        } else {
            $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้');
            }
            $fileName = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
            if (!move_uploaded_file($file['tmp_name'], $uploadDir . DIRECTORY_SEPARATOR . $fileName)) {
                throw new RuntimeException('ไม่สามารถบันทึกรูปเฉลยได้');
            }
            $answerImage = $fileName;
        }
    }
    if ($error === '') {
        if ($id) {
            $stmt = db()->prepare('UPDATE `17_tasks` SET title=?, subject=?, description=?, due_date=?, answer_image=? WHERE id=?');
            $stmt->execute([$title, $subject, $description, $dueDate, $answerImage, $id]);
        } else {
            $stmt = db()->prepare('INSERT INTO `17_tasks` (title, subject, description, due_date, answer_image, created_by) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$title, $subject, $description, $dueDate, $answerImage, $_SESSION['user_id']]);
        }
        redirect('dashboard.php');
    }
    $task = ['title' => $title, 'subject' => $subject, 'description' => $description, 'due_date' => $dueDate ?? '', 'answer_image' => $answerImage];
}
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= $id ? 'แก้ไขงาน' : 'เพิ่มงาน' ?> | WorkCheck</title><link rel="stylesheet" href="style.css"></head>
<body class="auth-page"><main class="auth-card"><a class="brand" href="dashboard.php"><span class="brand-mark"></span>WorkCheck</a><h1><?= $id ? 'แก้ไขงาน' : 'เพิ่มงานใหม่' ?></h1>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<label>ชื่องาน<input name="title" required value="<?= e($task['title']) ?>"></label>
<label>รายวิชา<select name="subject" required><?php foreach (SUBJECTS as $subject): ?><option value="<?= e($subject) ?>" <?= $task['subject'] === $subject ? 'selected' : '' ?>><?= e($subject) ?></option><?php endforeach; ?></select></label>
<label>รายละเอียด (ไม่ใส่ก็ได้)<textarea name="description"><?= e($task['description']) ?></textarea></label>
<label>กำหนดส่ง<input class="date-input" type="date" name="due_date" value="<?= e($task['due_date'] ?? '') ?>"></label>
<label>รูปเฉลย (ไม่ใส่ก็ได้)<input type="file" name="answer_image" accept="image/jpeg,image/png,image/gif,image/webp"></label>
<button class="button full" type="submit">บันทึก</button></form><p class="center"><a href="dashboard.php">← กลับแดชบอร์ด</a></p></main></body></html>
