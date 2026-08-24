<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if (!Auth::isAdmin()) {
    Utils::ajaxResponse(false, '无权限');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Utils::ajaxResponse(false, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$db = Database::getInstance();

switch ($action) {
    // ========== 用户管理 ==========
    case 'ban_user':
        $uid = intval($input['user_id'] ?? 0);
        $reason = trim($input['reason'] ?? '违反平台规则');
        $days = intval($input['days'] ?? 7);
        $customEnd = trim($input['custom_end'] ?? '');
        
        if ($uid <= 0) Utils::ajaxResponse(false, '参数错误');
        
        $startTime = date('Y-m-d H:i:s');
        if ($customEnd) {
            $endTime = $customEnd . ' 23:59:59';
        } elseif ($days === 0) {
            $endTime = date('Y-m-d H:i:s', strtotime('+10 years')); // 永久
        } else {
            $endTime = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        }
        
        $db->update('users', [
            'is_banned' => 1,
            'ban_reason' => $reason,
            'ban_start_time' => $startTime,
            'ban_end_time' => $endTime
        ], 'id = ?', [$uid]);
        
        // 发送邮件通知
        $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$uid]);
        if ($user) {
            $banEndText = ($days === 0 && !$customEnd) ? '永久封禁' : $endTime;
            $html = Email::getBanEmailHtml($user['username'], $reason, $startTime, $banEndText);
            Email::send($user['email'], '【' . SITE_NAME . '】账号封禁通知', $html);
        }
        
        Utils::ajaxResponse(true, '封禁成功，已通知用户', [
            'start' => $startTime,
            'end' => $endTime
        ]);
        break;

    case 'unban_user':
        $uid = intval($input['user_id'] ?? 0);
        if ($uid <= 0) Utils::ajaxResponse(false, '参数错误');
        
        $db->update('users', [
            'is_banned' => 0,
            'ban_reason' => '',
            'ban_start_time' => null,
            'ban_end_time' => null
        ], 'id = ?', [$uid]);
        
        Utils::ajaxResponse(true, '已解除封禁');
        break;

    case 'delete_user':
        $uid = intval($input['user_id'] ?? 0);
        if ($uid <= 0) Utils::ajaxResponse(false, '参数错误');
        
        $db->delete('users', 'id = ?', [$uid]);
        $db->delete('watch_history', 'user_id = ?', [$uid]);
        $db->delete('favorites', 'user_id = ?', [$uid]);
        $db->delete('announcement_dismissed', 'user_id = ?', [$uid]);
        // 保留反馈记录
        
        Utils::ajaxResponse(true, '已删除用户及其相关数据');
        break;

    case 'send_email_user':
        $uid = intval($input['user_id'] ?? 0);
        $subject = trim($input['subject'] ?? '');
        $content = trim($input['content'] ?? '');
        
        if (!$subject || !$content) Utils::ajaxResponse(false, '请填写标题和内容');
        
        $html = '<div style="max-width:600px;margin:0 auto;padding:20px;font-family:sans-serif;color:#333;">
            <div style="padding:20px;background:linear-gradient(135deg,#7c3aed,#a855f7);color:#fff;border-radius:12px 12px 0 0;">
                <h2 style="margin:0;">📧 ' . SITE_NAME . ' 系统通知</h2>
            </div>
            <div style="padding:24px;background:#fff;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 12px 12px;line-height:1.8;">
                ' . nl2br(htmlspecialchars($content)) . '
                <div style="margin-top:24px;padding-top:16px;border-top:1px solid #e5e7eb;color:#6b7280;font-size:13px;">
                    此邮件由 ' . SITE_NAME . ' 发送 - ' . date('Y-m-d H:i:s') . '
                </div>
            </div>
        </div>';
        
        if ($uid > 0) {
            $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$uid]);
            if (!$user) Utils::ajaxResponse(false, '用户不存在');
            $sent = Email::send($user['email'], $subject, $html);
            Utils::ajaxResponse($sent, $sent ? '邮件发送成功' : '邮件发送失败');
        } else {
            // 发送给所有用户
            $users = $db->fetchAll("SELECT email FROM users");
            $count = 0;
            foreach ($users as $u) {
                if (Email::send($u['email'], $subject, $html)) $count++;
            }
            Utils::ajaxResponse(true, "已成功发送 {$count}/" . count($users) . " 封邮件");
        }
        break;

    // ========== 播放源管理 ==========
    case 'save_source':
        $id = intval($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $apiUrl = trim($input['api_url'] ?? '');
        $parserUrl = trim($input['parser_url'] ?? VIDEO_PARSER);
        $sortOrder = intval($input['sort_order'] ?? 0);
        $status = intval($input['status'] ?? 1);
        $isDefault = intval($input['is_default'] ?? 0);
        
        if (!$name || !$apiUrl) Utils::ajaxResponse(false, '请填写名称和API地址');
        
        if ($isDefault) {
            $db->update('play_sources', ['is_default' => 0], '1=1');
        }
        
        $data = [
            'name' => $name,
            'api_url' => $apiUrl,
            'parser_url' => $parserUrl,
            'sort_order' => $sortOrder,
            'status' => $status,
            'is_default' => $isDefault
        ];
        
        if ($id > 0) {
            $db->update('play_sources', $data, 'id = ?', [$id]);
            Utils::ajaxResponse(true, '更新成功');
        } else {
            $db->insert('play_sources', $data);
            Utils::ajaxResponse(true, '添加成功');
        }
        break;

    case 'delete_source':
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) Utils::ajaxResponse(false, '参数错误');
        $db->delete('play_sources', 'id = ?', [$id]);
        Utils::ajaxResponse(true, '已删除');
        break;

    case 'set_default_source':
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) Utils::ajaxResponse(false, '参数错误');
        $db->update('play_sources', ['is_default' => 0], '1=1');
        $db->update('play_sources', ['is_default' => 1], 'id = ?', [$id]);
        Utils::ajaxResponse(true, '已设为默认');
        break;

    // ========== 公告管理 ==========
    case 'save_announcement':
        $id = intval($input['id'] ?? 0);
        $title = trim($input['title'] ?? '');
        $content = trim($input['content'] ?? '');
        
        if (!$title || !$content) Utils::ajaxResponse(false, '请填写标题和内容');
        
        if ($id > 0) {
            $db->update('announcements', [
                'title' => $title,
                'content' => $content,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);
            Utils::ajaxResponse(true, '更新成功');
        } else {
            $db->insert('announcements', [
                'title' => $title,
                'content' => $content,
                'created_by' => 0
            ]);
            Utils::ajaxResponse(true, '发布成功');
        }
        break;

    case 'delete_announcement':
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) Utils::ajaxResponse(false, '参数错误');
        $db->delete('announcements', 'id = ?', [$id]);
        $db->delete('announcement_dismissed', 'announcement_id = ?', [$id]);
        Utils::ajaxResponse(true, '已删除');
        break;

    // ========== 反馈管理 ==========
    case 'reply_feedback':
        $fid = intval($input['feedback_id'] ?? $input['id'] ?? 0);
        $content = trim($input['content'] ?? '');
        
        if ($fid <= 0 || !$content) Utils::ajaxResponse(false, '参数错误');
        
        $db->insert('feedback_replies', [
            'feedback_id' => $fid,
            'user_id' => 0,
            'content' => $content,
            'is_admin' => 1
        ]);
        
        $db->update('feedbacks', ['status' => 1], 'id = ?', [$fid]);
        Utils::ajaxResponse(true, '回复成功');
        break;

    case 'delete_feedback':
        $fid = intval($input['feedback_id'] ?? $input['id'] ?? 0);
        if ($fid <= 0) Utils::ajaxResponse(false, '参数错误');
        $db->delete('feedbacks', 'id = ?', [$fid]);
        $db->delete('feedback_replies', 'feedback_id = ?', [$fid]);
        $db->delete('feedback_likes', 'feedback_id = ?', [$fid]);
        Utils::ajaxResponse(true, '已删除反馈及相关内容');
        break;

    // ========== 主题设置 ==========
    case 'save_theme':
        $primary = trim($input['primary'] ?? '#7c3aed');
        $secondary = trim($input['secondary'] ?? '#a855f7');
        
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $primary) || !preg_match('/^#[0-9a-fA-F]{6}$/', $secondary)) {
            Utils::ajaxResponse(false, '颜色格式错误');
        }
        
        $db->query("REPLACE INTO site_settings (setting_key, setting_value) VALUES ('theme_color', ?)", [$primary]);
        $db->query("REPLACE INTO site_settings (setting_key, setting_value) VALUES ('theme_color_secondary', ?)", [$secondary]);
        
        Utils::ajaxResponse(true, '主题保存成功');
        break;

    default:
        Utils::ajaxResponse(false, '未知操作: ' . htmlspecialchars($action));
}
?>
