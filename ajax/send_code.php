<?php
// 发送邮箱验证码 AJAX
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Utils::ajaxResponse(false, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$purpose = $input['purpose'] ?? 'register';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Utils::ajaxResponse(false, '邮箱格式不正确');
}

// 防止频繁发送
$db = Database::getInstance();
$recent = $db->fetchOne(
    "SELECT created_at FROM email_codes WHERE email = ? AND purpose = ? ORDER BY id DESC LIMIT 1",
    [$email, $purpose]
);

if ($recent && (time() - strtotime($recent['created_at'])) < 60) {
    Utils::ajaxResponse(false, '发送太频繁，请稍后再试');
}

$code = Email::generateCode();
Email::saveCode($email, $code, $purpose);

$html = Email::getVerifyEmailHtml($code);
$sent = Email::send($email, '【' . SITE_NAME . '】您的邮箱验证码', $html);

if ($sent) {
    Utils::ajaxResponse(true, '验证码已发送到您的邮箱，有效期5分钟');
} else {
    Utils::ajaxResponse(false, '验证码发送失败，请稍后重试');
}
?>
