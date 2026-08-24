<?php
class TMDB {
    private static function request($endpoint, $params = []) {
        $params['api_key'] = TMDB_API_KEY;
        $params['language'] = 'zh-CN';
        $params['region'] = 'CN';
        
        $url = TMDB_BASE_URL . $endpoint . '?' . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . TMDB_API_TOKEN,
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) return null;
        
        $data = json_decode($response, true);
        return $data ?? null;
    }

    public static function getTrending($mediaType = 'all', $timeWindow = 'week', $page = 1) {
        $cacheKey = "trending_{$mediaType}_{$timeWindow}_{$page}";
        return self::request("/trending/{$mediaType}/{$timeWindow}", ['page' => $page]);
    }

    public static function getPopular($mediaType = 'movie', $page = 1) {
        return self::request("/{$mediaType}/popular", ['page' => $page]);
    }

    public static function getTopRated($mediaType = 'movie', $page = 1) {
        return self::request("/{$mediaType}/top_rated", ['page' => $page]);
    }

    public static function getNowPlaying($page = 1) {
        return self::request("/movie/now_playing", ['page' => $page]);
    }

    public static function getUpcoming($page = 1) {
        return self::request("/movie/upcoming", ['page' => $page]);
    }

    public static function getAiringToday($page = 1) {
        return self::request("/tv/airing_today", ['page' => $page]);
    }

    public static function getOnTheAir($page = 1) {
        return self::request("/tv/on_the_air", ['page' => $page]);
    }

    public static function getDetail($mediaType, $id) {
        $result = self::request("/{$mediaType}/{$id}", [
            'append_to_response' => 'credits,videos,images,similar,keywords,external_ids'
        ]);
        return $result;
    }

    public static function getSeasonDetail($tvId, $seasonNumber) {
        return self::request("/tv/{$tvId}/season/{$seasonNumber}", [
            'append_to_response' => 'videos,images,credits'
        ]);
    }

    public static function getEpisodeDetail($tvId, $seasonNumber, $episodeNumber) {
        return self::request("/tv/{$tvId}/season/{$seasonNumber}/episode/{$episodeNumber}", [
            'append_to_response' => 'videos,images,credits'
        ]);
    }

    public static function getEpisodeGroups($tvId) {
        return self::request("/tv/{$tvId}/episode_groups");
    }

    public static function search($query, $page = 1, $includeAdult = false) {
        return self::request("/search/multi", [
            'query' => $query,
            'page' => $page,
            'include_adult' => $includeAdult
        ]);
    }

    public static function getDiscover($mediaType = 'movie', $params = []) {
        return self::request("/discover/{$mediaType}", $params);
    }

    public static function getGenres($mediaType = 'movie') {
        return self::request("/genre/{$mediaType}/list");
    }

    public static function getImageUrl($path, $size = 'w500') {
        if (!$path) return '';
        return TMDB_IMG_BASE . "/{$size}{$path}";
    }

    public static function getBackdropUrl($path, $size = 'original') {
        if (!$path) return '';
        return TMDB_IMG_BASE . "/{$size}{$path}";
    }

    public static function getPersonDetail($id) {
        return self::request("/person/{$id}", [
            'append_to_response' => 'movie_credits,tv_credits,images'
        ]);
    }

    public static function getTranslations($mediaType, $id) {
        return self::request("/{$mediaType}/{$id}/translations");
    }

    public static function getAlternativeTitles($mediaType, $id) {
        if ($mediaType === 'movie') {
            return self::request("/movie/{$id}/alternative_titles");
        }
        return self::request("/tv/{$id}/alternative_titles");
    }

    // 检测是否有普通话配音（基于翻译数据和关键词）
    public static function hasMandarinDub($mediaType, $id, $detail = null) {
        // 如果是中文/国产内容，不需要配音选择
        if ($detail) {
            $originCountry = $detail['origin_country'] ?? [];
            $originalLanguage = $detail['original_language'] ?? '';
            if (in_array('CN', $originCountry) || $originalLanguage === 'zh') {
                return false;
            }
        }

        $translations = self::getTranslations($mediaType, $id);
        if (isset($translations['translations'])) {
            foreach ($translations['translations'] as $t) {
                if (in_array($t['iso_3166_1'], ['CN', 'TW', 'HK'])) {
                    return true;
                }
            }
        }
        
        // 检查alternative titles
        $altTitles = self::getAlternativeTitles($mediaType, $id);
        if ($mediaType === 'movie' && isset($altTitles['titles'])) {
            foreach ($altTitles['titles'] as $t) {
                if (in_array($t['iso_3166_1'], ['CN', 'TW', 'HK'])) {
                    return true;
                }
            }
        } elseif ($mediaType === 'tv' && isset($altTitles['results'])) {
            foreach ($altTitles['results'] as $t) {
                if (in_array($t['iso_3166_1'], ['CN', 'TW', 'HK'])) {
                    return true;
                }
            }
        }

        return false;
    }

    // 获取分季列表
    public static function getSeasons($detail) {
        $seasons = [];
        if (isset($detail['seasons'])) {
            foreach ($detail['seasons'] as $season) {
                if ($season['season_number'] > 0) {
                    $seasons[] = $season;
                }
            }
        }
        return $seasons;
    }
}
?>
