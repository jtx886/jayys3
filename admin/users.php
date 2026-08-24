<?php
$pageName = '用户管理';
include __DIR__ . '/admin_header.php';

$page = max(1, intval($_GET['page'] ?? 1));
$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
if ($search) {
    $where[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($statusFilter === 'banned') $where[] = "is_banned = 1";
elseif ($statusFilter === 'normal') $where[] = "is_banned = 0";
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = $db->fetchOne("SELECT COUNT(*) as c FROM users $whereSql", $params)['c'];
$totalPages = ceil($total / $perPage);

$users = $db->fetchAll("SELECT * FROM users $whereSql ORDER BY id DESC LIMIT $perPage OFFSET $offset", $params);
?>
<div class="admin-content">

    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">👥 用户管理</div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <form style="display:flex;gap:8px;" method="get">
                    <select name="status" class="form-input" style="width:120px;padding:8px 10px;">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>全部</option>
                        <option value="normal" <?php echo $statusFilter === 'normal' ? 'selected' : ''; ?>>正常</option>
                        <option value="banned" <?php echo $statusFilter === 'banned' ? 'selected' : ''; ?>>已封禁</option>
                    </select>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="用户名/邮箱搜索" class="form-input" style="width:220px;padding:8px 12px;">
                    <button type="submit" class="btn btn-primary btn-sm">搜索</button>
                </form>
                <button class="btn btn-outline btn-sm" onclick="sendEmailModal()">📧 群发邮件</button>
            </div>
        </div>
        <div class="panel-body" style="padding:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th><th>用户</th><th>邮箱</th><th>注册时间</th><th>封禁状态</th><th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td style="font-family:monospace;color:var(--text-muted);">#<?php echo $u['id']; ?></td>
                        <td>
                            <div class="table-user">
                                <div class="table-avatar">
                                    <?php if (!empty($u['avatar'])): ?>
                                        <img src="../<?php echo htmlspecialchars($u['avatar']); ?>" alt="">
                                    <?php else: echo mb_substr($u['username'], 0, 1); endif; ?>
                                </div>
                                <div>
                                    <div style="font-weight:600;"><?php echo htmlspecialchars($u['username']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--text-muted);font-size:13px;"><?php echo htmlspecialchars($u['email']); ?></td>
                        <td style="color:var(--text-muted);font-size:13px;"><?php echo date('Y-m-d', strtotime($u['created_at'])); ?></td>
                        <td>
                            <?php if (!$u['is_banned']): ?>
                                <span class="badge badge-success">正常</span>
                            <?php else: ?>
                                <span class="badge badge-danger" title="<?php echo htmlspecialchars($u['ban_reason']); ?>">封禁中</span>
                                <?php if ($u['ban_end_time']): ?>
                                    <div style="font-size:11px;color:var(--text-muted);margin-top:3px;">
                                        至 <?php echo date('m-d', strtotime($u['ban_end_time'])); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <?php if (!$u['is_banned']): ?>
                                    <button class="btn btn-sm btn-outline" onclick="banModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>')">🔨 封禁</button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-success" onclick="unbanUser(<?php echo $u['id']; ?>)">✅ 解封</button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline" onclick="singleEmailModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>')">📧 发邮件</button>
                                <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $u['id']; ?>)">🗑️ 删除</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:48px;color:var(--text-muted);">没有找到用户</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <div style="padding:16px 24px;display:flex;justify-content:center;gap:8px;flex-wrap:wrap;">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?>&q=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>" class="btn btn-outline btn-sm">上一页</a>
            <?php endif; ?>
            <?php
            $start = max(1, $page - 3);
            $end = min($totalPages, $start + 6);
            for ($i = $start; $i <= $end; $i++):
            ?>
            <a href="?page=<?php echo $i; ?>&q=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>" class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page+1; ?>&q=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>" class="btn btn-outline btn-sm">下一页</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 封禁弹窗 -->
<div class="modal-overlay" id="banModal" style="display:none;">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <div class="modal-title">🔨 封禁用户 - <span id="banUsername"></span></div>
            <button class="modal-close" onclick="document.getElementById('banModal').style.display='none'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="banUserId">
            <div class="form-group">
                <label class="form-label">封禁原因</label>
                <input type="text" id="banReason" class="form-input" placeholder="例如：违反社区规则" value="违反平台使用规则">
            </div>
            <div class="form-group">
                <label class="form-label">封禁时长</label>
                <select id="banDays" class="form-input" onchange="document.getElementById('banCustomEnd').style.display=this.value==='custom'?'':'none'">
                    <option value="1">1 天</option>
                    <option value="3">3 天</option>
                    <option value="7" selected>7 天</option>
                    <option value="30">30 天</option>
                    <option value="0">永久封禁</option>
                    <option value="custom">自定义时间</option>
                </select>
            </div>
            <div class="form-group" id="banCustomEnd" style="display:none;">
                <label class="form-label">解封日期</label>
                <input type="date" id="banCustomEndDate" class="form-input">
            </div>
            <div style="padding:12px;background:rgba(239,68,68,0.1);border-radius:10px;color:#f87171;font-size:13px;">
                ⚠️ 封禁后系统会自动向用户发送邮件通知封禁详情
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="document.getElementById('banModal').style.display='none'">取消</button>
            <button class="btn btn-danger" onclick="confirmBan()">确认封禁</button>
        </div>
    </div>
</div>

<!-- 邮件弹窗 -->
<div class="modal-overlay" id="emailModal" style="display:none;">
    <div class="modal" style="max-width:520px;">
        <div class="modal-header">
            <div class="modal-title">📧 发送邮件 - <span id="emailTarget">所有用户</span></div>
            <button class="modal-close" onclick="document.getElementById('emailModal').style.display='none'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="emailTargetUid" value="0">
            <div class="form-group">
                <label class="form-label">邮件标题</label>
                <input type="text" id="emailSubject" class="form-input" placeholder="请输入邮件标题">
            </div>
            <div class="form-group">
                <label class="form-label">邮件内容</label>
                <textarea id="emailContent" class="form-input" rows="6" placeholder="请输入邮件内容..." style="resize:vertical;"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="document.getElementById('emailModal').style.display='none'">取消</button>
            <button class="btn btn-primary" onclick="confirmSendEmail()">📤 发送邮件</button>
        </div>
    </div>
</div>

<script>
function banModal(uid, name) {
    document.getElementById('banUserId').value = uid;
    document.getElementById('banUsername').textContent = name;
    document.getElementById('banModal').style.display = 'flex';
}

async function confirmBan() {
    const uid = parseInt(document.getElementById('banUserId').value);
    const reason = document.getElementById('banReason').value.trim();
    const daysSel = document.getElementById('banDays').value;
    const customEnd = document.getElementById('banCustomEndDate').value;
    
    if (!reason) return showToast('请填写封禁原因', 'warning');
    if (daysSel === 'custom' && !customEnd) return showToast('请选择解封日期', 'warning');
    
    const res = await adminApi('ban_user', {
        user_id: uid,
        reason,
        days: daysSel === 'custom' ? 0 : parseInt(daysSel),
        custom_end: daysSel === 'custom' ? customEnd : ''
    });
    
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) setTimeout(() => location.reload(), 800);
}

async function unbanUser(uid) {
    if (!confirm('确定解除此用户的封禁？')) return;
    const res = await adminApi('unban_user', {user_id: uid});
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) setTimeout(() => location.reload(), 800);
}

async function deleteUser(uid) {
    if (!confirm('确定删除此用户？所有数据将被清除！')) return;
    if (!confirm('再次确认：此操作不可恢复！')) return;
    const res = await adminApi('delete_user', {user_id: uid});
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) setTimeout(() => location.reload(), 800);
}

function singleEmailModal(uid, name) {
    document.getElementById('emailTargetUid').value = uid;
    document.getElementById('emailTarget').textContent = name;
    document.getElementById('emailModal').style.display = 'flex';
}

function sendEmailModal() {
    document.getElementById('emailTargetUid').value = 0;
    document.getElementById('emailTarget').textContent = '所有用户 (' + <?php echo $userCount; ?> + '人)';
    document.getElementById('emailModal').style.display = 'flex';
}

async function confirmSendEmail() {
    const uid = parseInt(document.getElementById('emailTargetUid').value);
    const subject = document.getElementById('emailSubject').value.trim();
    const content = document.getElementById('emailContent').value.trim();
    if (!subject || !content) return showToast('请填写标题和内容', 'warning');
    
    const btn = event.target;
    btn.disabled = true; btn.textContent = '发送中...';
    const res = await adminApi('send_email_user', {user_id: uid, subject, content});
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) {
        document.getElementById('emailModal').style.display = 'none';
        document.getElementById('emailSubject').value = '';
        document.getElementById('emailContent').value = '';
    }
    btn.disabled = false; btn.innerHTML = '📤 发送邮件';
}
</script>

<?php include __DIR__ . '/admin_footer.php'; ?>
