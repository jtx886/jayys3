<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Utils::ajaxResponse(false, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$db = Database::getInstance();

switch ($action) {
    case 'create':
        if (!Auth::isLoggedIn()) Utils::ajaxResponse(false, '请先登录');
        
        $title = trim($input['title'] ?? '');
        $content = trim($input['content'] ?? '');
        
        if (mb_strlen($title) < 5 || mb_strlen($title) > 50) Utils::ajaxResponse(false, '标题长度5-50字');
        if (mb_strlen($content) < 10 || mb_strlen($content) > 2000) Utils::ajaxResponse(false, '内容长度10-2000字');
        
        $fid = $db->insert('feedbacks', [
            'user_id' => $_SESSION['user_id'],
            'title' => $title,
            'content' => $content,
            'status' => 0
        ]);
        
        Utils::ajaxResponse(true, '反馈提交成功，感谢您的宝贵意见！', ['id' => $fid]);
        break;

    case 'like':
        if (!Auth::isLoggedIn()) Utils::ajaxResponse(false, '请先登录');
        
        $fid = intval($input['feedback_id'] ?? $input['id'] ?? 0);
        if ($fid <= 0) Utils::ajaxResponse(false, '参数错误');
        $uid = $_SESSION['user_id'];
        
        $existing = $db->fetchOne("SELECT id FROM feedback_likes WHERE feedback_id = ? AND user_id = ?", [$fid, $uid]);
        if ($existing) {
            $db->delete('feedback_likes', 'id = ?', [$existing['id']]);
            $liked = false;
        } else {
            try { $db->insert('feedback_likes', ['feedback_id' => $fid, 'user_id' => $uid]); } catch (\Exception $e) {}
            $liked = true;
        }
        $count = $db->fetchOne("SELECT COUNT(*) as c FROM feedback_likes WHERE feedback_id = ?", [$fid])['c'];
        Utils::ajaxResponse(true, 'OK', ['liked' => $liked, 'like_count' => $count]);
        break;

    case 'reply':
        if (!Auth::isLoggedIn()) Utils::ajaxResponse(false, '请先登录');
        
        $fid = intval($input['feedback_id'] ?? $input['id'] ?? 0);
        $content = trim($input['content'] ?? '');
        if ($fid <= 0) Utils::ajaxResponse(false, '参数错误');
        if (!$content || mb_strlen($content) > 500) Utils::ajaxResponse(false, '回复内容长度1-500字');
        
        $isAdmin = Auth::isAdmin() ? 1 : 0;
        $userId = $isAdmin ? 0 : $_SESSION['user_id'];  // 管理员用0标识
        if ($isAdmin) $userId = 0; else $userId = $_SESSION['user_id'];
        
        $db->insert('feedback_replies', [
            'feedback_id' => $fid,
            'user_id' => $userId,
            'content' => $content,
            'is_admin' => $isAdmin
        ]);
        
        // 如果是管理员回复，更新反馈状态
        if ($isAdmin) {
            $db->update('feedbacks', ['status' => 1], 'id = ?', [$fid]);
        }
        
        Utils::ajaxResponse(true, '回复成功');
        break;

    default:
        Utils::ajaxResponse(false, '未知操作');
}
?>
