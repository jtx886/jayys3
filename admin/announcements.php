<?php
$pageName = '公告管理';
include __DIR__ . '/admin_header.php';

$anns = $db->fetchAll("SELECT * FROM announcements ORDER BY id DESC");
?>
<div class="admin-content">
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">📢 公告管理</div>
            <button class="btn btn-primary btn-sm" onclick="annModal()">+ 发布公告</button>
        </div>
        <div style="padding:0 24px 16px;">
            <div style="padding:14px 18px;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.25);border-radius:10px;color:#fbbf24;font-size:13px;">
                💡 公告以弹窗形式在 <b>首页</b> 展示。每次发布新公告，所有用户都会看到，除非用户勾选"不再显示"。
                有多条公告时，只显示 <b>最新</b> 的一条。用户在其他页面不会看到公告弹窗。
            </div>
        </div>
        <div class="panel-body" style="padding:0;">
            <table class="data-table">
                <thead><tr><th>ID</th><th>标题</th><th>内容摘要</th><th>发布时间</th><th>操作</th></tr></thead>
                <tbody>
                    <?php foreach ($anns as $a): ?>
                    <tr>
                        <td style="font-family:monospace;color:var(--text-muted);">#<?php echo $a['id']; ?></td>
                        <td><b style="font-size:15px;"><?php echo htmlspecialchars($a['title']); ?></b></td>
                        <td style="color:var(--text-secondary);max-width:360px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <?php echo htmlspecialchars(mb_substr($a['content'], 0, 60)); ?>
                            <?php echo mb_strlen($a['content']) > 60 ? '...' : ''; ?>
                        </td>
                        <td style="color:var(--text-muted);font-size:13px;"><?php echo date('Y-m-d H:i', strtotime($a['created_at'])); ?></td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <button class="btn btn-sm btn-outline" onclick="annModal(<?php echo $a['id']; ?>, <?php echo htmlspecialchars(json_encode($a), ENT_QUOTES); ?>)">编辑</button>
                                <button class="btn btn-sm btn-danger" onclick="deleteAnn(<?php echo $a['id']; ?>)">删除</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($anns)): ?><tr><td colspan="5" style="text-align:center;padding:48px;color:var(--text-muted);">暂无公告</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="annModal" style="display:none;">
    <div class="modal" style="max-width:620px;">
        <div class="modal-header">
            <div class="modal-title"><span id="annTitle">发布</span>公告</div>
            <button class="modal-close" onclick="document.getElementById('annModal').style.display='none'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="annId" value="0">
            <div class="form-group">
                <label class="form-label">公告标题 *</label>
                <input type="text" id="annTitleInput" class="form-input" maxlength="50" placeholder="请输入公告标题">
            </div>
            <div class="form-group">
                <label class="form-label">公告内容 *（支持换行）</label>
                <textarea id="annContent" class="form-input" rows="8" placeholder="请输入公告内容..." style="resize:vertical;" maxlength="5000"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="document.getElementById('annModal').style.display='none'">取消</button>
            <button class="btn btn-primary" onclick="saveAnn()">💾 保存</button>
        </div>
    </div>
</div>

<script>
function annModal(id, data) {
    const modal = document.getElementById('annModal');
    document.getElementById('annTitle').textContent = id ? '编辑' : '发布';
    if (id) {
        document.getElementById('annId').value = id;
        document.getElementById('annTitleInput').value = data.title;
        document.getElementById('annContent').value = data.content;
    } else {
        document.getElementById('annId').value = 0;
        document.getElementById('annTitleInput').value = '';
        document.getElementById('annContent').value = '';
    }
    modal.style.display = 'flex';
}

async function saveAnn() {
    const id = parseInt(document.getElementById('annId').value);
    const title = document.getElementById('annTitleInput').value.trim();
    const content = document.getElementById('annContent').value.trim();
    if (!title || !content) return showToast('请填写标题和内容', 'warning');
    
    const res = await adminApi('save_announcement', {id, title, content});
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) setTimeout(() => location.reload(), 800);
}

async function deleteAnn(id) {
    if (!confirm('确定删除此公告？')) return;
    const res = await adminApi('delete_announcement', {id});
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) setTimeout(() => location.reload(), 600);
}
</script>

<?php include __DIR__ . '/admin_footer.php'; ?>
