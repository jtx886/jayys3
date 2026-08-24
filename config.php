<?php
// 数据库配置 - 使用SQLite兼容免费服务器
define('DB_PATH', __DIR__ . '/data/jayvideo.db');

// TMDB API 配置
define('TMDB_API_KEY', 'cb44223c5dee5676ed3a839f42ed27e3');
define('TMDB_API_TOKEN', 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJjYjQ0MjIzYzVlZTU2NzZlZDNhOTNmNDJlZDI3ZTMzMyIsIm5iZiI6MTczMjkyNzg1MS42NzA3MTMsInN1YiI6IjY5OTNjYjBkZDI3MzNmOGExNjljYjBkZCIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.95UWM3wql05P9SnJf0Py9NNjMikjsXSNGX7a6i6t4qs');
define('TMDB_BASE_URL', 'https://api.themoviedb.org/3');
define('TMDB_IMG_BASE', 'https://image.tmdb.org/t/p');

// SMTP 邮箱配置
define('SMTP_HOST', 'smtp.163.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'jtxnb886@163.com');
define('SMTP_PASS', 'FLLRDtadYAfGXp9Y');
define('SMTP_FROM', 'jtxnb886@163.com');
define('SMTP_FROM_NAME', 'Jay影视');

// 播放源API
define('PLAY_SOURCE_API', 'https://api.yyzy-tv.vip/inc/apijson.php');

// 解析播放器
define('VIDEO_PARSER', 'https://svip.ffzyplay.com/?url=');

// 管理员账号
define('ADMIN_USERNAME', '杰同学');
define('ADMIN_PASSWORD', '101113');

// 网站设置
define('SITE_NAME', 'Jay影视');
define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']);

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 开始会话
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 自动加载类
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
?>
