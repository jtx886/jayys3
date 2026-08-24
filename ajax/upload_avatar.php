<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if (!Auth::isLoggedIn()) {
    Utils::ajaxResponse(false, '请先登录');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Utils::ajaxResponse(false, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);
$avatar = $input['avatar'] ?? '';

if (!$avatar || !preg_match('#^data:image/[^;]+;base64,#', $avatar)) {
    Utils::ajaxResponse(false, '无效的图片数据');
}

// 保存为本地文件（或直接存base64到数据库，兼容免费空间）
$avatarDir = __DIR__ . '/../data/avatars';
if (!is_dir($avatarDir)) {
    mkdir($avatarDir, 0777, true);
}

// 提取数据
$imageData = preg_replace('#^data:image/[^;]+;base64,#', '', $avatar);
$imageData = base64_decode($imageData, true);
if (!$imageData) {
    Utils::ajaxResponse(false, '图片解码失败');
}

// 检测文件类型
$finfo = finfo_open();
$mimeType = finfo_buffer($finfo, $imageData, FILEINFO_MIME_TYPE);
finfo_close($finfo);
if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
    Utils::ajaxResponse(false, '仅支持JPG/PNG/GIF/WEBP格式');
}

$extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
$ext = $extMap[$mimeType];

// 使用文件名防止泄露路径：直接存数据库base64较短版本，或存相对路径
$userId = $_SESSION['user_id'];
$fileName = $userId . '_' . time() . '.' . $ext;
$filePath = $avatarDir . '/' . $fileName;

if (file_put_contents($filePath, $imageData) === false) {
    Utils::ajaxResponse(false, '保存图片失败，请检查目录权限');
}

// 更新数据库
$db = Database::getInstance();
$relativePath = 'data/avatars/' . $fileName;
$db->update('users', ['avatar' => $relativePath, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$userId]);
$_SESSION['user_info']['avatar'] = $relativePath;

Utils::ajaxResponse(true, '头像上传成功', ['avatar' => $relativePath]);
?>
