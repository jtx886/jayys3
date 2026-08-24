<?php
$pageName = '用户收藏';
include __DIR__ . '/admin_header.php';

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;
$search = trim($_GET['q'] ?? '');
$filterUser = intval($_GET['user'] ?? 0);

$where = [];
$params = [];
if ($search) { $where[] = "f.title LIKE ?"; $params[] = "%$search%"; }
if ($filterUser > 0) { $where[] = "f.user_id = ?"; $params[] = $filterUser; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = $db->fetchOne("SELECT COUNT(*) as c FROM favorites f $whereSql", $params)['c'];
$totalPages = ceil($total / $perPage);
$favs = $db->fetchAll(
    "SELECT f.*, u.username, u.avatar FROM favorites f LEFT JOIN users u ON f.user_id = u.id
     $whereSql ORDER BY f.id DESC LIMIT $perPage OFFSET $offset",
    $params
);
$usersList = $db->fetchAll("SELECT id, username FROM users ORDER BY id DESC LIMIT 50");
?>
<div class="admin-content">
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">⭐ 用户收藏</div>
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
                <a href="favorites.php" class="btn btn-outline btn-sm">重置</a>
            </form>
        </div>
        <div class="panel-body" style="padding:0;">
            <table class="data-table">
                <thead><tr><th>用户</th><th>收藏内容</th><th>类型</th><th>收藏时间</th></tr></thead>
                <tbody>
                    <?php foreach ($favs as $f): ?>
                    <tr>
                        <td>
                            <div class="table-user">
                                <div class="table-avatar">
                                    <?php if (!empty($f['avatar'])): ?>
                                        <img src="../<?php echo htmlspecialchars($f['avatar']); ?>" alt="">
                                    <?php else: echo mb_substr($f['username'] ?? 'U', 0, 1); endif; ?>
                                </div>
                                <span><?php echo htmlspecialchars($f['username'] ?? '用户#' . $f['user_id']); ?></span>
                            </div>
                        </td>
                        <td>
                            <div style="display:flex;gap:10px;align-items:center;">
                                <?php if (!empty($f['poster'])): ?>
                                    <img src="<?php echo htmlspecialchars($f['poster']); ?>" style="width:36px;height:54px;border-radius:6px;object-fit:cover;" onerror="this.style.display='none'">
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight:500;"><?php echo htmlspecialchars($f['title']); ?></div>
                                    <div style="font-size:11px;color:var(--text-muted);">ID:<?php echo $f['media_id']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php 
                            $mt = strtolower($f['media_type']);
                            $labels = ['movie' => ['电影', 'badge-success'], 'tv' => ['电视剧', 'badge-info'], 'anime' => ['动漫', 'badge-warning']];
                            $lb = $labels[$mt] ?? [$mt, 'badge-info'];
                            echo "<span class=\"badge {$lb[1]}\">{$lb[0]}</span>";
                            ?>
                        </td>
                        <td style="color:var(--text-muted);font-size:13px;"><?php echo date('Y-m-d H:i', strtotime($f['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($favs)): ?><tr><td colspan="4" style="text-align:center;padding:48px;color:var(--text-muted);">暂无记录</td></tr><?php endif; ?>
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
