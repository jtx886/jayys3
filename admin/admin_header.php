<?php
// 管理后台公共头部 - 防止直接访问
if (basename($_SERVER['PHP_SELF'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../config.php';
Auth::requireAdmin();

$theme = Utils::getThemeColor();
$pageName = $pageName ?? '仪表盘';
$db = Database::getInstance();

// 统计数据
$userCount = $db->fetchOne("SELECT COUNT(*) as c FROM users")['c'];
$feedbackCount = $db->fetchOne("SELECT COUNT(*) as c FROM feedbacks")['c'];
$pendingFb = $db->fetchOne("SELECT COUNT(*) as c FROM feedbacks WHERE status = 0")['c'];
$favCount = $db->fetchOne("SELECT COUNT(*) as c FROM favorites")['c'];
$historyCount = $db->fetchOne("SELECT COUNT(*) as c FROM watch_history")['c'];
$annCount = $db->fetchOne("SELECT COUNT(*) as c FROM announcements")['c'];
$sourceCount = $db->fetchOne("SELECT COUNT(*) as c FROM play_sources")['c'];
$bannedCount = $db->fetchOne("SELECT COUNT(*) as c FROM users WHERE is_banned = 1")['c'];

// 最新用户
$newUsers = $db->fetchAll("SELECT id, username, email, avatar, created_at FROM users ORDER BY id DESC LIMIT 5");
// 最新反馈
$newFeedbacks = $db->fetchAll("SELECT f.*, u.username FROM feedbacks f LEFT JOIN users u ON f.user_id = u.id ORDER BY f.id DESC LIMIT 5");
// 最新观看
$newHistories = $db->fetchAll("SELECT h.*, u.username FROM watch_history h LEFT JOIN users u ON h.user_id = u.id ORDER BY h.watched_at DESC LIMIT 5");
// 最新收藏
$newFavorites = $db->fetchAll("SELECT f.*, u.username FROM favorites f LEFT JOIN users u ON f.user_id = u.id ORDER BY f.id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.0">
    <style>
        :root {
            --primary: <?php echo $theme['primary']; ?>;
            --secondary: <?php echo $theme['secondary']; ?>;
            --gradient-primary: linear-gradient(135deg, <?php echo $theme['primary']; ?> 0%, <?php echo $theme['secondary']; ?> 100%);
        }
    </style>
</head>
<body>

<!-- Toast 容器 -->
<div class="toast-container" id="toastContainer"></div>

<div class="admin-layout">
    <!-- 侧边栏 -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="logo" style="font-size:18px;">
                <div class="logo-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"></path></svg>
                </div>
                <span class="logo-text">Jay影视</span>
            </a>
        </div>
        <nav class="sidebar-menu">
            <a href="index.php" class="sidebar-item <?php echo $pageName === '仪表盘' ? 'active' : ''; ?>">
                <span class="sidebar-icon">📊</span>仪表盘
            </a>
            <a href="users.php" class="sidebar-item <?php echo $pageName === '用户管理' ? 'active' : ''; ?>">
                <span class="sidebar-icon">👥</span>用户管理
            </a>
            <a href="history.php" class="sidebar-item <?php echo $pageName === '观看历史' ? 'active' : ''; ?>">
                <span class="sidebar-icon">⏱️</span>观看历史
            </a>
            <a href="favorites.php" class="sidebar-item <?php echo $pageName === '用户收藏' ? 'active' : ''; ?>">
                <span class="sidebar-icon">⭐</span>用户收藏
            </a>
            <a href="sources.php" class="sidebar-item <?php echo $pageName === '播放源管理' ? 'active' : ''; ?>">
                <span class="sidebar-icon">📡</span>播放源管理
            </a>
            <a href="announcements.php" class="sidebar-item <?php echo $pageName === '公告管理' ? 'active' : ''; ?>">
                <span class="sidebar-icon">📢</span>公告管理
            </a>
            <a href="feedbacks.php" class="sidebar-item <?php echo $pageName === '反馈管理' ? 'active' : ''; ?>">
                <span class="sidebar-icon">💬</span>反馈管理
                <?php if ($pendingFb > 0): ?><span class="badge badge-danger" style="margin-left:auto;font-size:10px;"><?php echo $pendingFb; ?></span><?php endif; ?>
            </a>
            <a href="email.php" class="sidebar-item <?php echo $pageName === '邮件通知' ? 'active' : ''; ?>">
                <span class="sidebar-icon">📧</span>邮件通知
            </a>
            <a href="theme.php" class="sidebar-item <?php echo $pageName === '主题设置' ? 'active' : ''; ?>">
                <span class="sidebar-icon">🎨</span>主题设置
            </a>
            <div style="height:20px;"></div>
            <a href="../index.php" class="sidebar-item">
                <span class="sidebar-icon">🏠</span>返回前台
            </a>
            <a href="../logout.php" class="sidebar-item" style="color:var(--danger);">
                <span class="sidebar-icon">🚪</span>退出登录
            </a>
        </nav>
    </aside>

    <!-- 主内容 -->
    <main class="admin-main">
        <header class="admin-topbar">
            <div style="display:flex;align-items:center;gap:14px;">
                <h1 class="admin-topbar-title">
                    <span style="color:var(--text-muted);font-size:15px;font-weight:400;">管理后台 / </span>
                    <?php echo $pageName; ?>
                </h1>
            </div>
            <div style="display:flex;align-items:center;gap:14px;">
                <div class="nav-user">
                    <div class="nav-avatar" style="background:linear-gradient(135deg,#ef4444,#dc2626);">🛡️</div>
                    <span class="nav-username">
                        <?php echo ADMIN_USERNAME; ?>
                        <span class="admin-badge">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                            开发者
                        </span>
                    </span>
                </div>
            </div>
        </header>

<script>
function showToast(message, type = 'info', duration = 3000) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast ' + type;
    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
    toast.innerHTML = '<span class="toast-icon">' + icons[type] + '</span><span>' + message + '</span>';
    container.appendChild(toast);
    setTimeout(() => { toast.style.animation = 'fadeIn 0.2s ease reverse'; setTimeout(() => toast.remove(), 200); }, duration);
}

async function adminApi(action, data = {}) {
    const res = await fetch('ajax_admin.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action, ...data})
    });
    return await res.json();
}
</script>
