<?php
class Auth {
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public static function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            $_SESSION['login_message'] = '需要登录才可以观看哦，如没有账号请注册！';
            header('Location: login.php');
            exit;
        }
        
        // 检查是否被封禁
        $user = self::getCurrentUser();
        if ($user && $user['is_banned']) {
            // 检查是否已过期
            if ($user['ban_end_time'] && strtotime($user['ban_end_time']) <= time()) {
                $db = Database::getInstance();
                $db->update('users', [
                    'is_banned' => 0,
                    'ban_reason' => '',
                    'ban_start_time' => null,
                    'ban_end_time' => null
                ], 'id = ?', [$user['id']]);
                $_SESSION['user_info']['is_banned'] = 0;
            } else {
                session_destroy();
                $_SESSION['ban_message'] = '您的账号已被封禁：' . $user['ban_reason'] . '，解封时间：' . $user['ban_end_time'];
                header('Location: login.php');
                exit;
            }
        }
    }

    public static function requireAdmin() {
        if (!self::isAdmin()) {
            header('Location: ../login.php');
            exit;
        }
    }

    public static function getCurrentUser() {
        if (!self::isLoggedIn()) return null;
        if (isset($_SESSION['user_info'])) return $_SESSION['user_info'];
        
        $db = Database::getInstance();
        $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
        $_SESSION['user_info'] = $user;
        return $user;
    }

    public static function login($username, $password) {
        $db = Database::getInstance();
        
        // 检查管理员登录
        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            $_SESSION['user_id'] = 0;
            $_SESSION['username'] = ADMIN_USERNAME;
            $_SESSION['is_admin'] = true;
            $_SESSION['user_info'] = [
                'id' => 0,
                'username' => ADMIN_USERNAME,
                'email' => SMTP_FROM,
                'avatar' => '',
                'is_banned' => 0
            ];
            return true;
        }

        // 普通用户登录
        $user = $db->fetchOne("SELECT * FROM users WHERE username = ? OR email = ?", [$username, $username]);
        
        if (!$user) return false;
        if (!password_verify($password, $user['password'])) return false;

        // 检查封禁
        if ($user['is_banned']) {
            if ($user['ban_end_time'] && strtotime($user['ban_end_time']) <= time()) {
                $db->update('users', [
                    'is_banned' => 0,
                    'ban_reason' => '',
                    'ban_start_time' => null,
                    'ban_end_time' => null
                ], 'id = ?', [$user['id']]);
                $user['is_banned'] = 0;
            } else {
                $_SESSION['ban_message'] = '您的账号已被封禁：' . $user['ban_reason'] . '，解封时间：' . $user['ban_end_time'];
                return false;
            }
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = false;
        $_SESSION['user_info'] = $user;
        
        return true;
    }

    public static function register($username, $email, $password, $code) {
        // 验证邮箱验证码
        if (!Email::verifyCode($email, $code, 'register')) {
            return ['success' => false, 'message' => '邮箱验证码错误或已过期'];
        }

        $db = Database::getInstance();

        // 检查用户名是否存在
        $exists = $db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
        if ($exists) {
            return ['success' => false, 'message' => '用户名已存在'];
        }

        // 检查邮箱是否存在
        $exists = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($exists) {
            return ['success' => false, 'message' => '邮箱已被注册'];
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $userId = $db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword
        ]);

        return ['success' => true, 'user_id' => $userId];
    }

    public static function logout() {
        session_unset();
        session_destroy();
    }

    public static function checkCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
?>
