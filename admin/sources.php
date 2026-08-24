<?php
$pageName = '播放源管理';
include __DIR__ . '/admin_header.php';

$sources = $db->fetchAll("SELECT * FROM play_sources ORDER BY is_default DESC, sort_order ASC, id DESC");
?>
<div class="admin-content">
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">📡 播放源管理</div>
            <button class="btn btn-primary btn-sm" onclick="sourceModal()">+ 添加播放源</button>
        </div>
        <div class="panel-body" style="padding:0;">
            <table class="data-table">
                <thead><tr><th>ID</th><th>名称</th><th>API地址</th><th>解析播放器</th><th>排序</th><th>状态</th><th>操作</th></tr></thead>
                <tbody>
                    <?php foreach ($sources as $s): ?>
                    <tr>
                        <td style="font-family:monospace;color:var(--text-muted);">#<?php echo $s['id']; ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <b><?php echo htmlspecialchars($s['name']); ?></b>
                                <?php if ($s['is_default']): ?><span class="badge badge-success">默认</span><?php endif; ?>
                            </div>
                        </td>
                        <td style="color:var(--text-muted);font-size:12px;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($s['api_url']); ?>">
                            <code style="background:rgba(255,255,255,0.05);padding:3px 8px;border-radius:6px;"><?php echo htmlspecialchars($s['api_url']); ?></code>
                        </td>
                        <td style="color:var(--text-muted);font-size:12px;max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($s['parser_url']); ?>">
                            <code style="background:rgba(255,255,255,0.05);padding:3px 8px;border-radius:6px;"><?php echo htmlspecialchars($s['parser_url']); ?></code>
                        </td>
                        <td><?php echo $s['sort_order']; ?></td>
                        <td><?php echo $s['status'] ? '<span class="badge badge-success">启用</span>' : '<span class="badge badge-danger">禁用</span>'; ?></td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <?php if (!$s['is_default']): ?>
                                    <button class="btn btn-sm btn-success" onclick="setDefault(<?php echo $s['id']; ?>)">设为默认</button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline" onclick="sourceModal(<?php echo $s['id']; ?>, <?php echo htmlspecialchars(json_encode($s)); ?>)">编辑</button>
                                <button class="btn btn-sm btn-danger" onclick="deleteSource(<?php echo $s['id']; ?>)">删除</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($sources)): ?><tr><td colspan="7" style="text-align:center;padding:48px;color:var(--text-muted);">暂无播放源，点击"添加播放源"创建</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="srcModal" style="display:none;">
    <div class="modal" style="max-width:560px;">
        <div class="modal-header">
            <div class="modal-title"><span id="srcModalTitle">添加</span>播放源</div>
            <button class="modal-close" onclick="document.getElementById('srcModal').style.display='none'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="srcId" value="0">
            <div class="form-group">
                <label class="form-label">播放源名称 *</label>
                <input type="text" id="srcName" class="form-input" placeholder="例如：默认播放源、备用源 等">
            </div>
            <div class="form-group">
                <label class="form-label">API 地址 * (搜索接口)</label>
                <input type="text" id="srcApi" class="form-input" placeholder="https://api.yyzy-tv.vip/inc/apijson.php">
            </div>
            <div class="form-group">
                <label class="form-label">解析播放器 URL（将拼接真实视频地址使用）</label>
                <input type="text" id="srcParser" class="form-input" placeholder="https://svip.ffzyplay.com/?url=" value="https://svip.ffzyplay.com/?url=">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label class="form-label">排序</label>
                    <input type="number" id="srcSort" class="form-input" value="0">
                </div>
                <div class="form-group">
                    <label class="form-label">状态</label>
                    <select id="srcStatus" class="form-input">
                        <option value="1">启用</option>
                        <option value="0">禁用</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">是否默认</label>
                    <select id="srcDefault" class="form-input">
                        <option value="0">否</option>
                        <option value="1">是</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="document.getElementById('srcModal').style.display='none'">取消</button>
            <button class="btn btn-primary" onclick="saveSource()">💾 保存</button>
        </div>
    </div>
</div>

<script>
function sourceModal(id, data) {
    const modal = document.getElementById('srcModal');
    const titleEl = document.getElementById('srcModalTitle');
    if (id) {
        titleEl.textContent = '编辑';
        document.getElementById('srcId').value = id;
        document.getElementById('srcName').value = data.name;
        document.getElementById('srcApi').value = data.api_url;
        document.getElementById('srcParser').value = data.parser_url || '';
        document.getElementById('srcSort').value = data.sort_order;
        document.getElementById('srcStatus').value = data.status;
        document.getElementById('srcDefault').value = data.is_default;
    } else {
        titleEl.textContent = '添加';
        document.getElementById('srcId').value = 0;
        document.getElementById('srcName').value = '';
        document.getElementById('srcApi').value = 'https://api.yyzy-tv.vip/inc/apijson.php';
        document.getElementById('srcParser').value = 'https://svip.ffzyplay.com/?url=';
        document.getElementById('srcSort').value = '0';
        document.getElementById('srcStatus').value = '1';
        document.getElementById('srcDefault').value = '0';
    }
    modal.style.display = 'flex';
}

async function saveSource() {
    const id = parseInt(document.getElementById('srcId').value);
    const name = document.getElementById('srcName').value.trim();
    const api = document.getElementById('srcApi').value.trim();
    const parser = document.getElementById('srcParser').value.trim();
    const sort = parseInt(document.getElementById('srcSort').value) || 0;
    const status = parseInt(document.getElementById('srcStatus').value);
    const isDefault = parseInt(document.getElementById('srcDefault').value);
    
    if (!name || !api) return showToast('请填写名称和API地址', 'warning');
    
    const res = await adminApi('save_source', {
        id, name, api_url: api, parser_url: parser,
        sort_order: sort, status, is_default: isDefault
    });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) setTimeout(() => location.reload(), 800);
}

async function setDefault(id) {
    const res = await adminApi('set_default_source', {id});
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) setTimeout(() => location.reload(), 600);
}

async function deleteSource(id) {
    if (!confirm('确定删除此播放源？')) return;
    const res = await adminApi('delete_source', {id});
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) setTimeout(() => location.reload(), 600);
}
</script>

<?php include __DIR__ . '/admin_footer.php'; ?>
