<?php
$pageName = '观看历史';
include __DIR__ . '/admin_header.php';

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;
$search = trim($_GET['q'] ?? '');
$filterUser = intval($_GET['user'] ?? 0);

$where = [];
$params = [];
if ($search) {
    $where[] = "h.title LIKE ?";
    $params[] = "%$search%";
}
if ($filterUser > 0) {
    $where[] = "h.user_id = ?";
    $params[] = $filterUser;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = $db->fetchOne("SELECT COUNT(*) as c FROM watch_history h $whereSql", $params)['c'];
$totalPages = ceil($total / $perPage);
$histories = $db->fetchAll(
    "SELECT h.*, u.username, u.avatar FROM watch_history h LEFT JOIN users u ON h.user_id = u.id 
     $whereSql ORDER BY h.watched_at DESC LIMIT $perPage OFFSET $offset",
    $params
);

$usersList = $db->fetchAll("SELECT id, username FROM users ORDER BY id DESC LIMIT 50");
?>
<div class="admin-content">
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">⏱️ 观看历史记录</div>
            <form style="display:flex;gap:8px;" method="get">
                <select name="user" class="form-input" style="width:160px;padding:8px 10px;">
                    <option value="0">全部用户</option>
                    <?php foreach ($usersList as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo $filterUser == $u['id'] ? 'selected' : ''; ?>>
                            #<?php echo $u['id']; ?> - <?php echo htmlspecialchars($u['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="搜索影视名称" class="form-input" style="width:200px;padding:8px 12px;">
                <button type="submit" class="btn btn-primary btn-sm">筛选</button>
                <a href="history.php" class="btn btn-outline btn-sm">重置</a>
            </form>
        </div>
        <div class="panel-body" style="padding:0;">
            <table class="data-table">
                <thead><tr><th>用户</th><th>影视</th><th>季/集</th><th>观看时长</th><th>时间</th></tr></thead>
                <tbody>
                    <?php foreach ($histories as $h): ?>
                    <tr>
                        <td>
                            <div class="table-user">
                                <div class="table-avatar">
                                    <?php if (!empty($h['avatar'])): ?>
                                        <img src="../<?php echo htmlspecialchars($h['avatar']); ?>" alt="">
                                    <?php else: echo mb_substr($h['username'] ?? 'U', 0, 1); endif; ?>
                                </div>
                                <span><?php echo htmlspecialchars($h['username'] ?? '用户#' . $h['user_id']); ?></span>
                            </div>
                        </td>
                        <td>
                            <div style="display:flex;gap:10px;align-items:center;">
                                <?php if (!empty($h['poster'])): ?>
                                    <img src="<?php echo htmlspecialchars($h['poster']); ?>" style="width:36px;height:54px;border-radius:6px;object-fit:cover;" onerror="this.style.display='none'">
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight:500;"><?php echo htmlspecialchars($h['title']); ?></div>
                                    <div style="font-size:11px;color:var(--text-muted);"><?php echo strtoupper($h['media_type']); ?> · ID:<?php echo $h['media_id']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo $h['season_number'] > 0 ? "S{$h['season_number']} / E{$h['episode_number']}" : '—'; ?></td>
                        <td><span class="badge badge-info"><?php echo gmdate('H:i:s', $h['watch_seconds']); ?></span></td>
                        <td style="color:var(--text-muted);font-size:13px;"><?php echo date('Y-m-d H:i', strtotime($h['watched_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($histories)): ?><tr><td colspan="5" style="text-align:center;padding:48px;color:var(--text-muted);">暂无记录</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <div style="padding:16px 24px;display:flex;justify-content:center;gap:8px;flex-wrap:wrap;">
            <?php 
            $qs = http_build_query(['q' => $search, 'user' => $filterUser]);
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
<?php include __DIR__ . '/admin_footer.php'; ?>
