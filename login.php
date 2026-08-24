<?php
require_once __DIR__ . '/config.php';

$theme = Utils::getThemeColor();
$tab = $_GET['tab'] ?? 'login';
$loginMessage = $_SESSION['login_message'] ?? '';
$banMessage = $_SESSION['ban_message'] ?? '';
unset($_SESSION['login_message'], $_SESSION['ban_message']);

if (Auth::isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="<?php echo $theme['primary']; ?>">
    <title>登录/注册 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0">
    <style>
        :root {
            --primary: <?php echo $theme['primary']; ?>;
            --secondary: <?php echo $theme['secondary']; ?>;
            --gradient-primary: linear-gradient(135deg, <?php echo $theme['primary']; ?> 0%, <?php echo $theme['secondary']; ?> 100%);
        }
    </style>
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <a href="index.php" class="auth-logo">
                <div class="logo-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
                </div>
                <span class="logo-text">Jay影视</span>
            </a>
            <p class="auth-subtitle">欢迎回来，发现精彩影视世界</p>
        </div>

        <div class="auth-tabs">
            <div class="auth-tab <?php echo $tab === 'login' ? 'active' : ''; ?>" data-tab="login">登录</div>
            <div class="auth-tab <?php echo $tab === 'register' ? 'active' : ''; ?>" data-tab="register">注册</div>
        </div>

        <?php if ($loginMessage): ?>
        <div class="form-warning" style="padding:12px 16px;background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);border-radius:12px;color:#fbbf24;font-size:13px;margin-bottom:18px;display:flex;align-items:center;gap:8px;">
            <span>⚠️</span><span><?php echo htmlspecialchars($loginMessage); ?></span>
        </div>
        <?php endif; ?>

        <?php if ($banMessage): ?>
        <div class="form-error" id="banMsgBox">
            <span>🚫</span><span><?php echo htmlspecialchars($banMessage); ?></span>
        </div>
        <?php endif; ?>

        <!-- 登录表单 -->
        <form id="loginForm" class="auth-form" style="<?php echo $tab === 'login' ? '' : 'display:none;'; ?>">
            <div class="form-group">
                <label class="form-label">用户名 / 邮箱</label>
                <input type="text" class="form-input" id="loginUsername" placeholder="请输入用户名或邮箱" autocomplete="username">
            </div>
            <div class="form-group">
                <label class="form-label">密码</label>
                <input type="password" class="form-input" id="loginPassword" placeholder="请输入密码" autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                立即登录
            </button>
        </form>

        <!-- 注册表单 -->
        <form id="registerForm" class="auth-form" style="<?php echo $tab === 'register' ? '' : 'display:none;'; ?>">
            <div class="form-group">
                <label class="form-label">用户名</label>
                <input type="text" class="form-input" id="regUsername" placeholder="2-20个字符，支持中文/字母/数字/下划线" maxlength="20" autocomplete="username">
            </div>
            <div class="form-group">
                <label class="form-label">邮箱</label>
                <input type="email" class="form-input" id="regEmail" placeholder="请输入您的邮箱地址" autocomplete="email">
            </div>
            <div class="form-group">
                <label class="form-label">邮箱验证码</label>
                <div class="form-input-row">
                    <input type="text" class="form-input" id="regCode" placeholder="请输入6位验证码" maxlength="6" style="letter-spacing:4px;text-align:center;font-weight:700;">
                    <button type="button" class="btn btn-outline" id="sendCodeBtn">获取验证码</button>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">密码</label>
                <input type="password" class="form-input" id="regPassword" placeholder="6-32个字符" autocomplete="new-password" maxlength="32">
            </div>
            <div class="form-group">
                <label class="form-label">确认密码</label>
                <input type="password" class="form-input" id="regConfirmPassword" placeholder="再次输入密码" autocomplete="new-password" maxlength="32">
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                创建账号
            </button>
        </form>

        <div class="auth-footer">
            <span id="loginTip">还没有账号？<a href="javascript:;" onclick="switchTab('register')">立即注册</a></span>
            <span id="registerTip" style="display:none;">已有账号？<a href="javascript:;" onclick="switchTab('login')">返回登录</a></span>
        </div>
    </div>
</div>

<!-- Toast 容器 -->
<div class="toast-container" id="toastContainer"></div>

<script>
function showToast(message, type = 'info', duration = 3000) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast ' + type;
    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
    toast.innerHTML = '<span class="toast-icon">' + icons[type] + '</span><span>' + message + '</span>';
    container.appendChild(toast);
    setTimeout(() => { toast.style.animation = 'fadeIn 0.2s ease reverse'; setTimeout(() => toast.remove(), 200); }, duration);
}

// Tab 切换
function switchTab(tab) {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const loginTab = document.querySelector('[data-tab="login"]');
    const registerTab = document.querySelector('[data-tab="register"]');
    const loginTip = document.getElementById('loginTip');
    const registerTip = document.getElementById('registerTip');
    
    if (tab === 'login') {
        loginForm.style.display = '';
        registerForm.style.display = 'none';
        loginTab.classList.add('active');
        registerTab.classList.remove('active');
        loginTip.style.display = '';
        registerTip.style.display = 'none';
    } else {
        loginForm.style.display = 'none';
        registerForm.style.display = '';
        loginTab.classList.remove('active');
        registerTab.classList.add('active');
        loginTip.style.display = 'none';
        registerTip.style.display = '';
    }
}

document.querySelectorAll('.auth-tab').forEach(tab => {
    tab.addEventListener('click', () => switchTab(tab.dataset.tab));
});

// 发送验证码
const sendCodeBtn = document.getElementById('sendCodeBtn');
let countdown = 0;
sendCodeBtn.addEventListener('click', async () => {
    if (countdown > 0) return;
    const email = document.getElementById('regEmail').value.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showToast('请输入正确的邮箱地址', 'warning');
        return;
    }
    
    sendCodeBtn.disabled = true;
    sendCodeBtn.textContent = '发送中...';
    
    try {
        const res = await fetch('ajax/send_code.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({email, purpose: 'register'})
        });
        const data = await res.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            countdown = 60;
            const timer = setInterval(() => {
                countdown--;
                sendCodeBtn.textContent = countdown + 's 后重发';
                if (countdown <= 0) {
                    clearInterval(timer);
                    sendCodeBtn.disabled = false;
                    sendCodeBtn.textContent = '获取验证码';
                }
            }, 1000);
        } else {
            showToast(data.message, 'error');
            sendCodeBtn.disabled = false;
            sendCodeBtn.textContent = '获取验证码';
        }
    } catch (e) {
        showToast('网络错误，请稍后重试', 'error');
        sendCodeBtn.disabled = false;
        sendCodeBtn.textContent = '获取验证码';
    }
});

// 登录
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const username = document.getElementById('loginUsername').value.trim();
    const password = document.getElementById('loginPassword').value;
    
    if (!username || !password) {
        showToast('请填写用户名和密码', 'warning');
        return;
    }
    
    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = '登录中...';
    
    try {
        const res = await fetch('ajax/login.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({username, password})
        });
        const data = await res.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.href = data.data.redirect, 800);
        } else {
            showToast(data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg> 立即登录';
        }
    } catch (e) {
        showToast('网络错误', 'error');
        btn.disabled = false;
    }
});

// 注册
document.getElementById('registerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const username = document.getElementById('regUsername').value.trim();
    const email = document.getElementById('regEmail').value.trim();
    const code = document.getElementById('regCode').value.trim();
    const password = document.getElementById('regPassword').value;
    const confirmPassword = document.getElementById('regConfirmPassword').value;
    
    if (!username) return showToast('请输入用户名', 'warning');
    if (username.length < 2 || username.length > 20) return showToast('用户名长度应为2-20个字符', 'warning');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return showToast('请输入正确的邮箱', 'warning');
    if (code.length !== 6) return showToast('请输入6位验证码', 'warning');
    if (password.length < 6 || password.length > 32) return showToast('密码长度应为6-32个字符', 'warning');
    if (password !== confirmPassword) return showToast('两次密码不一致', 'warning');
    
    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = '注册中...';
    
    try {
        const res = await fetch('ajax/register.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({username, email, code, password, confirm_password: confirmPassword})
        });
        const data = await res.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.href = data.data.redirect, 800);
        } else {
            showToast(data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg> 创建账号';
        }
    } catch (e) {
        showToast('网络错误', 'error');
        btn.disabled = false;
    }
});
</script>
</body>
</html>
