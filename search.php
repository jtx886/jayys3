<?php
require_once __DIR__ . '/config.php';

$q = trim($_GET['q'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$pageTitle = $q ? '搜索：' . $q : '搜索';
$activeNav = '';

$results = [];
$totalPages = 1;
if ($q) {
    $data = TMDB::search($q, $page);
    $results = $data['results'] ?? [];
    $totalPages = min(500, intval($data['total_pages'] ?? 1));
}

function renderMediaCard($item) {
    $mediaId = $item['id'];
    $mediaType = $item['media_type'] ?? 'movie';
    if ($mediaType === 'person') return '';
    
    $title = $item['title'] ?? $item['name'] ?? '未知';
    $poster = TMDB::getImageUrl($item['poster_path'] ?? '', 'w500');
    $year = substr($item['release_date'] ?? $item['first_air_date'] ?? '', 0, 4);
    $rating = round($item['vote_average'] ?? 0, 1);
    $typeLabel = '';
    if ($mediaType === 'tv') $typeLabel = '电视剧';
    elseif ($mediaType === 'movie') $typeLabel = '电影';
    
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
            <div class="media-subtitle"><span>' . ($year ?: '----') . '</span>' . ($typeLabel ? '<span class="media-dot"></span><span>' . $typeLabel . '</span>' : '') . '</div>
        </div>
    </div>';
}

include __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding:24px 24px 80px;">
    
    <!-- 搜索框 -->
    <div style="max-width:640px;margin:0 auto 32px;">
        <form action="search.php" method="get" style="display:flex;gap:10px;">
            <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="搜索电影、电视剧、动漫..." required 
                   class="form-input" style="flex:1;padding:14px 20px;font-size:15px;">
            <button type="submit" class="btn btn-primary btn-lg">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                搜索
            </button>
        </form>
    </div>

    <?php if ($q): ?>
    <div class="section-header" style="margin-bottom:24px;">
        <h2 class="section-title">
            <span class="section-title-icon">🔍</span>
            搜索结果
        </h2>
        <span style="color:var(--text-muted);font-size:14px;">找到 <?php echo count($results); ?> 个结果</span>
    </div>

    <?php if (empty($results)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">😔</div>
        <div class="empty-state-title">没有找到相关内容</div>
        <div class="empty-state-desc">试试换个关键词搜索吧</div>
    </div>
    <?php else: ?>
    <div class="media-grid">
        <?php
        foreach ($results as $item) {
            echo renderMediaCard($item);
        }
        ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="display:flex;justify-content:center;gap:8px;margin-top:40px;flex-wrap:wrap;">
        <?php if ($page > 1): ?>
        <a href="?q=<?php echo urlencode($q); ?>&page=<?php echo $page-1; ?>" class="btn btn-outline btn-sm">上一页</a>
        <?php endif; ?>
        <?php
        $startPage = max(1, $page - 4);
        $endPage = min($totalPages, $startPage + 9);
        for ($i = $startPage; $i <= $endPage; $i++):
        ?>
        <a href="?q=<?php echo urlencode($q); ?>&page=<?php echo $i; ?>" 
           class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="?q=<?php echo urlencode($q); ?>&page=<?php echo $page+1; ?>" class="btn btn-outline btn-sm">下一页</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">🎬</div>
        <div class="empty-state-title">开始搜索</div>
        <div class="empty-state-desc">输入关键字搜索您想要的影视内容</div>
    </div>

    <!-- 热门搜索推荐 -->
    <section class="section" style="margin-top:40px;">
        <div class="section-header"><h2 class="section-title"><span class="section-title-icon">🔥</span>热门推荐</h2></div>
        <div class="media-grid">
            <?php
            $trending = TMDB::getTrending('all', 'week', 1);
            $hot = array_slice($trending['results'] ?? [], 0, 12);
            foreach ($hot as $item) {
                echo renderMediaCard($item);
            }
            ?>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
