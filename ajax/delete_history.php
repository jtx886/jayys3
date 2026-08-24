<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if (!Auth::isLoggedIn()) {
    Utils::ajaxResponse(false, '请先登录');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Utils::ajaxResponse(false, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];
$db = Database::getInstance();

if (!empty($input['all'])) {
    $db->delete('watch_history', 'user_id = ?', [$userId]);
    Utils::ajaxResponse(true, '已清空全部观看历史');
}

$mediaId = intval($input['media_id'] ?? 0);
$mediaType = trim($input['media_type'] ?? '');
$season = intval($input['season'] ?? 0);
$episode = intval($input['episode'] ?? 0);

if ($mediaId <= 0) {
    Utils::ajaxResponse(false, '参数错误');
}

$db->delete(
    'watch_history',
    'user_id = ? AND media_id = ? AND media_type = ? AND season_number = ? AND episode_number = ?',
    [$userId, $mediaId, $mediaType, $season, $episode]
);

Utils::ajaxResponse(true, '已删除该记录');
?>
