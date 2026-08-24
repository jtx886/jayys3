<?php
require_once __DIR__ . '/config.php';

// 检查登录
if (!Auth::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = '我的';
$activeNav = 'profile';
$currentUser = Auth::getCurrentUser();
$db = Database::getInstance();

$tab = $_GET['tab'] ?? 'favorites';

// 统计数据
$favCount = $db->fetchOne("SELECT COUNT(*) as c FROM favorites WHERE user_id = ?", [$currentUser['id']])['c'];
$historyCount = $db->fetchOne("SELECT COUNT(*) as c FROM watch_history WHERE user_id = ?", [$currentUser['id']])['c'];
$totalSeconds = $db->fetchOne("SELECT COALESCE(SUM(watch_seconds),0) as s FROM watch_history WHERE user_id = ?", [$currentUser['id']])['s'];
$totalHours = round($totalSeconds / 3600, 1);

// 分页
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 24;
$offset = ($page - 1) * $perPage;

$favorites = [];
$history = [];
$totalPages = 1;

if ($tab === 'favorites') {
    $total = $favCount;
    $totalPages = ceil($total / $perPage);
    $favorites = $db->fetchAll("SELECT * FROM favorites WHERE user_id = ? ORDER BY id DESC LIMIT $perPage OFFSET $offset", [$currentUser['id']]);
} else {
    $total = $historyCount;
    $totalPages = ceil($total / $perPage);
    $history = $db->fetchAll("SELECT * FROM watch_history WHERE user_id = ? ORDER BY watched_at DESC LIMIT $perPage OFFSET $offset", [$currentUser['id']]);
}

include __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding:24px 24px 100px;">

    <!-- 个人资料卡片 -->
    <div class="profile-header">
        <div class="profile-avatar" onclick="document.getElementById('avatarInput').click()">
            <?php if (!empty($currentUser['avatar'])): ?>
                <img id="avatarImg" src="<?php echo htmlspecialchars($currentUser['avatar']); ?>" alt="">
            <?php else: ?>
                <span id="avatarText"><?php echo mb_substr($currentUser['username'], 0, 1); ?></span>
            <?php endif; ?>
            <div class="profile-avatar-edit">更换头像</div>
            <input type="file" id="avatarInput" accept="image/*" style="display:none;" onchange="uploadAvatar(event)">
        </div>
        <div class="profile-info">
            <div class="profile-name">
                <?php echo htmlspecialchars($currentUser['username']); ?>
                <?php if (Auth::isAdmin()): ?>
                <span class="admin-badge">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                    开发者
                </span>
                <?php endif; ?>
            </div>
            <div class="profile-email">📧 <?php echo htmlspecialchars($currentUser['email']); ?> · 注册于 <?php echo date('Y-m-d', strtotime($currentUser['created_at'])); ?></div>
            <div class="profile-stats">
                <div class="profile-stat">
                    <div class="profile-stat-value"><?php echo $favCount; ?></div>
                    <div class="profile-stat-label">我的收藏</div>
                </div>
                <div class="profile-stat">
                    <div class="profile-stat-value"><?php echo $historyCount; ?></div>
                    <div class="profile-stat-label">观看记录</div>
                </div>
                <div class="profile-stat">
                    <div class="profile-stat-value"><?php echo $totalHours; ?>h</div>
                    <div class="profile-stat-label">总时长</div>
                </div>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <?php if (Auth::isAdmin()): ?>
            <a href="admin/index.php" class="btn btn-primary btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"></path></svg>
                管理后台
            </a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-outline btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                退出登录
            </a>
        </div>
    </div>

    <!-- Tab导航 -->
    <div class="tabs-nav">
        <a href="?tab=favorites" class="tab-item <?php echo $tab === 'favorites' ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
            我的收藏
        </a>
        <a href="?tab=history" class="tab-item <?php echo $tab === 'history' ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            观看历史
        </a>
    </div>

    <!-- 收藏列表 -->
    <?php if ($tab === 'favorites'): ?>
    <?php if (empty($favorites)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">💔</div>
        <div class="empty-state-title">还没有收藏内容</div>
        <div class="empty-state-desc">去发现精彩影视，点击收藏按钮开始收集吧</div>
        <a href="index.php" class="btn btn-primary" style="margin-top:24px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
            去看看
        </a>
    </div>
    <?php else: ?>
    <div class="media-grid">
        <?php 
        foreach ($favorites as $f) {
            $fId = $f['media_id'];
            $fType = $f['media_type'];
            $fTitle = $f['title'];
            $fPoster = $f['poster'];
            if (!$fPoster) $fPoster = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="300" height="450"><rect fill="#2a2a3a" width="300" height="450"/></svg>');
            $fUrl = "detail.php?id={$fId}&type={$fType}";
            $createdAt = date('Y-m-d', strtotime($f['created_at']));
            echo '<div class="media-card-wrap" style="position:relative;">
                <div class="media-card" onclick="location.href=\'' . $fUrl . '\'">
                    <div class="media-poster">
                        <img src="' . htmlspecialchars($fPoster) . '" loading="lazy" onerror="this.src=\'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="300" height="450"><rect fill="%232a2a3a" width="300" height="450"/></svg>') . '\'">
                        <div class="media-play-overlay"><div class="media-play-btn"><svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg></div></div>
                    </div>
                    <div class="media-info">
                        <div class="media-title">' . htmlspecialchars($fTitle) . '</div>
                        <div class="media-subtitle"><span>收藏于 ' . $createdAt . '</span></div>
                    </div>
                </div>
                <button class="history-delete" style="position:absolute;top:8px;right:8px;background:rgba(0,0,0,0.7);backdrop-filter:blur(10px);" onclick="event.stopPropagation();deleteFavorite(' . $fId . ', \'' . $fType . '\', this)" title="取消收藏">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </button>
            </div>';
        }
        ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="display:flex;justify-content:center;gap:8px;margin-top:40px;flex-wrap:wrap;">
        <?php if ($page > 1): ?>
        <a href="?tab=favorites&page=<?php echo $page-1; ?>" class="btn btn-outline btn-sm">上一页</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= min(10, $totalPages); $i++): ?>
        <a href="?tab=favorites&page=<?php echo $i; ?>" class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="?tab=favorites&page=<?php echo $page+1; ?>" class="btn btn-outline btn-sm">下一页</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php else: ?>
    <!-- 观看历史 -->
    <?php if (empty($history)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📺</div>
        <div class="empty-state-title">暂无观看记录</div>
        <div class="empty-state-desc">开始观看影视，这里会记录你的观看进度</div>
        <a href="index.php" class="btn btn-primary" style="margin-top:24px;">开始观看</a>
    </div>
    <?php else: ?>
    <div>
        <?php 
        foreach ($history as $h) {
            $hId = $h['media_id'];
            $hType = $h['media_type'];
            $hTitle = $h['title'];
            $hPoster = $h['poster'];
            if (!$hPoster) $hPoster = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="200" height="300"><rect fill="#2a2a3a" width="200" height="300"/></svg>');
            $hUrl = "detail.php?id={$hId}&type={$hType}";
            $watchedAt = date('Y-m-d H:i', strtotime($h['watched_at']));
            $seconds = intval($h['watch_seconds']);
            $hms = gmdate('H:i:s', $seconds);
            if (substr($hms, 0, 2) === '00') $hms = substr($hms, 3);
            $seasonText = '';
            if ($h['season_number'] > 0) {
                $seasonText = '第' . $h['season_number'] . '季';
                if ($h['episode_number'] > 0) $seasonText .= ' · 第' . $h['episode_number'] . '集';
            }
            $pUrl = "play.php?id={$hId}&type={$hType}&season={$h['season_number']}&episode={$h['episode_number']}";
            $pct = min(100, $seconds / 3600 * 50); // 粗略估算进度
            echo '<div class="history-item">
                <div class="history-poster" onclick="location.href=\'' . $hUrl . '\'">
                    <img src="' . htmlspecialchars($hPoster) . '" loading="lazy" onerror="this.src=\'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="200" height="300"><rect fill="%232a2a3a" width="200" height="300"/></svg>') . '\'">
                </div>
                <div class="history-content">
                    <div class="history-title" onclick="location.href=\'' . $pUrl . '\'">' . htmlspecialchars($hTitle) . '</div>
                    <div class="history-meta">
                        ' . ($seasonText ? '<span>📺 ' . $seasonText . '</span>' : '') . '
                        <span>🕐 观看时长: ' . $hms . '</span>
                        <span>📅 ' . $watchedAt . '</span>
                    </div>
                    <div class="history-progress">
                        <div class="progress-bar"><div class="progress-fill" style="width:' . $pct . '%;"></div></div>
                        <div class="progress-text">已观看约 ' . round($pct) . '%</div>
                    </div>
                </div>
                <button class="history-delete" onclick="deleteHistory(' . $hId . ', \'' . $hType . '\', ' . $h['season_number'] . ', ' . $h['episode_number'] . ', this)" title="删除记录">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </button>
            </div>';
        }
        ?>

        <div style="margin-top:24px;text-align:right;">
            <button class="btn btn-outline btn-sm" onclick="clearAllHistory()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                清空所有历史
            </button>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="display:flex;justify-content:center;gap:8px;margin-top:40px;flex-wrap:wrap;">
        <?php if ($page > 1): ?>
        <a href="?tab=history&page=<?php echo $page-1; ?>" class="btn btn-outline btn-sm">上一页</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= min(10, $totalPages); $i++): ?>
        <a href="?tab=history&page=<?php echo $i; ?>" class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="?tab=history&page=<?php echo $page+1; ?>" class="btn btn-outline btn-sm">下一页</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>

</main>

<script>
// 上传头像
async function uploadAvatar(e) {
    const file = e.target.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
        showToast('请选择图片文件', 'warning');
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        showToast('图片大小不能超过5MB', 'warning');
        return;
    }
    
    // 转为base64上传
    const reader = new FileReader();
    reader.onload = async () => {
        const base64 = reader.result;
        try {
            const res = await fetch('ajax/upload_avatar.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({avatar: base64})
            });
            const data = await res.json();
            if (data.success) {
                showToast('头像更新成功', 'success');
                setTimeout(() => location.reload(), 600);
            } else {
                showToast(data.message, 'error');
            }
        } catch (err) {
            showToast('上传失败', 'error');
        }
    };
    reader.readAsDataURL(file);
}

// 删除收藏
async function deleteFavorite(id, type, btn) {
    if (!confirm('确定要取消收藏吗？')) return;
    try {
        const res = await fetch('ajax/toggle_favorite.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({media_id: id, media_type: type, title: '', poster: ''})
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            btn.closest('.media-card-wrap').style.transition = 'all 0.3s';
            btn.closest('.media-card-wrap').style.opacity = '0';
            setTimeout(() => btn.closest('.media-card-wrap').remove(), 300);
        } else {
            showToast(data.message, 'error');
        }
    } catch (e) {
        showToast('操作失败', 'error');
    }
}

// 删除单条历史
async function deleteHistory(id, type, season, episode, btn) {
    if (!confirm('确定删除此条观看记录吗？')) return;
    try {
        const res = await fetch('ajax/delete_history.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({media_id: id, media_type: type, season, episode, all: false})
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            const el = btn.closest('.history-item');
            el.style.transition = 'all 0.3s';
            el.style.opacity = '0';
            el.style.height = el.offsetHeight + 'px';
            setTimeout(() => { el.style.height = '0'; el.style.padding = '0'; el.style.margin = '0'; }, 100);
            setTimeout(() => { el.remove(); }, 400);
        } else {
            showToast(data.message, 'error');
        }
    } catch (e) {
        showToast('操作失败', 'error');
    }
}

async function clearAllHistory() {
    if (!confirm('确定清空所有观看历史吗？此操作不可恢复！')) return;
    try {
        const res = await fetch('ajax/delete_history.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({all: true})
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(data.message, 'error');
        }
    } catch (e) {
        showToast('操作失败', 'error');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
