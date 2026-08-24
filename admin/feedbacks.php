<?php
$pageName = '反馈管理';
include __DIR__ . '/admin_header.php';

$page = max(1, intval($_GET['page'] ?? 1));
$status = $_GET['status'] ?? 'all';
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
if ($status === 'pending') $where[] = "status = 0";
elseif ($status === 'replied') $where[] = "status = 1";
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = $db->fetchOne("SELECT COUNT(*) as c FROM feedbacks $whereSql", $params)['c'];
$totalPages = ceil($total / $perPage);

$feedbacks = $db->fetchAll(
    "SELECT f.*, u.username, u.avatar, u.email 
     FROM feedbacks f LEFT JOIN users u ON f.user_id = u.id 
     $whereSql ORDER BY f.id DESC LIMIT $perPage OFFSET $offset",
    $params
);

foreach ($feedbacks as &$f) {
    $fid = $f['id'];
    $f['like_count'] = $db->fetchOne("SELECT COUNT(*) as c FROM feedback_likes WHERE feedback_id = ?", [$fid])['c'];
    $f['replies'] = $db->fetchAll(
        "SELECT r.*, u.username, r.is_admin FROM feedback_replies r LEFT JOIN users u ON r.user_id = u.id 
         WHERE r.feedback_id = ? ORDER BY r.is_admin DESC, r.id ASC",
        [$fid]
    );
}
unset($f);
?>
<div class="admin-content">
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">💬 反馈管理 <small style="font-weight:400;font-size:13px;color:var(--text-muted);">共 <?php echo $total; ?> 条</small></div>
            <div style="display:flex;gap:8px;">
                <a href="?status=all" class="btn btn-sm <?php echo $status === 'all' ? 'btn-primary' : 'btn-outline'; ?>">全部</a>
                <a href="?status=pending" class="btn btn-sm <?php echo $status === 'pending' ? 'btn-primary' : 'btn-outline'; ?>">待处理 <?php echo $pendingFb ? "({$pendingFb})" : ''; ?></a>
                <a href="?status=replied" class="btn btn-sm <?php echo $status === 'replied' ? 'btn-primary' : 'btn-outline'; ?>">已回复</a>
            </div>
        </div>
        <div class="panel-body">
            <?php if (empty($feedbacks)): ?>
            <div style="text-align:center;padding:48px;color:var(--text-muted);">暂无反馈</div>
            <?php endif; ?>

            <?php foreach ($feedbacks as $f): ?>
            <div class="feedback-item" id="fb_<?php echo $f['id']; ?>" style="margin-bottom:20px;">
                <div class="feedback-header">
                    <div class="feedback-avatar">
                        <?php if (!empty($f['avatar'])): ?>
                            <img src="../<?php echo htmlspecialchars($f['avatar']); ?>" alt="">
                        <?php else: echo mb_substr($f['username'] ?? 'U', 0, 1); endif; ?>
                    </div>
                    <div class="feedback-body">
                        <div class="feedback-author-row">
                            <span class="feedback-author"><?php echo htmlspecialchars($f['username'] ?? '用户#' . $f['user_id']); ?></span>
                            <span style="font-size:11px;color:var(--text-muted);"><?php echo htmlspecialchars($f['email'] ?? ''); ?></span>
                            <span class="feedback-time"><?php echo date('Y-m-d H:i', strtotime($f['created_at'])); ?></span>
                            <span class="feedback-status <?php echo $f['status'] ? 'replied' : 'pending'; ?>">
                                <?php echo $f['status'] ? '✅ 已回复' : '⏳ 待处理'; ?>
                            </span>
                            <span style="color:var(--text-muted);font-size:12px;">❤️ <?php echo $f['like_count']; ?></span>
                        </div>
                        <h3 class="feedback-item-title"><?php echo htmlspecialchars($f['title']); ?></h3>
                        <div class="feedback-item-content"><?php echo nl2br(htmlspecialchars($f['content'])); ?></div>
                    </div>
                </div>
                <div class="feedback-replies" style="padding:0 24px 16px;">
                    <div class="reply-list">
                        <?php foreach ($f['replies'] as $r): ?>
                        <?php
                        $isAdmin = !empty($r['is_admin']);
                        $rName = $isAdmin ? ADMIN_USERNAME : ($r['username'] ?? '用户#' . $r['user_id']);
                        ?>
                        <div class="reply-item <?php echo $isAdmin ? 'admin-reply' : ''; ?>">
                            <div class="reply-avatar"><?php echo $isAdmin ? '🛡️' : mb_substr($rName, 0, 1); ?></div>
                            <div class="reply-body">
                                <div class="reply-author-row">
                                    <span class="reply-author">
                                        <?php echo htmlspecialchars($rName); ?>
                                        <?php if ($isAdmin): ?>
                                        <span class="admin-badge">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
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
                    </div>
                    <div class="reply-form">
                        <div class="nav-avatar" style="width:36px;height:36px;background:linear-gradient(135deg,#ef4444,#dc2626);flex-shrink:0;">🛡️</div>
                        <input type="text" class="form-input admin-reply-input" placeholder="以管理员身份回复..." maxlength="1000" style="flex:1;">
                        <button class="btn btn-primary btn-sm" onclick="submitAdminReply(<?php echo $f['id']; ?>, this)">📝 回复</button>
                    </div>
                </div>
                <div style="padding:14px 24px;border-top:1px solid var(--border-color);text-align:right;">
                    <button class="btn btn-sm btn-danger" onclick="deleteFeedback(<?php echo $f['id']; ?>)">🗑️ 删除反馈</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if ($totalPages > 1): ?>
        <div style="padding:16px 24px;display:flex;justify-content:center;gap:8px;flex-wrap:wrap;border-top:1px solid var(--border-color);">
            <?php 
            $qs = http_build_query(['status' => $status]);
            if ($page > 1): ?>
            <a href="?<?php echo $qs; ?>&page=<?php echo $page-1; ?>" class="btn btn-outline btn-sm">上一页</a>
            <?php endif;
            for ($i = max(1, $page-3); $i <= min($totalPages, $page+3); $i++):
            ?>
            <a href="?<?php echo $qs; ?>&page=<?php echo $i; ?>" class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>"><?php echo $i; ?></a>
            <?php endfor;
            if ($page < $totalPages): ?>
            <a href="?<?php echo $qs; ?>&page=<?php echo $page+1; ?>" class="btn btn-outline btn-sm">下一页</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
async function submitAdminReply(fid, btn) {
    const input = btn.closest('.reply-form').querySelector('.admin-reply-input');
    const content = input.value.trim();
    if (!content) return showToast('请输入回复内容', 'warning');
    
    btn.disabled = true; btn.textContent = '发送中...';
    const res = await adminApi('reply_feedback', {feedback_id: fid, content});
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) setTimeout(() => location.reload(), 600);
    btn.disabled = false; btn.textContent = '📝 回复';
}

async function deleteFeedback(fid) {
    if (!confirm('确定删除此反馈及所有回复？')) return;
    const res = await adminApi('delete_feedback', {feedback_id: fid});
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) setTimeout(() => location.reload(), 600);
}
</script>

<?php include __DIR__ . '/admin_footer.php'; ?>
