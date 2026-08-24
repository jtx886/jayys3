<?php
// 登录 AJAX
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Utils::ajaxResponse(false, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if (empty($username) || empty($password)) {
    Utils::ajaxResponse(false, '请填写用户名和密码');
}

if (Auth::login($username, $password)) {
    $redirect = $_SESSION['redirect_url'] ?? 'index.php';
    unset($_SESSION['redirect_url']);
    unset($_SESSION['login_message']);
    
    if (Auth::isAdmin()) {
        $redirect = 'admin/index.php';
    }
    
    Utils::ajaxResponse(true, '登录成功', ['redirect' => $redirect]);
} else {
    $msg = $_SESSION['ban_message'] ?? '用户名或密码错误';
    unset($_SESSION['ban_message']);
    Utils::ajaxResponse(false, $msg);
}
?>
