<?php
require_once __DIR__ . '/config.php';

$pageTitle = '反馈';
$activeNav = 'feedback';

$db = Database::getInstance();

// 读取所有反馈 + 回复 + 点赞
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$total = $db->fetchOne("SELECT COUNT(*) as c FROM feedbacks")['c'];
$totalPages = ceil($total / $perPage);

$feedbacks = $db->fetchAll("
    SELECT f.*, u.username as author_name, u.avatar as author_avatar
    FROM feedbacks f 
    LEFT JOIN users u ON f.user_id = u.id 
    ORDER BY f.id DESC 
    LIMIT $perPage OFFSET $offset
");

$currentUserId = Auth::isLoggedIn() ? $_SESSION['user_id'] : 0;

// 处理每个反馈的回复和点赞
foreach ($feedbacks as &$f) {
    $fid = $f['id'];
    
    // 点赞数 + 当前用户是否点过
    $likeCount = $db->fetchOne("SELECT COUNT(*) as c FROM feedback_likes WHERE feedback_id = ?", [$fid])['c'];
    $f['like_count'] = $likeCount;
    $f['liked_by_me'] = false;
    if ($currentUserId > 0) {
        $liked = $db->fetchOne("SELECT 1 FROM feedback_likes WHERE feedback_id = ? AND user_id = ?", [$fid, $currentUserId]);
        $f['liked_by_me'] = !empty($liked);
    }
    
    // 回复 - 管理员在最前（反馈者之下）
    $allReplies = $db->fetchAll("
        SELECT r.*, u.username as reply_name, u.avatar as reply_avatar, r.is_admin
        FROM feedback_replies r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.feedback_id = ?
        ORDER BY 
            CASE WHEN r.is_admin = 1 THEN 1 ELSE 0 END DESC,
            r.id ASC
    ", [$fid]);
    
    // 如果是管理员回复且 user_id=0，使用默认名称
    foreach ($allReplies as &$r) {
        if ($r['is_admin'] && (!$r['reply_name'] || $r['user_id'] == 0)) {
            $r['reply_name'] = ADMIN_USERNAME;
            $r['is_admin_actual'] = true;
        } else {
            $r['is_admin_actual'] = false;
        }
    }
    $f['replies'] = $allReplies;
}
unset($f);

include __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding:24px 24px 100px;max-width:960px;">

    <div class="section-header" style="margin-bottom:24px;">
        <h1 class="section-title">
            <span class="section-title-icon">💬</span>
            用户反馈
        </h1>
    </div>

    <!-- 发布反馈 -->
    <?php if (Auth::isLoggedIn()): ?>
    <div class="feedback-create">
        <div class="feedback-create-header">
            <div class="feedback-create-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            </div>
            <div>
                <div class="feedback-create-title">我要反馈</div>
                <div style="font-size:12px;color:var(--text-muted);">您的建议是我们前进的动力 💪</div>
            </div>
        </div>
        <div class="space-y-4">
            <div>
                <label class="form-label">反馈标题</label>
                <input type="text" id="fbTitle" class="form-input" placeholder="简短描述您的问题或建议（5-50字）" maxlength="50">
            </div>
            <div>
                <label class="form-label">反馈内容</label>
                <textarea id="fbContent" class="form-input" rows="5" placeholder="请详细描述反馈内容，我们会尽快处理..." maxlength="2000" style="resize:vertical;"></textarea>
            </div>
            <div style="text-align:right;">
                <button class="btn btn-primary" onclick="submitFeedback()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    提交反馈
                </button>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="panel" style="padding:28px;text-align:center;margin-bottom:32px;">
        <div class="empty-state">
            <div class="empty-state-icon">🔒</div>
            <div class="empty-state-title">登录后才能发布反馈</div>
            <div class="empty-state-desc">登录后您可以发布反馈、回复和点赞</div>
            <a href="login.php" class="btn btn-primary" style="margin-top:20px;">立即登录</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- 反馈列表 -->
    <div class="feedback-list">
        <?php if (empty($feedbacks)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <div class="empty-state-title">还没有任何反馈</div>
            <div class="empty-state-desc">成为第一个提出反馈的人吧！</div>
        </div>
        <?php endif; ?>

        <?php foreach ($feedbacks as $i => $f): ?>
        <?php
        $fid = $f['id'];
        $isAuthor = ($currentUserId == $f['user_id']);
        $authorAvatar = $f['author_avatar'] ?? '';
        $authorName = $f['author_name'] ?? '用户' . $f['user_id'];
        $firstChar = mb_substr($authorName, 0, 1);
        $allReplies = $f['replies'] ?? [];
        $replyCount = count($allReplies);
        // 前3条(如果有管理员回复，保留管理员+2条普通) 其余收起
        $showCount = $replyCount > 3 ? 3 : $replyCount;
        $visibleReplies = array_slice($allReplies, 0, $showCount);
        $hiddenCount = $replyCount - $showCount;
        ?>
        <div class="feedback-item" id="fb_<?php echo $fid; ?>">
            <div class="feedback-header">
                <div class="feedback-avatar">
                    <?php if ($authorAvatar): ?>
                        <img src="<?php echo htmlspecialchars($authorAvatar); ?>" alt="">
                    <?php else: ?>
                        <?php echo $firstChar; ?>
                    <?php endif; ?>
                </div>
                <div class="feedback-body">
                    <div class="feedback-author-row">
                        <span class="feedback-author"><?php echo htmlspecialchars($authorName); ?></span>
                        <?php if ($isAuthor): ?><span class="badge badge-info" style="font-size:10px;">作者</span><?php endif; ?>
                        <span class="feedback-time"><?php echo date('Y-m-d H:i', strtotime($f['created_at'])); ?></span>
                        <span class="feedback-status <?php echo $f['status'] ? 'replied' : 'pending'; ?>">
                            <?php echo $f['status'] ? '✅ 已回复' : '⏳ 处理中'; ?>
                        </span>
                    </div>
                    <h3 class="feedback-item-title"><?php echo htmlspecialchars($f['title']); ?></h3>
                    <div class="feedback-item-content"><?php echo nl2br(htmlspecialchars($f['content'])); ?></div>
                </div>
            </div>
            
            <div class="feedback-actions">
                <div class="feedback-action <?php echo $f['liked_by_me'] ? 'liked' : ''; ?>" 
                     onclick="toggleLike(<?php echo $fid; ?>, this)">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="<?php echo $f['liked_by_me'] ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                    <span>点赞</span>
                    <span class="like-count">(<?php echo $f['like_count']; ?>)</span>
                </div>
                <div class="feedback-action" onclick="toggleReplyBox(<?php echo $fid; ?>)">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    <span>回复</span>
                    (<?php echo $replyCount; ?>)
                </div>
            </div>

            <div class="feedback-replies">
                <div class="reply-list" id="replyList_<?php echo $fid; ?>">
                    <?php foreach ($visibleReplies as $r): ?>
                    <?php
                    $rAvatar = $r['reply_avatar'] ?? '';
                    $rName = $r['reply_name'] ?? '用户';
                    $rChar = mb_substr($rName, 0, 1);
                    $isAdminReply = !empty($r['is_admin']) || !empty($r['is_admin_actual']);
                    ?>
                    <div class="reply-item <?php echo $isAdminReply ? 'admin-reply' : ''; ?>">
                        <div class="reply-avatar">
                            <?php if ($rAvatar && !$isAdminReply): ?>
                                <img src="<?php echo htmlspecialchars($rAvatar); ?>" alt="">
                            <?php else: ?>
                                <?php echo $isAdminReply ? '🛡️' : $rChar; ?>
                            <?php endif; ?>
                        </div>
                        <div class="reply-body">
                            <div class="reply-author-row">
                                <span class="reply-author">
                                    <?php echo htmlspecialchars($rName); ?>
                                    <?php if ($isAdminReply): ?>
                                    <span class="admin-badge">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                                        开发者
                                    </span>
                                    <?php endif; ?>
                                </span>
                                <span class="reply-time"><?php echo date('Y-m-d H:i', strtotime($r['created_at'])); ?></span>
                            </div>
                            <div class="reply-content"><?php echo nl2br(htmlspecialchars($r['content'])); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if ($hiddenCount > 0): ?>
                    <button class="expand-btn visible" onclick="toggleExpand(<?php echo $fid; ?>, <?php echo $hiddenCount; ?>)">
                        展开 <?php echo $hiddenCount; ?> 条更多回复 ▼
                    </button>
                    <?php 
                    // 输出隐藏的回复
                    $hiddenReplies = array_slice($allReplies, $showCount);
                    echo '<div class="hidden-replies" id="hiddenReplies_' . $fid . '" style="display:none;flex-direction:column;gap:12px;margin-top:12px;">';
                    foreach ($hiddenReplies as $r) {
                        $rAvatar = $r['reply_avatar'] ?? '';
                        $rName = $r['reply_name'] ?? '用户';
                        $rChar = mb_substr($rName, 0, 1);
                        $isAdminReply = !empty($r['is_admin']) || !empty($r['is_admin_actual']);
                        echo '<div class="reply-item ' . ($isAdminReply ? 'admin-reply' : '') . '">
                            <div class="reply-avatar">' . ($rAvatar && !$isAdminReply ? '<img src="'.htmlspecialchars($rAvatar).'">' : ($isAdminReply ? '🛡️' : $rChar)) . '</div>
                            <div class="reply-body">
                                <div class="reply-author-row">
                                    <span class="reply-author">' . htmlspecialchars($rName);
                                    if ($isAdminReply) echo '<span class="admin-badge"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>开发者</span>';
                        echo '</span><span class="reply-time">' . date('Y-m-d H:i', strtotime($r['created_at'])) . '</span>
                                </div>
                                <div class="reply-content">' . nl2br(htmlspecialchars($r['content'])) . '</div>
                            </div>
                        </div>';
                    }
                    echo '</div>';
                    endif; 
                    ?>
                </div>

                <?php if (Auth::isLoggedIn()): ?>
                <div class="reply-form" id="replyForm_<?php echo $fid; ?>" style="display:none;">
                    <div class="nav-avatar" style="width:36px;height:36px;font-size:13px;flex-shrink:0;">
                        <?php 
                        $u = $currentUser;
                        $ava = $_SESSION['user_info']['avatar'] ?? '';
                        if ($ava): ?>
                        <img src="<?php echo htmlspecialchars($ava); ?>" alt="">
                        <?php else: echo mb_substr($_SESSION['username'], 0, 1); endif; ?>
                    </div>
                    <input type="text" class="form-input" id="replyInput_<?php echo $fid; ?>" placeholder="写下你的回复..." maxlength="500">
                    <button class="btn btn-primary btn-sm" onclick="submitReply(<?php echo $fid; ?>)">发送</button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="display:flex;justify-content:center;gap:8px;margin-top:40px;flex-wrap:wrap;">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page-1; ?>" class="btn btn-outline btn-sm">上一页</a>
        <?php endif; ?>
        <?php 
        $start = max(1, $page - 3);
        $end = min($totalPages, $start + 6);
        for ($i = $start; $i <= $end; $i++): ?>
        <a href="?page=<?php echo $i; ?>" class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page+1; ?>" class="btn btn-outline btn-sm">下一页</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</main>

<script>
<?php if (!Auth::isLoggedIn()): ?>
function requireLoginTip() {
    showToast('请先登录后再操作哦', 'warning');
    setTimeout(() => location.href = 'login.php', 1000);
}
<?php endif; ?>

async function submitFeedback() {
    <?php if (!Auth::isLoggedIn()): ?>
    requireLoginTip(); return;
    <?php endif; ?>
    const title = document.getElementById('fbTitle').value.trim();
    const content = document.getElementById('fbContent').value.trim();
    if (title.length < 5) return showToast('标题至少5个字', 'warning');
    if (content.length < 10) return showToast('内容至少10个字', 'warning');
    
    try {
        const res = await fetch('ajax/feedback_action.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'create', title, content})
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

async function toggleLike(fid, el) {
    <?php if (!Auth::isLoggedIn()): ?>
    requireLoginTip(); return;
    <?php endif; ?>
    try {
        const res = await fetch('ajax/feedback_action.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'like', feedback_id: fid})
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            const countEl = el.querySelector('.like-count');
            if (data.data.liked) {
                el.classList.add('liked');
                el.querySelector('svg').setAttribute('fill', 'currentColor');
            } else {
                el.classList.remove('liked');
                el.querySelector('svg').setAttribute('fill', 'none');
            }
            countEl.textContent = '(' + data.data.like_count + ')';
        } else {
            showToast(data.message, 'error');
        }
    } catch (e) {
        showToast('操作失败', 'error');
    }
}

function toggleReplyBox(fid) {
    <?php if (!Auth::isLoggedIn()): ?>
    requireLoginTip(); return;
    <?php endif; ?>
    const box = document.getElementById('replyForm_' + fid);
    if (box.style.display === 'none' || !box.style.display) {
        box.style.display = 'flex';
        setTimeout(() => document.getElementById('replyInput_' + fid).focus(), 100);
    } else {
        box.style.display = 'none';
    }
}

async function submitReply(fid) {
    const input = document.getElementById('replyInput_' + fid);
    const content = input.value.trim();
    if (!content) return showToast('请输入回复内容', 'warning');
    if (content.length > 500) return showToast('回复内容过长', 'warning');
    
    try {
        const res = await fetch('ajax/feedback_action.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'reply', feedback_id: fid, content})
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

function toggleExpand(fid, hiddenCount) {
    const box = document.getElementById('hiddenReplies_' + fid);
    const btn = box.previousElementSibling;
    if (box.style.display === 'none') {
        box.style.display = 'flex';
        btn.textContent = '收起 ' + hiddenCount + ' 条回复 ▲';
    } else {
        box.style.display = 'none';
        btn.textContent = '展开 ' + hiddenCount + ' 条更多回复 ▼';
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
