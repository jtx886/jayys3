<?php
// 注册 AJAX
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Utils::ajaxResponse(false, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';
$code = $input['code'] ?? '';

// 验证
if (empty($username) || mb_strlen($username) < 2 || mb_strlen($username) > 20) {
    Utils::ajaxResponse(false, '用户名长度应为2-20个字符');
}

if (!preg_match('/^[\x{4e00}-\x{9fa5}A-Za-z0-9_]+$/u', $username)) {
    Utils::ajaxResponse(false, '用户名只能包含中文、字母、数字和下划线');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Utils::ajaxResponse(false, '邮箱格式不正确');
}

if (strlen($password) < 6 || strlen($password) > 32) {
    Utils::ajaxResponse(false, '密码长度应为6-32个字符');
}

if ($password !== $confirmPassword) {
    Utils::ajaxResponse(false, '两次输入的密码不一致');
}

if (strlen($code) !== 6) {
    Utils::ajaxResponse(false, '验证码格式错误');
}

$result = Auth::register($username, $email, $password, $code);
if ($result['success']) {
    // 自动登录
    Auth::login($username, $password);
    Utils::ajaxResponse(true, '注册成功', ['redirect' => 'index.php']);
} else {
    Utils::ajaxResponse(false, $result['message']);
}
?>
