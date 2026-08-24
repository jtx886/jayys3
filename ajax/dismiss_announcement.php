<?php
// 忽略公告 AJAX
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Utils::ajaxResponse(false, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);

if ($id <= 0) {
    Utils::ajaxResponse(false, '参数错误');
}

Utils::dismissAnnouncement($id);
Utils::ajaxResponse(true, '已记录');
?>
