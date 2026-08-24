<?php
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $dbDir = dirname(DB_PATH);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0777, true);
            }
            
            $this->pdo = new PDO('sqlite:' . DB_PATH);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->exec("PRAGMA journal_mode=WAL");
            $this->pdo->exec("PRAGMA foreign_keys=ON");
            
            $this->initTables();
        } catch (PDOException $e) {
            die("数据库连接失败: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    private function initTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            avatar TEXT DEFAULT '',
            is_banned INTEGER DEFAULT 0,
            ban_reason TEXT DEFAULT '',
            ban_start_time DATETIME,
            ban_end_time DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS play_sources (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            api_url TEXT NOT NULL,
            parser_url TEXT DEFAULT '',
            is_default INTEGER DEFAULT 0,
            sort_order INTEGER DEFAULT 0,
            status INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS announcements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            created_by INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS announcement_dismissed (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            announcement_id INTEGER NOT NULL,
            dismissed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, announcement_id)
        );

        CREATE TABLE IF NOT EXISTS feedbacks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            status INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS feedback_replies (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            feedback_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            content TEXT NOT NULL,
            is_admin INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS feedback_likes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            feedback_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(feedback_id, user_id)
        );

        CREATE TABLE IF NOT EXISTS watch_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            media_id INTEGER NOT NULL,
            media_type TEXT NOT NULL,
            title TEXT NOT NULL,
            poster TEXT DEFAULT '',
            season_number INTEGER DEFAULT 0,
            episode_number INTEGER DEFAULT 0,
            watch_seconds INTEGER DEFAULT 0,
            watched_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, media_id, media_type, season_number, episode_number)
        );

        CREATE TABLE IF NOT EXISTS favorites (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            media_id INTEGER NOT NULL,
            media_type TEXT NOT NULL,
            title TEXT NOT NULL,
            poster TEXT DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, media_id, media_type)
        );

        CREATE TABLE IF NOT EXISTS email_codes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL,
            code TEXT NOT NULL,
            purpose TEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            used INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS site_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT NOT NULL UNIQUE,
            setting_value TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        ";

        $this->pdo->exec($sql);

        // 初始化默认播放源
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM play_sources");
        if ($stmt->fetchColumn() == 0) {
            $this->pdo->exec("INSERT INTO play_sources (name, api_url, parser_url, is_default, sort_order) VALUES 
                ('默认播放源', 'https://api.yyzy-tv.vip/inc/apijson.php', 'https://svip.ffzyplay.com/?url=', 1, 1)");
        }

        // 初始化默认主题设置
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM site_settings WHERE setting_key = 'theme_color'");
        if ($stmt->fetchColumn() == 0) {
            $this->pdo->exec("INSERT INTO site_settings (setting_key, setting_value) VALUES 
                ('theme_color', '#7c3aed'),
                ('theme_color_secondary', '#a855f7')");
        }
    }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function insert($table, $data) {
        $keys = array_keys($data);
        $placeholders = ':' . implode(', :', $keys);
        $sql = "INSERT INTO $table (" . implode(', ', $keys) . ") VALUES ($placeholders)";
        $this->query($sql, $data);
        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach (array_keys($data) as $key) {
            $set[] = "$key = :$key";
        }
        // 规范化WHERE参数：若where使用?占位符+whereParams是数字索引，转换为命名参数
        $normalizedParams = $data;
        $normalizedWhere = $where;

        // 查找?并替换为:where_0, :where_1
        if (strpos($where, '?') !== false && !empty($whereParams)) {
            $i = 0;
            $offset = 0;
            foreach ($whereParams as $wpk => $wpv) {
                // 只处理数字键的位置参数，跳过已经命名的
                if (is_int($wpk)) {
                    $name = "where_$i";
                    $pos = strpos($normalizedWhere, '?', $offset);
                    if ($pos !== false) {
                        $normalizedWhere = substr_replace($normalizedWhere, ":$name", $pos, 1);
                        $offset = $pos + strlen(":$name");
                    }
                    $normalizedParams[$name] = $wpv;
                    $i++;
                } else {
                    // 已经是命名参数，原样合并
                    $normalizedParams[$wpk] = $wpv;
                }
            }
        } elseif (!empty($whereParams)) {
            // where不含?，认为已用命名参数，合并whereParams (支持直接传冒号或不带冒号键名)
            foreach ($whereParams as $k => $v) {
                $realKey = ltrim($k, ':');
                $normalizedParams[$realKey] = $v;
            }
        }

        $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE $normalizedWhere";
        return $this->query($sql, $normalizedParams)->rowCount();
    }

    public function delete($table, $where, $params = []) {
        // 同样规范化where参数
        $normalizedParams = [];
        $normalizedWhere = $where;
        if (strpos($where, '?') !== false && !empty($params)) {
            $i = 0;
            $offset = 0;
            foreach ($params as $pk => $pv) {
                if (is_int($pk)) {
                    $name = "where_$i";
                    $pos = strpos($normalizedWhere, '?', $offset);
                    if ($pos !== false) {
                        $normalizedWhere = substr_replace($normalizedWhere, ":$name", $pos, 1);
                        $offset = $pos + strlen(":$name");
                    }
                    $normalizedParams[$name] = $pv;
                    $i++;
                } else {
                    $k = ltrim($pk, ':');
                    $normalizedParams[$k] = $pv;
                }
            }
        } elseif (!empty($params)) {
            foreach ($params as $k => $v) {
                $realKey = ltrim($k, ':');
                $normalizedParams[$realKey] = $v;
            }
        }
        $sql = "DELETE FROM $table WHERE $normalizedWhere";
        return $this->query($sql, $normalizedParams)->rowCount();
    }
}
?>
