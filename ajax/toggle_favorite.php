<?php
// 收藏切换 AJAX
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if (!Auth::isLoggedIn()) {
    Utils::ajaxResponse(false, '请先登录');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Utils::ajaxResponse(false, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);
$mediaId = intval($input['media_id'] ?? 0);
$mediaType = trim($input['media_type'] ?? 'movie');
$title = trim($input['title'] ?? '');
$poster = trim($input['poster'] ?? '');

if ($mediaId <= 0 || empty($title)) {
    Utils::ajaxResponse(false, '参数错误');
}

$result = Utils::toggleFavorite($mediaId, $mediaType, $title, $poster);
Utils::ajaxResponse($result['success'], $result['message'], [
    'favorited' => $result['favorited'] ?? false
]);
?>
