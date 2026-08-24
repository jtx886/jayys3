<?php
require_once __DIR__ . '/config.php';

// 检查登录
if (!Auth::isLoggedIn()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    $_SESSION['login_message'] = '需要登录才可以观看哦，如没有账号请注册！';
    header('Location: login.php');
    exit;
}

// 检查封禁
$user = Auth::getCurrentUser();
if ($user && $user['is_banned']) {
    if ($user['ban_end_time'] && strtotime($user['ban_end_time']) <= time()) {
        $db = Database::getInstance();
        $db->update('users', ['is_banned' => 0, 'ban_reason' => '', 'ban_start_time' => null, 'ban_end_time' => null], 'id = ?', [$user['id']]);
    } else {
        session_destroy();
        header('Location: login.php');
        exit;
    }
}

$id = intval($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'movie';
$season = intval($_GET['season'] ?? 1);
$episode = intval($_GET['episode'] ?? 1);
$dub = $_GET['dub'] ?? 'original';
$sourceId = intval($_GET['source'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

if (!in_array($type, ['movie', 'tv', 'anime'])) $type = 'movie';
$apiType = ($type === 'anime') ? 'tv' : $type;

$detail = TMDB::getDetail($apiType, $id);
if (!$detail) {
    echo '<h1 style="color:#fff;padding:40px;text-align:center;">内容加载失败</h1><a href="index.php" style="color:var(--primary);text-align:center;display:block;">返回首页</a>';
    exit;
}

$title = $detail['title'] ?? $detail['name'] ?? '';
$originalTitle = $detail['original_title'] ?? $detail['original_name'] ?? '';
$poster = TMDB::getImageUrl($detail['poster_path'] ?? '', 'w300');
$backdrop = TMDB::getBackdropUrl($detail['backdrop_path'] ?? '', 'w1280');

$pageTitle = $title;
$activeNav = '';

$isTv = ($apiType === 'tv');
$seasonName = '';
$episodeName = '';
$episodes = [];
$seasons = [];

if ($isTv) {
    $seasons = TMDB::getSeasons($detail);
    // 找到当前季
    $targetSeason = null;
    foreach ($seasons as $s) {
        if ($s['season_number'] == $season) { $targetSeason = $s; break; }
    }
    if (!$targetSeason && !empty($seasons)) { $targetSeason = $seasons[0]; $season = $targetSeason['season_number']; }
    
    if ($targetSeason) {
        $seasonName = $targetSeason['name'] ?? ('第' . $season . '季');
        $seasonDetail = TMDB::getSeasonDetail($id, $season);
        $episodes = $seasonDetail['episodes'] ?? [];
        foreach ($episodes as $ep) {
            if ($ep['episode_number'] == $episode) {
                $episodeName = $ep['name'] ?? ('第' . $episode . '集');
                break;
            }
        }
        if (!$episodeName && !empty($episodes)) {
            $first = $episodes[0];
            $episode = $first['episode_number'];
            $episodeName = $first['name'] ?? ('第' . $episode . '集');
        }
    }
}

// 播放源
$playSources = Utils::getAllPlaySources();
$currentSource = Utils::getPlaySource($sourceId);
if (!$currentSource && !empty($playSources)) $currentSource = $playSources[0];

// 构建播放URL: 使用解析播放器 + 通过关键词在播放源API搜索匹配
$parserUrl = $currentSource['parser_url'] ?: VIDEO_PARSER;
$searchKeyword = $title;
if ($isTv) {
    $searchKeyword .= " 第{$season}季 第{$episode}集";
}
// 配音标识
if ($dub === 'mandarin') {
    $searchKeyword .= " 普通话";
}

// 使用播放源搜索，获取第一个匹配结果的播放地址
$playUrl = '';
$searchResult = Utils::searchPlaySource($searchKeyword);
if ($searchResult && !empty($searchResult['list'])) {
    $first = $searchResult['list'][0];
    // 尝试解析播放源API格式 (通常 list[0].vod_url 或者 list 里直链)
    if (!empty($first['vod_play_url'])) {
        $urls = explode('#', $first['vod_play_url']);
        if ($isTv && isset($urls[$episode - 1])) {
            $playUrl = explode('$', $urls[$episode - 1])[1] ?? $urls[$episode - 1];
        } elseif (!$isTv) {
            $playUrl = explode('$', $urls[0])[1] ?? $urls[0] ?? '';
        }
    } elseif (is_string($first)) {
        $playUrl = $first;
    }
}

// 最终iframe地址：使用解析播放器
$videoSrc = $parserUrl . urlencode($playUrl);

include __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding:20px 24px 80px;">

    <!-- 面包屑 -->
    <div style="margin-bottom:16px;color:var(--text-muted);font-size:13px;display:flex;gap:8px;flex-wrap:wrap;">
        <a href="index.php" style="color:var(--text-secondary);">首页</a>
        <span>/</span>
        <a href="detail.php?id=<?php echo $id; ?>&type=<?php echo $type; ?>" style="color:var(--text-secondary);"><?php echo htmlspecialchars($title); ?></a>
        <?php if ($isTv): ?>
        <span>/</span><span><?php echo htmlspecialchars($seasonName); ?></span>
        <span>/</span><span style="color:var(--text-primary);"><?php echo htmlspecialchars($episodeName ?: ('第' . $episode . '集')); ?></span>
        <?php else: ?>
        <span>/</span><span style="color:var(--text-primary);">正片</span>
        <?php endif; ?>
    </div>

    <!-- 播放器 -->
    <div class="player-container">
        <div class="player-header">
            <div class="player-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
                <?php echo htmlspecialchars($title); ?>
                <?php if ($isTv): ?>
                    <small style="color:var(--text-muted);font-size:13px;font-weight:400;">· <?php echo htmlspecialchars($seasonName); ?> · <?php echo htmlspecialchars($episodeName ?: ('第' . $episode . '集')); ?></small>
                <?php endif; ?>
                <?php if ($dub === 'mandarin'): ?>
                    <span class="badge badge-info" style="margin-left:8px;">🇨🇳 普通话</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="player-wrapper">
            <iframe id="playerFrame" 
                    src="<?php echo htmlspecialchars($videoSrc); ?>" 
                    allowfullscreen 
                    allow="autoplay; fullscreen; encrypted-media; picture-in-picture"
                    referrerpolicy="no-referrer"
                    sandbox="allow-scripts allow-same-origin allow-presentation allow-popups allow-fullscreen allow-forms">
            </iframe>
        </div>

        <!-- 播放源切换 -->
        <div class="player-sources">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
                <div>
                    <div class="selector-label" style="margin-bottom:8px;">📡 播放源</div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <?php foreach ($playSources as $src): ?>
                        <button class="btn btn-sm <?php echo $currentSource['id'] == $src['id'] ? 'btn-primary' : 'btn-outline'; ?>"
                                onclick="changeSource(<?php echo $src['id']; ?>)">
                            <?php echo htmlspecialchars($src['name']); ?>
                            <?php if ($src['is_default']): ?><span class="badge badge-success" style="margin-left:4px;padding:1px 6px;font-size:9px;">默认</span><?php endif; ?>
                        </button>
                        <?php endforeach; ?>
                        <?php if (empty($playSources)): ?>
                        <span style="color:var(--text-muted);font-size:13px;">暂无可用播放源</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                $hasMandarin = TMDB::hasMandarinDub($apiType, $id, $detail);
                if ($hasMandarin):
                ?>
                <div>
                    <div class="selector-label" style="margin-bottom:8px;">🔊 播放语言</div>
                    <div style="display:flex;gap:8px;">
                        <a href="?id=<?php echo $id; ?>&type=<?php echo $type; ?>&season=<?php echo $season; ?>&episode=<?php echo $episode; ?>&dub=original&source=<?php echo $sourceId; ?>" 
                           class="btn btn-sm <?php echo $dub !== 'mandarin' ? 'btn-primary' : 'btn-outline'; ?>">🔊 原声版</a>
                        <a href="?id=<?php echo $id; ?>&type=<?php echo $type; ?>&season=<?php echo $season; ?>&episode=<?php echo $episode; ?>&dub=mandarin&source=<?php echo $sourceId; ?>" 
                           class="btn btn-sm <?php echo $dub === 'mandarin' ? 'btn-primary' : 'btn-outline'; ?>">🇨🇳 普通话版</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 剧集快速切换 -->
    <?php if ($isTv && !empty($episodes)): ?>
    <div class="panel" style="padding:20px 24px;margin-top:24px;">
        <div class="selector-label" style="margin-bottom:14px;">
            📽️ 快速切换 <small style="opacity:0.6;margin-left:4px;">(当前：第<?php echo $episode; ?>集)</small>
        </div>
        <div class="episode-grid">
            <?php 
            foreach ($episodes as $ep): 
                $epNum = $ep['episode_number'];
                $epName = $ep['name'] ?? ('第' . $epNum . '集');
                $epStill = TMDB::getImageUrl($ep['still_path'] ?? '', 'w300');
                if (!$epStill) $epStill = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180"><rect fill="#2a2a3a" width="320" height="180"/><text x="160" y="90" text-anchor="middle" fill="#6b7280" font-size="14">E' . $epNum . '</text></svg>');
                $isActive = ($epNum == $episode);
            ?>
            <a href="?id=<?php echo $id; ?>&type=<?php echo $type; ?>&season=<?php echo $season; ?>&episode=<?php echo $epNum; ?>&dub=<?php echo $dub; ?>&source=<?php echo $sourceId; ?>" 
               class="episode-card" style="<?php echo $isActive ? 'border-color:var(--primary);box-shadow:0 0 0 2px rgba(124,58,237,0.3);' : ''; ?>">
                <div class="episode-thumb">
                    <img src="<?php echo htmlspecialchars($epStill); ?>" loading="lazy" onerror="this.src='data:image/svg+xml;base64,<?php echo base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180"><rect fill="%232a2a3a" width="320" height="180"/></svg>'); ?>'">
                    <span class="episode-number" style="<?php echo $isActive ? 'background:var(--gradient-primary);' : ''; ?>">第<?php echo $epNum; ?>集</span>
                    <?php if ($isActive): ?>
                    <div style="position:absolute;inset:0;background:rgba(124,58,237,0.15);"></div>
                    <?php endif; ?>
                </div>
                <div class="episode-info">
                    <div class="episode-title" style="<?php echo $isActive ? 'color:var(--secondary);' : ''; ?>"><?php echo htmlspecialchars($epName); ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 影视信息 -->
    <div style="margin-top:24px;display:flex;gap:20px;flex-wrap:wrap;align-items:flex-start;">
        <div style="width:140px;flex-shrink:0;border-radius:12px;overflow:hidden;box-shadow:var(--shadow-card);">
            <img src="<?php echo htmlspecialchars($poster); ?>" alt="" style="width:100%;aspect-ratio:2/3;object-fit:cover;">
        </div>
        <div style="flex:1;min-width:260px;">
            <h1 style="font-size:24px;font-weight:700;margin-bottom:6px;"><?php echo htmlspecialchars($title); ?></h1>
            <?php if ($originalTitle && $originalTitle !== $title): ?>
            <div style="color:var(--text-muted);margin-bottom:14px;"><?php echo htmlspecialchars($originalTitle); ?></div>
            <?php endif; ?>
            <div style="color:var(--text-secondary);font-size:14px;line-height:1.8;">
                <?php echo htmlspecialchars($detail['overview'] ?? ''); ?>
            </div>
            <div style="margin-top:14px;display:flex;gap:16px;flex-wrap:wrap;">
                <?php if (!empty($detail['vote_average'])): ?>
                <span style="color:#fbbf24;font-weight:700;">⭐ <?php echo round($detail['vote_average'], 1); ?>分</span>
                <?php endif; ?>
                <?php 
                $year = substr($detail['release_date'] ?? $detail['first_air_date'] ?? '', 0, 4);
                if ($year): ?>
                <span style="color:var(--text-muted);">📅 <?php echo $year; ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

</main>

<script>
const MEDIA_ID = <?php echo $id; ?>;
const MEDIA_TYPE = '<?php echo $type; ?>';
const MEDIA_TITLE = <?php echo json_encode($title . ($isTv ? " $seasonName $episodeName" : '')); ?>;
const MEDIA_POSTER = <?php echo json_encode($poster); ?>;
const SEASON = <?php echo $season; ?>;
const EPISODE = <?php echo $episode; ?>;

function changeSource(id) {
    const params = new URLSearchParams(location.search);
    params.set('source', id);
    location.search = params.toString();
}

// 定期保存观看进度 (每30秒)
let watchSeconds = 0;
setInterval(() => {
    watchSeconds += 30;
    try {
        fetch('ajax/save_progress.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                media_id: MEDIA_ID,
                media_type: MEDIA_TYPE,
                title: MEDIA_TITLE,
                poster: MEDIA_POSTER,
                season: SEASON,
                episode: EPISODE,
                seconds: watchSeconds
            })
        });
    } catch(e) {}
}, 30000);

// 尝试从iframe获取进度（如果同源）
try {
    const frame = document.getElementById('playerFrame');
    setInterval(() => {
        try {
            const v = frame.contentDocument?.querySelector('video');
            if (v && v.currentTime) watchSeconds = Math.floor(v.currentTime);
        } catch(e) {}
    }, 5000);
} catch(e) {}

// 页面关闭时保存进度
window.addEventListener('beforeunload', () => {
    try {
        navigator.sendBeacon('ajax/save_progress.php', JSON.stringify({
            media_id: MEDIA_ID,
            media_type: MEDIA_TYPE,
            title: MEDIA_TITLE,
            poster: MEDIA_POSTER,
            season: SEASON,
            episode: EPISODE,
            seconds: watchSeconds
        }));
    } catch(e) {}
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
