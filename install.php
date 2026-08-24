<?php
// Jay影视 - 安装/初始化检查
require_once __DIR__ . '/config.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

$tests = [];

// 1. 数据库
$tests['数据库连接'] = true;

// 2. 测试数据表
foreach (['users','play_sources','announcements','feedbacks','watch_history','favorites','email_codes','site_settings'] as $t) {
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$t'");
    $tests["数据表 $t"] = (bool)$stmt->fetch();
}

// 3. 目录写入权限
$tests['data目录可写'] = is_writable(__DIR__ . '/data');
$tests['data/avatars目录可写'] = is_writable(__DIR__ . '/data/avatars');

// 4. 默认数据检查
$src = $db->fetchOne("SELECT COUNT(*) as c FROM play_sources");
$tests['播放源初始化'] = ($src['c'] > 0);
$theme = $db->fetchAll("SELECT * FROM site_settings WHERE setting_key LIKE 'theme%'");
$tests['主题配置初始化'] = (count($theme) > 0);

// 5. curl扩展检查
$tests['curl扩展'] = function_exists('curl_init');

// 6. pdo_sqlite
$tests['PDO_SQLite扩展'] = in_array('sqlite', PDO::getAvailableDrivers());

// 7. mail函数（或SMTP可用）
$tests['mail函数或socket'] = function_exists('mail') || function_exists('fsockopen');

// 8. GD
$tests['GD扩展'] = extension_loaded('gd');

// 9. 会话
$tests['Session支持'] = function_exists('session_start');

$allPassed = true;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jay影视 - 系统检测</title>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            font-family:-apple-system,"PingFang SC","Microsoft YaHei",sans-serif;
            background:linear-gradient(135deg,#0f0f14 0%,#1a1030 100%);
            min-height:100vh;
            padding:40px 20px;
            color:#fff;
        }
        .wrap { max-width:640px; margin:0 auto; }
        .card {
            background:rgba(255,255,255,0.06);
            backdrop-filter:blur(20px);
            border:1px solid rgba(255,255,255,0.08);
            border-radius:20px;
            padding:32px;
            box-shadow:0 20px 60px rgba(0,0,0,0.4);
        }
        .header {
            text-align:center;
            margin-bottom:28px;
        }
        .logo {
            width:64px;height:64px;
            margin:0 auto 14px;
            background:linear-gradient(135deg,#7c3aed,#a855f7);
            border-radius:18px;
            display:flex;align-items:center;justify-content:center;
            font-size:28px;
            box-shadow:0 8px 24px rgba(124,58,237,0.4);
        }
        h1 { font-size:24px;margin-bottom:6px; }
        .subtitle { color:#9ca3af;font-size:14px; }
        .test-row {
            display:flex;justify-content:space-between;align-items:center;
            padding:12px 16px;margin:6px 0;
            background:rgba(255,255,255,0.04);
            border-radius:10px;
            font-size:14px;
        }
        .pass { color:#10b981;font-weight:600; }
        .fail { color:#ef4444;font-weight:600; }
        .summary {
            margin-top:20px;padding:16px;border-radius:12px;text-align:center;
            background:rgba(255,255,255,0.05);
            border:1px solid rgba(255,255,255,0.08);
        }
        .btn {
            display:block;margin:20px auto 0;text-align:center;
            padding:14px 28px;border-radius:12px;
            background:linear-gradient(135deg,#7c3aed,#a855f7);color:#fff;
            font-weight:700;text-decoration:none;font-size:15px;
            box-shadow:0 6px 20px rgba(124,58,237,0.35);
            border:none;cursor:pointer;
        }
        .admin-info {
            margin-top:24px;padding:18px;background:rgba(16,185,129,0.08);
            border:1px solid rgba(16,185,129,0.25);border-radius:14px;
        }
        .admin-info h3 { font-size:16px;margin-bottom:8px;color:#10b981; }
        .admin-info p { font-size:13px;line-height:1.7;color:#9ca3af; }
        .admin-info code {
            background:rgba(255,255,255,0.08);padding:3px 8px;border-radius:6px;
            color:#fff;font-family:monospace;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="header">
                <div class="logo">🎬</div>
                <h1>Jay影视 安装向导</h1>
                <p class="subtitle">系统环境检测与初始化</p>
            </div>

            <?php foreach ($tests as $name => $ok): ?>
                <?php if ($ok === false) $allPassed = false; ?>
                <div class="test-row">
                    <span><?php echo htmlspecialchars($name); ?></span>
                    <span class="<?php echo $ok ? 'pass' : 'fail'; ?>">
                        <?php echo $ok ? '✅ 通过' : '❌ 未通过'; ?>
                    </span>
                </div>
            <?php endforeach; ?>

            <div class="summary">
                <?php if ($allPassed): ?>
                    <div class="pass" style="font-size:17px;font-weight:700;">🎉 恭喜！所有环境检测通过</div>
                    <div style="color:#9ca3af;font-size:13px;margin-top:6px;">系统已准备就绪，您可以开始使用 Jay影视 了！</div>
                <?php else: ?>
                    <div class="fail" style="font-size:17px;font-weight:700;">⚠️ 部分检测未通过</div>
                    <div style="color:#9ca3af;font-size:13px;margin-top:6px;">请安装/开启对应扩展后重试。部分非核心功能（如邮件）可先忽略。</div>
                <?php endif; ?>
            </div>

            <div class="admin-info">
                <h3>🛡️ 管理后台账号</h3>
                <p>
                    用户名：<code>杰同学</code><br>
                    密&nbsp;&nbsp;&nbsp;&nbsp;码：<code>101113</code><br>
                    登录地址：<code>admin/index.php</code> 或登录后点右上角开发者徽章
                </p>
            </div>

            <a href="index.php" class="btn">🚀 进入网站首页</a>
        </div>
    </div>
</body>
</html>
