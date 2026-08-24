<?php
class Utils {
    public static function ajaxResponse($success, $message = '', $data = []) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function getThemeColor() {
        $db = Database::getInstance();
        $primary = $db->fetchOne("SELECT setting_value FROM site_settings WHERE setting_key = 'theme_color'");
        $secondary = $db->fetchOne("SELECT setting_value FROM site_settings WHERE setting_key = 'theme_color_secondary'");
        return [
            'primary' => $primary ? $primary['setting_value'] : '#7c3aed',
            'secondary' => $secondary ? $secondary['setting_value'] : '#a855f7'
        ];
    }

    public static function getActiveAnnouncement() {
        $db = Database::getInstance();
        return $db->fetchOne("SELECT * FROM announcements ORDER BY id DESC LIMIT 1");
    }

    public static function isAnnouncementDismissed($announcementId) {
        if (!Auth::isLoggedIn()) {
            return isset($_COOKIE['dismissed_ann_' . $announcementId]);
        }
        $db = Database::getInstance();
        $userId = $_SESSION['user_id'];
        $record = $db->fetchOne(
            "SELECT id FROM announcement_dismissed WHERE user_id = ? AND announcement_id = ?",
            [$userId, $announcementId]
        );
        return !empty($record);
    }

    public static function dismissAnnouncement($announcementId) {
        if (!Auth::isLoggedIn()) {
            setcookie('dismissed_ann_' . $announcementId, '1', time() + (86400 * 365), '/');
            return true;
        }
        $db = Database::getInstance();
        $userId = $_SESSION['user_id'];
        try {
            $db->insert('announcement_dismissed', [
                'user_id' => $userId,
                'announcement_id' => $announcementId
            ]);
        } catch (Exception $e) {
            // Already dismissed
        }
        return true;
    }

    public static function saveWatchHistory($mediaId, $mediaType, $title, $poster, $season = 0, $episode = 0, $seconds = 0) {
        if (!Auth::isLoggedIn()) return false;
        $db = Database::getInstance();
        $userId = $_SESSION['user_id'];

        $existing = $db->fetchOne(
            "SELECT id FROM watch_history WHERE user_id = ? AND media_id = ? AND media_type = ? AND season_number = ? AND episode_number = ?",
            [$userId, $mediaId, $mediaType, $season, $episode]
        );

        if ($existing) {
            $db->update('watch_history', [
                'watch_seconds' => $seconds,
                'watched_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$existing['id']]);
        } else {
            $db->insert('watch_history', [
                'user_id' => $userId,
                'media_id' => $mediaId,
                'media_type' => $mediaType,
                'title' => $title,
                'poster' => $poster,
                'season_number' => $season,
                'episode_number' => $episode,
                'watch_seconds' => $seconds
            ]);
        }
        return true;
    }

    public static function toggleFavorite($mediaId, $mediaType, $title, $poster) {
        if (!Auth::isLoggedIn()) return ['success' => false, 'message' => '请先登录'];
        $db = Database::getInstance();
        $userId = $_SESSION['user_id'];

        $existing = $db->fetchOne(
            "SELECT id FROM favorites WHERE user_id = ? AND media_id = ? AND media_type = ?",
            [$userId, $mediaId, $mediaType]
        );

        if ($existing) {
            $db->delete('favorites', 'id = ?', [$existing['id']]);
            return ['success' => true, 'favorited' => false, 'message' => '已取消收藏'];
        } else {
            $db->insert('favorites', [
                'user_id' => $userId,
                'media_id' => $mediaId,
                'media_type' => $mediaType,
                'title' => $title,
                'poster' => $poster
            ]);
            return ['success' => true, 'favorited' => true, 'message' => '收藏成功'];
        }
    }

    public static function isFavorited($mediaId, $mediaType) {
        if (!Auth::isLoggedIn()) return false;
        $db = Database::getInstance();
        $userId = $_SESSION['user_id'];
        $existing = $db->fetchOne(
            "SELECT id FROM favorites WHERE user_id = ? AND media_id = ? AND media_type = ?",
            [$userId, $mediaId, $mediaType]
        );
        return !empty($existing);
    }

    public static function formatDuration($minutes) {
        if (!$minutes) return '';
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        if ($hours > 0) {
            return $hours . '小时' . ($mins > 0 ? $mins . '分钟' : '');
        }
        return $mins . '分钟';
    }

    public static function formatDate($dateStr) {
        if (!$dateStr) return '';
        return date('Y-m-d', strtotime($dateStr));
    }

    public static function getStarsHtml($rating) {
        $rating = $rating / 2;
        $full = floor($rating);
        $half = $rating - $full >= 0.5;
        $html = '';
        for ($i = 0; $i < 5; $i++) {
            if ($i < $full) {
                $html .= '<span class="star star-full">★</span>';
            } elseif ($i == $full && $half) {
                $html .= '<span class="star star-half">★</span>';
            } else {
                $html .= '<span class="star star-empty">★</span>';
            }
        }
        return $html;
    }

    public static function getPlaySource($id = null) {
        $db = Database::getInstance();
        if ($id) {
            return $db->fetchOne("SELECT * FROM play_sources WHERE id = ? AND status = 1", [$id]);
        }
        return $db->fetchOne("SELECT * FROM play_sources WHERE status = 1 ORDER BY is_default DESC, sort_order ASC LIMIT 1");
    }

    public static function getAllPlaySources() {
        $db = Database::getInstance();
        return $db->fetchAll("SELECT * FROM play_sources WHERE status = 1 ORDER BY is_default DESC, sort_order ASC");
    }

    public static function searchPlaySource($keyword) {
        $source = self::getPlaySource();
        if (!$source) return null;
        
        $url = $source['api_url'] . '?' . http_build_query([
            'wd' => $keyword
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        return $data;
    }
}
?>
