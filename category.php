<?php
require_once __DIR__ . '/config.php';

$type = $_GET['type'] ?? 'movie';
$page = max(1, intval($_GET['page'] ?? 1));
$genre = intval($_GET['genre'] ?? 0);
$keyword = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'popularity.desc';

$typeMap = [
    'movie' => ['name' => '电影', 'nav' => 'movie', 'genres' => [28=>'动作',12=>'冒险',16=>'动画',35=>'喜剧',80=>'犯罪',99=>'纪录',18=>'剧情',10751=>'家庭',14=>'奇幻',36=>'历史',27=>'恐怖',10402=>'音乐',9648=>'悬疑',10749=>'爱情',878=>'科幻',53=>'惊悚',10752=>'战争',37=>'西部']],
    'tv' => ['name' => '电视剧', 'nav' => 'tv', 'genres' => [10759=>'动作冒险',16=>'动画',35=>'喜剧',80=>'犯罪',99=>'纪录',18=>'剧情',10751=>'家庭',10762=>'儿童',9648=>'悬疑',10763=>'新闻',10764=>'真人秀',10765=>'科幻奇幻',10766=>'肥皂剧',10767=>'脱口秀',10768=>'战争政治',37=>'西部']],
    'anime' => ['name' => '动漫', 'nav' => 'anime', 'genres' => [], 'with_genre' => 16],
    'variety' => ['name' => '综艺', 'nav' => 'variety', 'genres' => [], 'with_genre' => '10764,10767']
];

if (!isset($typeMap[$type])) $type = 'movie';
$config = $typeMap[$type];

$pageTitle = $config['name'];
$activeNav = $config['nav'];

// 获取分类数据
$apiType = ($type === 'anime' || $type === 'variety') ? 'tv' : $type;
$params = ['page' => $page, 'sort_by' => $sort, 'vote_count.gte' => 20];

if (!empty($config['with_genre'])) {
    $params['with_genres'] = $config['with_genre'];
}
if ($genre > 0) {
    $params['with_genres'] = $genre;
}
if ($keyword) {
    $params['with_text_query'] = $keyword;
}

$data = TMDB::getDiscover($apiType, $params);
$results = $data['results'] ?? [];
$totalPages = min(500, intval($data['total_pages'] ?? 1));

function renderMediaCard($item, $type = null) {
    $mediaId = $item['id'];
    $mediaType = $type ?? ($item['media_type'] ?? 'movie');
    $title = $item['title'] ?? $item['name'] ?? '未知';
    $poster = TMDB::getImageUrl($item['poster_path'] ?? '', 'w500');
    $year = substr($item['release_date'] ?? $item['first_air_date'] ?? '', 0, 4);
    $rating = round($item['vote_average'] ?? 0, 1);
    $typeLabel = $mediaType === 'tv' ? '剧集' : '电影';
    
    if (!$poster) {
        $poster = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="300" height="450"><rect fill="#2a2a3a" width="300" height="450"/><text x="150" y="225" text-anchor="middle" fill="#6b7280" font-size="16">No Image</text></svg>');
    }
    
    $url = "detail.php?id={$mediaId}&type={$mediaType}";
    
    return '<div class="media-card" onclick="location.href=\'' . $url . '\'">
        <div class="media-poster">
            <img src="' . htmlspecialchars($poster) . '" alt="' . htmlspecialchars($title) . '" loading="lazy" onerror="this.src=\'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="300" height="450"><rect fill="%232a2a3a" width="300" height="450"/></svg>') . '\'">
            ' . ($rating > 0 ? '<div class="media-badge-rating">' . $rating . '</div>' : '') . '
            <div class="media-play-overlay"><div class="media-play-btn"><svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg></div></div>
        </div>
        <div class="media-info">
            <div class="media-title">' . htmlspecialchars($title) . '</div>
            <div class="media-subtitle"><span>' . ($year ?: '----') . '</span><span class="media-dot"></span><span>' . $typeLabel . '</span></div>
        </div>
    </div>';
}

include __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding:24px 24px 80px;">
    <div class="section-header" style="margin-bottom:24px;">
        <h1 class="section-title">
            <span class="section-title-icon">
                <?php echo $type === 'movie' ? '🎬' : ($type === 'tv' ? '📺' : ($type === 'anime' ? '🎨' : '🎤')); ?>
            </span>
            <?php echo $config['name']; ?>
            <?php if ($genre > 0 && isset($config['genres'][$genre])): ?>
                <small style="font-size:16px;color:var(--text-muted);font-weight:400;">· <?php echo $config['genres'][$genre]; ?></small>
            <?php endif; ?>
        </h1>
    </div>

    <!-- 分类筛选 -->
    <?php if (!empty($config['genres'])): ?>
    <div class="category-tabs" style="margin-bottom:24px;">
        <a href="category.php?type=<?php echo $type; ?>&sort=<?php echo urlencode($sort); ?>" class="category-tab <?php echo !$genre ? 'active' : ''; ?>">全部</a>
        <?php foreach ($config['genres'] as $gid => $gname): ?>
            <a href="category.php?type=<?php echo $type; ?>&genre=<?php echo $gid; ?>&sort=<?php echo urlencode($sort); ?>" class="category-tab <?php echo $genre == $gid ? 'active' : ''; ?>"><?php echo $gname; ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- 结果 -->
    <?php if (empty($results)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">🔍</div>
        <div class="empty-state-title">暂无内容</div>
        <div class="empty-state-desc">此分类暂时没有找到相关内容</div>
    </div>
    <?php else: ?>
    <div class="media-grid">
        <?php
        foreach ($results as $item) {
            echo renderMediaCard($item, $apiType);
        }
        ?>
    </div>

    <!-- 分页 -->
    <?php if ($totalPages > 1): ?>
    <div style="display:flex;justify-content:center;gap:8px;margin-top:40px;flex-wrap:wrap;">
        <?php if ($page > 1): ?>
        <a href="?type=<?php echo $type; ?>&genre=<?php echo $genre; ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $page-1; ?>" class="btn btn-outline btn-sm">上一页</a>
        <?php endif; ?>
        
        <?php
        $startPage = max(1, $page - 4);
        $endPage = min($totalPages, $startPage + 9);
        if ($endPage - $startPage < 9) $startPage = max(1, $endPage - 9);
        for ($i = $startPage; $i <= $endPage; $i++):
        ?>
        <a href="?type=<?php echo $type; ?>&genre=<?php echo $genre; ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $i; ?>" 
           class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
        <a href="?type=<?php echo $type; ?>&genre=<?php echo $genre; ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $page+1; ?>" class="btn btn-outline btn-sm">下一页</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
