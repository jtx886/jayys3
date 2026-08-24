<?php
// 获取主题颜色
$theme = Utils::getThemeColor();
$activeNav = $activeNav ?? 'home';
$currentUser = Auth::getCurrentUser();

// 获取最新公告（仅首页显示）
$showAnnouncement = ($activeNav === 'home');
$announcement = null;
$announcementDismissed = true;
if ($showAnnouncement) {
    $announcement = Utils::getActiveAnnouncement();
    if ($announcement) {
        $announcementDismissed = Utils::isAnnouncementDismissed($announcement['id']);
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="<?php echo $theme['primary']; ?>">
    <title><?php echo $pageTitle ?? SITE_NAME; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0">
    <style>
        :root {
            --primary: <?php echo $theme['primary']; ?>;
            --secondary: <?php echo $theme['secondary']; ?>;
            --gradient-primary: linear-gradient(135deg, <?php echo $theme['primary']; ?> 0%, <?php echo $theme['secondary']; ?> 100%);
        }
    </style>
</head>
<body>

<!-- 公告弹窗 -->
<?php if ($announcement && !$announcementDismissed): ?>
<div class="modal-overlay announcement-modal" id="announcementModal">
    <div class="modal">
        <div class="modal-header">
            <div style="display:flex;align-items:center;gap:14px;">
                <div class="announcement-icon">📢</div>
                <div>
                    <div class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                    <div class="announcement-meta">
                        <span>🕐 <?php echo date('Y-m-d H:i', strtotime($announcement['created_at'])); ?></span>
                    </div>
                </div>
            </div>
            <button class="modal-close" onclick="closeAnnouncement()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="announcement-content"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></div>
            <label class="dismiss-checkbox">
                <input type="checkbox" id="dismissAnnouncement">
                <span>不再显示此公告（如有新公告仍会提示）</span>
            </label>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary btn-block" onclick="confirmAnnouncement()">我知道了</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- 导航栏 -->
<nav class="navbar">
    <div class="container">
        <div class="navbar-inner">
            <a href="index.php" class="logo">
                <div class="logo-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
                </div>
                <span class="logo-text">Jay影视</span>
            </a>

            <ul class="nav-links">
                <li><a class="nav-link <?php echo $activeNav === 'home' ? 'active' : ''; ?>" href="index.php">首页</a></li>
                <li><a class="nav-link <?php echo $activeNav === 'movie' ? 'active' : ''; ?>" href="category.php?type=movie">电影</a></li>
                <li><a class="nav-link <?php echo $activeNav === 'tv' ? 'active' : ''; ?>" href="category.php?type=tv">电视剧</a></li>
                <li><a class="nav-link <?php echo $activeNav === 'anime' ? 'active' : ''; ?>" href="category.php?type=anime">动漫</a></li>
                <li><a class="nav-link <?php echo $activeNav === 'variety' ? 'active' : ''; ?>" href="category.php?type=variety">综艺</a></li>
                <li><a class="nav-link <?php echo $activeNav === 'feedback' ? 'active' : ''; ?>" href="feedback.php">反馈</a></li>
            </ul>

            <div class="nav-right">
                <form class="nav-search" action="search.php" method="get">
                    <input type="text" name="q" placeholder="搜索电影、电视剧、动漫..." required>
                    <button type="submit" class="search-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
                </form>

                <?php if (Auth::isLoggedIn()): ?>
                    <a href="profile.php" class="nav-user">
                        <div class="nav-avatar">
                            <?php if (!empty($currentUser['avatar'])): ?>
                                <img src="<?php echo htmlspecialchars($currentUser['avatar']); ?>" alt="">
                            <?php else: ?>
                                <?php echo mb_substr($currentUser['username'], 0, 1); ?>
                            <?php endif; ?>
                        </div>
                        <span class="nav-username">
                            <?php echo htmlspecialchars($currentUser['username']); ?>
                            <?php if (Auth::isAdmin()): ?>
                                <span class="admin-badge" title="开发者">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                                    开发者
                                </span>
                            <?php endif; ?>
                        </span>
                    </a>
                    <?php if (Auth::isAdmin()): ?>
                        <a href="admin/index.php" class="nav-icon-btn" title="管理后台">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"></path></svg>
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline btn-sm">登录</a>
                    <a href="login.php?tab=register" class="btn btn-primary btn-sm">注册</a>
                <?php endif; ?>

                <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- Toast 容器 -->
<div class="toast-container" id="toastContainer"></div>

<script>
// Toast 提示
function showToast(message, type = 'info', duration = 3000) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast ' + type;
    
    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    
    toast.innerHTML = '<span class="toast-icon">' + icons[type] + '</span><span>' + message + '</span>';
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'fadeIn 0.2s ease reverse';
        setTimeout(() => toast.remove(), 200);
    }, duration);
}

// 公告弹窗
function closeAnnouncement() {
    document.getElementById('announcementModal').style.display = 'none';
}

function confirmAnnouncement() {
    const dismissed = document.getElementById('dismissAnnouncement').checked;
    const announcementId = <?php echo $announcement ? $announcement['id'] : 0; ?>;
    
    if (dismissed) {
        fetch('ajax/dismiss_announcement.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: announcementId})
        });
    }
    closeAnnouncement();
}

// 简易移动端菜单切换
function toggleMobileMenu() {
    // 可扩展为侧边菜单
    showToast('请使用底部导航栏', 'info');
}

// 通用fetch封装
async function apiPost(url, data = {}) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    });
    return await res.json();
}
</script>
