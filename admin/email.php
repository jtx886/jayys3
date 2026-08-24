<?php
$pageName = '邮件通知';
include __DIR__ . '/admin_header.php';
$userCount = $db->fetchOne("SELECT COUNT(*) as c FROM users")['c'];
$usersList = $db->fetchAll("SELECT id, username, email FROM users ORDER BY id DESC LIMIT 100");
?>
<div class="admin-content">
    <div class="panel" style="max-width:800px;margin:0 auto;">
        <div class="panel-header">
            <div class="panel-title">📧 邮件通知系统</div>
            <div style="font-size:13px;color:var(--text-muted);">
                SMTP: <code style="background:rgba(255,255,255,0.05);padding:3px 8px;border-radius:6px;"><?php echo SMTP_HOST; ?>:<?php echo SMTP_PORT; ?></code>
                · 发件人：<b><?php echo htmlspecialchars(SMTP_FROM_NAME); ?></b>
            </div>
        </div>
        <div class="panel-body">
            <div class="form-group">
                <label class="form-label">收件人 *</label>
                <select id="emailTo" class="form-input" onchange="document.getElementById('emailCustom').style.display=this.value==='custom'?'':'none'">
                    <option value="all">📨 发送给所有用户 (共 <?php echo $userCount; ?> 人)</option>
                    <?php foreach ($usersList as $u): ?>
                        <option value="u_<?php echo $u['id']; ?>">👤 <?php echo htmlspecialchars($u['username']); ?> - <?php echo htmlspecialchars($u['email']); ?></option>
                    <?php endforeach; ?>
                    <option value="custom">✏️ 自定义邮箱地址</option>
                </select>
            </div>
            <div class="form-group" id="emailCustom" style="display:none;">
                <label class="form-label">自定义收件邮箱（多个用逗号分隔）</label>
                <input type="text" id="emailCustomTo" class="form-input" placeholder="a@example.com, b@example.com">
            </div>
            <div class="form-group">
                <label class="form-label">邮件标题 *</label>
                <input type="text" id="emailSubject" class="form-input" placeholder="请输入邮件标题">
            </div>
            <div class="form-group">
                <label class="form-label">邮件内容 *（支持换行，自动转HTML）</label>
                <textarea id="emailContent" class="form-input" rows="10" placeholder="请输入邮件内容，支持换行符..." style="resize:vertical;"></textarea>
            </div>
            <div style="text-align:right;">
                <button class="btn btn-primary btn-lg" onclick="sendEmail()">📤 立即发送</button>
            </div>
        </div>
    </div>
</div>

<script>
async function sendEmail() {
    const toVal = document.getElementById('emailTo').value;
    const subject = document.getElementById('emailSubject').value.trim();
    const content = document.getElementById('emailContent').value.trim();
    
    if (!subject || !content) return showToast('请填写标题和内容', 'warning');
    
    const html = '<div style="max-width:600px;margin:0 auto;padding:20px;font-family:sans-serif;color:#333;">' +
        '<div style="padding:24px;background:linear-gradient(135deg,#7c3aed,#a855f7);color:#fff;border-radius:12px 12px 0 0;">' +
        '<div style="display:flex;align-items:center;gap:14px;"><div style="width:48px;height:48px;background:rgba(255,255,255,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;">📧</div>' +
        '<div><h2 style="margin:0;font-size:20px;">' + <?php echo json_encode(SITE_NAME); ?> + ' 系统通知</h2>' +
        '<div style="font-size:13px;opacity:0.85;">来自管理员的重要消息</div></div></div></div>' +
        '<div style="padding:28px;background:#fff;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 12px 12px;line-height:1.8;font-size:14px;">' +
        content.replace(/\n/g, '<br>') +
        '<div style="margin-top:28px;padding-top:16px;border-top:1px solid #e5e7eb;color:#6b7280;font-size:12px;line-height:1.6;">' +
        '此邮件由系统自动发送，请勿直接回复。如果您有任何问题，请通过网站反馈功能联系。<br>' +
        '© ' + new Date().getFullYear() + ' ' + <?php echo json_encode(SITE_NAME); ?> + ' 版权所有' +
        '</div></div></div>';
    
    const btn = event.target;
    btn.disabled = true; btn.textContent = '发送中...';
    
    try {
        if (toVal === 'all') {
            const res = await adminApi('send_email_user', {user_id: 0, subject, content});
            showToast(res.message, res.success ? 'success' : 'error');
        } else if (toVal === 'custom') {
            const custom = document.getElementById('emailCustomTo').value.trim();
            if (!custom) return showToast('请填写收件邮箱', 'warning');
            const list = custom.split(',').map(s => s.trim()).filter(Boolean);
            let sent = 0;
            for (const addr of list) {
                const ok = await window.sendDirectEmail(addr, subject, html);
                if (ok) sent++;
            }
            showToast(`已成功发送 ${sent}/${list.length} 封邮件`, sent > 0 ? 'success' : 'error');
        } else {
            const uid = parseInt(toVal.substring(2));
            const res = await adminApi('send_email_user', {user_id: uid, subject, content});
            showToast(res.message, res.success ? 'success' : 'error');
        }
    } catch (e) {
        showToast('发送失败', 'error');
    }
    
    btn.disabled = false; btn.innerHTML = '📤 立即发送';
}

// 自定义邮箱直接发送
window.sendDirectEmail = async function(addr, subject, html) {
    // 调用ajax发送（复用API逻辑）
    const form = new FormData();
    form.append('direct_to', addr);
    form.append('subject', subject);
    form.append('html', html);
    try {
        const res = await fetch('ajax_direct_email.php', {
            method: 'POST',
            body: form
        });
        const j = await res.json();
        return j.success;
    } catch (e) {
        return false;
    }
};
</script>

<?php include __DIR__ . '/admin_footer.php'; ?>
