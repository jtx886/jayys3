<?php
$pageName = '主题设置';
include __DIR__ . '/admin_header.php';

$theme = Utils::getThemeColor();
$presets = [
    ['#7c3aed', '#a855f7'], // 紫色默认
    ['#ef4444', '#f87171'], // 红色
    ['#ec4899', '#f472b6'], // 粉色
    ['#f97316', '#fb923c'], // 橙色
    ['#eab308', '#facc15'], // 黄色
    ['#10b981', '#34d399'], // 绿色
    ['#14b8a6', '#2dd4bf'], // 青色
    ['#3b82f6', '#60a5fa'], // 蓝色
    ['#6366f1', '#818cf8'], // 靛蓝
    ['#8b5cf6', '#a78bfa'], // 紫色2
    ['#0ea5e9', '#38bdf8'], // 天蓝
    ['#f43f5e', '#fb7185'], // 玫瑰红
];
?>
<div class="admin-content">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
        <div class="panel">
            <div class="panel-header"><div class="panel-title">🎨 网站主题颜色</div></div>
            <div class="panel-body space-y-4">
                <div>
                    <label class="form-label">主色调 (Primary)</label>
                    <div class="color-picker-wrapper">
                        <div class="color-preview" id="primaryPreview" style="background:<?php echo $theme['primary']; ?>;"></div>
                        <input type="color" id="primaryColor" value="<?php echo $theme['primary']; ?>" class="form-input color-picker-input" style="height:44px;padding:4px;cursor:pointer;">
                    </div>
                    <div class="color-presets">
                        <?php foreach ($presets as $p): ?>
                        <div class="color-preset" style="background:linear-gradient(135deg,<?php echo $p[0]; ?>,<?php echo $p[1]; ?>);<?php echo $p[0] === $theme['primary'] ? 'box-shadow:0 0 0 3px rgba(255,255,255,0.2);border:2px solid #fff;' : ''; ?>"
                             onclick="setPreset('<?php echo $p[0]; ?>','<?php echo $p[1]; ?>', this)">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <label class="form-label">辅助色 (Secondary / 渐变)</label>
                    <div class="color-picker-wrapper">
                        <div class="color-preview" id="secondaryPreview" style="background:<?php echo $theme['secondary']; ?>;"></div>
                        <input type="color" id="secondaryColor" value="<?php echo $theme['secondary']; ?>" class="form-input color-picker-input" style="height:44px;padding:4px;cursor:pointer;">
                    </div>
                </div>
                <div style="padding-top:16px;border-top:1px solid var(--border-color);display:flex;gap:12px;">
                    <button class="btn btn-primary" onclick="saveTheme()">💾 保存主题</button>
                    <button class="btn btn-outline" onclick="resetTheme()">↺ 恢复默认</button>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header"><div class="panel-title">👁️ 实时预览</div></div>
            <div class="panel-body">
                <div id="previewArea" style="border-radius:16px;overflow:hidden;border:1px solid var(--border-color);">
                    <div id="previewHeader" style="padding:16px;background:var(--gradient-primary);color:#fff;display:flex;align-items:center;gap:12px;">
                        <div style="width:36px;height:36px;background:rgba(255,255,255,0.2);border-radius:10px;display:flex;align-items:center;justify-content:center;">🎬</div>
                        <div style="font-weight:700;">Jay影视</div>
                        <div style="margin-left:auto;display:flex;gap:8px;">
                            <button style="padding:6px 14px;background:rgba(255,255,255,0.2);border:none;color:#fff;border-radius:8px;cursor:pointer;">登录</button>
                            <button style="padding:6px 14px;background:rgba(255,255,255,0.95);border:none;color:#333;border-radius:8px;cursor:pointer;font-weight:600;">注册</button>
                        </div>
                    </div>
                    <div style="padding:20px;background:#0f0f14;">
                        <div style="padding:16px;border-radius:12px;background:#1a1a24;border:1px solid rgba(255,255,255,0.08);">
                            <div style="display:flex;gap:16px;align-items:center;">
                                <div id="previewTag" style="padding:6px 14px;background:var(--gradient-primary);border-radius:999px;color:#fff;font-size:12px;font-weight:600;">正在热播</div>
                                <div style="color:#9ca3af;font-size:13px;">⭐ 8.7 · 2024 · 动作/奇幻</div>
                            </div>
                            <div style="height:14px;width:180px;background:#fff;border-radius:4px;margin:14px 0;opacity:0.9;"></div>
                            <div style="height:8px;width:100%;background:#fff;opacity:0.1;border-radius:4px;margin:6px 0;"></div>
                            <div style="height:8px;width:85%;background:#fff;opacity:0.1;border-radius:4px;margin:6px 0;"></div>
                            <div style="display:flex;gap:10px;margin-top:16px;">
                                <button id="previewBtn1" style="padding:10px 20px;background:var(--gradient-primary);color:#fff;border:none;border-radius:10px;font-weight:600;cursor:pointer;box-shadow:0 4px 15px rgba(0,0,0,0.2);">▶ 立即播放</button>
                                <button style="padding:10px 20px;background:rgba(255,255,255,0.08);color:#fff;border:1px solid rgba(255,255,255,0.1);border-radius:10px;cursor:pointer;">+ 收藏</button>
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:20px;">
                            <?php for ($i=1;$i<=3;$i++): ?>
                            <div style="aspect-ratio:2/3;border-radius:10px;background:linear-gradient(135deg,#2a2a3a,#1a1a24);overflow:hidden;position:relative;">
                                <div style="position:absolute;top:8px;left:8px;padding:2px 6px;background:rgba(0,0,0,0.7);color:#fbbf24;font-size:10px;border-radius:4px;font-weight:700;">8.<?php echo $i; ?></div>
                                <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#6b7280;font-size:11px;">影视封面</div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div style="padding:14px;background:#1a1a24;border-top:1px solid rgba(255,255,255,0.08);text-align:center;color:#6b7280;font-size:11px;">
                        © <?php echo date('Y'); ?> Jay影视 · 预览模式
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const pInput = document.getElementById('primaryColor');
const sInput = document.getElementById('secondaryColor');
const pPreview = document.getElementById('primaryPreview');
const sPreview = document.getElementById('secondaryPreview');

function applyLive(p, s) {
    pPreview.style.background = p;
    sPreview.style.background = s;
    document.documentElement.style.setProperty('--primary', p);
    document.documentElement.style.setProperty('--secondary', s);
    document.documentElement.style.setProperty('--gradient-primary', `linear-gradient(135deg, ${p} 0%, ${s} 100%)`);
}

pInput.addEventListener('input', e => applyLive(e.target.value, sInput.value));
sInput.addEventListener('input', e => applyLive(pInput.value, e.target.value));

function setPreset(p, s, el) {
    pInput.value = p;
    sInput.value = s;
    applyLive(p, s);
    document.querySelectorAll('.color-preset').forEach(x => {
        x.style.border = '2px solid transparent';
        x.style.boxShadow = '';
    });
    el.style.border = '2px solid #fff';
    el.style.boxShadow = '0 0 0 3px rgba(255,255,255,0.2)';
}

function resetTheme() {
    setPreset('#7c3aed', '#a855f7', document.querySelector('.color-preset'));
}

async function saveTheme() {
    const p = pInput.value;
    const s = sInput.value;
    const res = await adminApi('save_theme', {primary: p, secondary: s});
    showToast(res.message, res.success ? 'success' : 'error');
}
</script>

<?php include __DIR__ . '/admin_footer.php'; ?>
