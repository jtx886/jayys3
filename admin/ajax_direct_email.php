<?php
// 自定义邮箱直接发送（支持自定义目标）
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if (!Auth::isAdmin()) {
    Utils::ajaxResponse(false, '无权限');
}

$to = $_POST['direct_to'] ?? '';
$subject = $_POST['subject'] ?? '';
$html = $_POST['html'] ?? '';

if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !$subject) {
    Utils::ajaxResponse(false, '参数错误');
}

$sent = Email::send($to, $subject, $html);
Utils::ajaxResponse($sent, $sent ? 'OK' : '发送失败');
?>
