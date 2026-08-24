<?php
require_once __DIR__ . '/config.php';

$pageTitle = '首页';
$activeNav = 'home';

// 获取Banner数据（热播影视）
$banners = [];
$trending = TMDB::getTrending('movie', 'week', 1);
if ($trending && !empty($trending['results'])) {
    $banners = array_slice($trending['results'], 0, 5);
}

// 备用Banner（如果TMDB请求失败）
if (empty($banners)) {
    $banners = [
        ['id' => 100, 'title' => '精彩影视推荐', 'overview' => '发现全球优质影视内容，畅享高清视觉盛宴', 'poster_path' => '', 'backdrop_path' => '', 'vote_average' => 9.0, 'release_date' => '2024', 'media_type' => 'movie'],
        ['id' => 101, 'title' => '热门剧集精选', 'overview' => '同步更新最热门电视剧集，追剧快人一步', 'poster_path' => '', 'backdrop_path' => '', 'vote_average' => 8.8, 'release_date' => '2024', 'media_type' => 'tv'],
        ['id' => 102, 'title' => '动漫新番速递', 'overview' => '最新最全日漫国漫，二次元爱好者的天堂', 'poster_path' => '', 'backdrop_path' => '', 'vote_average' => 9.2, 'release_date' => '2024', 'media_type' => 'anime']
    ];
}

// 获取热门电影
$popularMovies = TMDB::getPopular('movie', 1);
$movies = $popularMovies['results'] ?? [];

// 获取热门电视剧
$popularTv = TMDB::getPopular('tv', 1);
$tvShows = $popularTv['results'] ?? [];

// 获取高分动漫（动画类型）
$animeData = TMDB::getDiscover('tv', [
    'with_genres' => '16',
    'sort_by' => 'popularity.desc',
    'vote_count.gte' => 50,
    'page' => 1
]);
$animes = $animeData['results'] ?? [];

// 热播榜（综合）
$topRated = TMDB::getTrending('all', 'week', 1);
$hotList = $topRated['results'] ?? [];

// 渲染卡片
function renderMediaCard($item, $type = null) {
    $mediaId = $item['id'];
    $mediaType = $type ?? ($item['media_type'] ?? 'movie');
    if ($mediaType === 'person') return '';
    
    $title = $item['title'] ?? $item['name'] ?? '未知';
    $poster = TMDB::getImageUrl($item['poster_path'] ?? '', 'w500');
    $year = substr($item['release_date'] ?? $item['first_air_date'] ?? '', 0, 4);
    $rating = round($item['vote_average'] ?? 0, 1);
    $typeLabel = '';
    $episodeInfo = '';
    
    if ($mediaType === 'tv' || $mediaType === 'anime') {
        $typeLabel = $mediaType === 'anime' ? '动画' : '电视剧';
        if (isset($item['number_of_episodes'])) {
            $episodeInfo = '· 更新至' . $item['number_of_episodes'] . '集';
        } elseif (isset($item['status'])) {
            $episodeInfo = $item['status'] === 'Ended' ? '· 全剧终' : '';
        }
    } else {
        $typeLabel = '电影';
    }
    
    if (!$poster) {
        $poster = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="300" height="450" viewBox="0 0 300 450"><rect fill="#2a2a3a" width="300" height="450"/><text x="150" y="225" text-anchor="middle" fill="#6b7280" font-size="16" font-family="sans-serif">Jay影视</text></svg>');
    }
    
    $url = "detail.php?id={$mediaId}&type={$mediaType}";
    
    return '<div class="media-card" onclick="location.href=\'' . $url . '\'">
        <div class="media-poster">
            <img src="' . htmlspecialchars($poster) . '" alt="' . htmlspecialchars($title) . '" loading="lazy" onerror="this.src=\'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="300" height="450"><rect fill="%232a2a3a" width="300" height="450"/><text x="150" y="225" text-anchor="middle" fill="%236b7280" font-size="16">No Image</text></svg>') . '\'">
            ' . ($rating > 0 ? '<div class="media-badge-rating">' . $rating . '</div>' : '') . '
            <div class="media-play-overlay">
                <div class="media-play-btn">
                    <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                </div>
            </div>
        </div>
        <div class="media-info">
            <div class="media-title">' . htmlspecialchars($title) . '</div>
            <div class="media-subtitle">
                <span>' . ($year ?: '----') . '</span>
                <span class="media-dot"></span>
                <span>' . $typeLabel . '</span>
                ' . $episodeInfo . '
            </div>
        </div>
    </div>';
}

include __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding-bottom:40px;">

    <!-- Hero Banner -->
    <section class="hero-section">
        <div class="hero-banner">
            <?php 
            $banner = $banners[0];
            $bannerId = $banner['id'];
            $bannerType = $banner['media_type'] ?? 'movie';
            if ($bannerType === 'person') {
                $bannerType = 'movie';
            }
            $bannerBackdrop = TMDB::getBackdropUrl($banner['backdrop_path'] ?? '', 'original');
            if (!$bannerBackdrop) {
                $bannerBackdrop = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="500"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#7c3aed"/><stop offset="100%" stop-color="#2563eb"/></linearGradient></defs><rect fill="url(#g)" width="1200" height="500"/></svg>');
            }
            $bannerTitle = $banner['title'] ?? $banner['name'] ?? '精彩推荐';
            $bannerRating = round($banner['vote_average'] ?? 0, 1);
            $bannerYear = substr($banner['release_date'] ?? $banner['first_air_date'] ?? '', 0, 4);
            $bannerDesc = $banner['overview'] ?? '发现更多精彩影视内容，尽在Jay影视！';
            if (mb_strlen($bannerDesc) > 80) $bannerDesc = mb_substr($bannerDesc, 0, 80) . '...';
            ?>
            <div class="hero-bg" style="background-image:url('<?php echo htmlspecialchars($bannerBackdrop); ?>')"></div>
            <div class="hero-content">
                <span class="hero-tag">🔥 正在热播</span>
                <h1 class="hero-title"><?php echo htmlspecialchars($bannerTitle); ?></h1>
                <div class="hero-meta">
                    <span class="hero-rating"><?php echo $bannerRating; ?></span>
                    <span><?php echo $bannerYear ?: '----'; ?></span>
                    <span><?php echo $bannerType === 'tv' ? '电视剧' : ($bannerType === 'anime' ? '动漫' : '电影'); ?></span>
                </div>
                <p class="hero-desc"><?php echo htmlspecialchars($bannerDesc); ?></p>
                <div class="hero-actions">
                    <a href="play.php?id=<?php echo $bannerId; ?>&type=<?php echo $bannerType; ?>" class="btn btn-primary btn-lg hero-play-btn">
                        <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        立即播放
                    </a>
                    <button class="btn btn-outline btn-lg" onclick="toggleBannerFav(<?php echo $bannerId; ?>, '<?php echo $bannerType; ?>', '<?php echo htmlspecialchars($bannerTitle, ENT_QUOTES); ?>', '<?php echo htmlspecialchars(TMDB::getImageUrl($banner['poster_path'] ?? ''), ENT_QUOTES); ?>')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        收藏
                    </button>
                </div>
            </div>
            <div class="hero-dots">
                <?php foreach ($banners as $i => $b): ?>
                    <div class="hero-dot <?php echo $i === 0 ? 'active' : ''; ?>"></div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- 热门推荐 -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-title-icon">🔥</span>
                热门推荐
            </h2>
            <a href="search.php?sort=popular" class="section-more">
                查看更多
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
        </div>
        <div class="media-scroll">
            <?php
            $hotItems = array_slice($hotList, 0, 12);
            foreach ($hotItems as $item) {
                echo renderMediaCard($item);
            }
            // 不够的话补充电影
            foreach (array_slice($movies, 0, 6) as $m) {
                echo renderMediaCard($m, 'movie');
            }
            ?>
        </div>
    </section>

    <!-- 电影 -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-title-icon">🎬</span>
                电影
            </h2>
            <a href="category.php?type=movie" class="section-more">
                查看更多
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
        </div>
        <div class="category-tabs">
            <button class="category-tab active">全部</button>
            <button class="category-tab">动作</button>
            <button class="category-tab">喜剧</button>
            <button class="category-tab">爱情</button>
            <button class="category-tab">科幻</button>
            <button class="category-tab">悬疑</button>
            <button class="category-tab">剧情</button>
        </div>
        <div class="media-grid">
            <?php
            foreach (array_slice($movies, 0, 18) as $m) {
                echo renderMediaCard($m, 'movie');
            }
            ?>
        </div>
    </section>

    <!-- 电视剧 -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-title-icon">📺</span>
                电视剧
            </h2>
            <a href="category.php?type=tv" class="section-more">
                查看更多
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
        </div>
        <div class="media-grid">
            <?php
            foreach (array_slice($tvShows, 0, 18) as $t) {
                echo renderMediaCard($t, 'tv');
            }
            ?>
        </div>
    </section>

    <!-- 动漫 -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-title-icon">🎨</span>
                动漫
            </h2>
            <a href="category.php?type=anime" class="section-more">
                查看更多
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
        </div>
        <div class="media-grid">
            <?php
            foreach (array_slice($animes, 0, 18) as $a) {
                echo renderMediaCard($a, 'anime');
            }
            ?>
        </div>
    </section>

</main>

<script>
// Banner收藏
async function toggleBannerFav(id, type, title, poster) {
    <?php if (!Auth::isLoggedIn()): ?>
    showToast('请先登录后再收藏哦', 'warning');
    setTimeout(() => location.href = 'login.php', 1000);
    return;
    <?php endif; ?>
    
    try {
        const res = await fetch('ajax/toggle_favorite.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({media_id: id, media_type: type, title, poster})
        });
        const data = await res.json();
        showToast(data.message, data.success ? 'success' : 'error');
    } catch (e) {
        showToast('操作失败', 'error');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
