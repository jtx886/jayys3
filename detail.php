<?php
require_once __DIR__ . '/config.php';

$id = intval($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'movie';
$season = intval($_GET['season'] ?? 1);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

if (!in_array($type, ['movie', 'tv', 'anime'])) $type = 'movie';
$apiType = ($type === 'anime') ? 'tv' : $type;

$detail = TMDB::getDetail($apiType, $id);
if (!$detail) {
    echo '<h1 style="color:#fff;padding:40px;text-align:center;">内容加载失败，请稍后重试</h1>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $detail['title'] ?? $detail['name'] ?? '详情';
$activeNav = '';

$isTv = ($apiType === 'tv');
$seasons = $isTv ? TMDB::getSeasons($detail) : [];
if ($season < 1) $season = 1;
if ($season > count($seasons)) $season = max(1, count($seasons));

$seasonDetail = null;
$episodes = [];
if ($isTv && !empty($seasons)) {
    // 修正：seasons数组的索引对应season_number可能不连续
    $targetSeason = null;
    foreach ($seasons as $s) {
        if ($s['season_number'] == $season) {
            $targetSeason = $s;
            break;
        }
    }
    if (!$targetSeason && !empty($seasons)) {
        $targetSeason = $seasons[0];
        $season = $targetSeason['season_number'];
    }
    if ($targetSeason) {
        $seasonDetail = TMDB::getSeasonDetail($id, $targetSeason['season_number']);
        $episodes = $seasonDetail['episodes'] ?? [];
    }
}

// 检测是否有普通话配音
$hasMandarin = $isTv ? TMDB::hasMandarinDub('tv', $id, $detail) : TMDB::hasMandarinDub('movie', $id, $detail);

$isFavorited = Utils::isFavorited($id, $type);

$title = $detail['title'] ?? $detail['name'] ?? '';
$originalTitle = $detail['original_title'] ?? $detail['original_name'] ?? '';
$overview = $detail['overview'] ?? '';
$backdrop = TMDB::getBackdropUrl($detail['backdrop_path'] ?? '', 'original');
if (!$backdrop) {
    $backdrop = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="500"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#7c3aed"/><stop offset="100%" stop-color="#2563eb"/></linearGradient></defs><rect fill="url(#g)" width="1200" height="500"/></svg>');
}
$poster = TMDB::getImageUrl($detail['poster_path'] ?? '', 'w500');
if (!$poster) {
    $poster = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="300" height="450"><rect fill="#2a2a3a" width="300" height="450"/><text x="150" y="225" text-anchor="middle" fill="#9ca3af" font-size="18">No Poster</text></svg>');
}
$rating = round($detail['vote_average'] ?? 0, 1);
$year = substr($detail['release_date'] ?? $detail['first_air_date'] ?? '', 0, 4);

// 类型
$genres = [];
foreach ($detail['genres'] ?? [] as $g) $genres[] = $g['name'];

// 演员
$cast = [];
foreach (($detail['credits']['cast'] ?? []) as $i => $c) {
    if ($i >= 10) break;
    $cast[] = $c['name'];
}
$director = '';
foreach (($detail['credits']['crew'] ?? []) as $c) {
    if ($c['job'] === 'Director') { $director = $c['name']; break; }
}

// 相似推荐
$similar = $detail['similar']['results'] ?? [];

include __DIR__ . '/includes/header.php';
?>

<!-- Hero区域 -->
<div class="detail-hero">
    <div class="detail-backdrop" style="background-image:url('<?php echo htmlspecialchars($backdrop); ?>')"></div>
    <div class="container">
        <div class="detail-content">
            <div class="detail-poster">
                <img src="<?php echo htmlspecialchars($poster); ?>" alt="<?php echo htmlspecialchars($title); ?>">
            </div>
            <div class="detail-info">
                <h1 class="detail-title"><?php echo htmlspecialchars($title); ?></h1>
                <?php if ($originalTitle && $originalTitle !== $title): ?>
                <div class="detail-original-title"><?php echo htmlspecialchars($originalTitle); ?></div>
                <?php endif; ?>
                
                <div class="detail-tags">
                    <?php foreach ($genres as $g): ?>
                    <span class="detail-tag"><?php echo htmlspecialchars($g); ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="detail-meta-row">
                    <div class="detail-rating-badge">
                        <span class="rating-score"><?php echo $rating; ?></span>
                        <span class="rating-label">/10 分</span>
                    </div>
                    <?php if ($year): ?><span>📅 <?php echo $year; ?></span><?php endif; ?>
                    <?php if (!empty($detail['runtime'])): ?><span>⏱️ <?php echo Utils::formatDuration($detail['runtime']); ?></span><?php endif; ?>
                    <?php if (!empty($detail['number_of_seasons'])): ?><span>📺 <?php echo $detail['number_of_seasons']; ?>季</span><?php endif; ?>
                    <?php if (!empty($detail['status'])): ?><span>📌 <?php echo $detail['status'] === 'Released' || $detail['status'] === 'Returning Series' ? '已上映' : htmlspecialchars($detail['status']); ?></span><?php endif; ?>
                </div>

                <div class="detail-overview"><?php echo htmlspecialchars($overview ?: '暂无剧情简介'); ?></div>

                <?php if ($director || !empty($cast)): ?>
                <div class="detail-cast">
                    <?php if ($director): ?><div class="detail-cast-label">导演</div><div style="margin-bottom:10px;"><?php echo htmlspecialchars($director); ?></div><?php endif; ?>
                    <?php if (!empty($cast)): ?><div class="detail-cast-label">主演</div><div class="detail-cast-list"><?php echo htmlspecialchars(implode(' / ', $cast)); ?></div><?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="detail-actions">
                    <a href="play.php?id=<?php echo $id; ?>&type=<?php echo $type; ?>&season=<?php echo $season; ?>" class="btn btn-primary btn-lg hero-play-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        立即播放
                    </a>
                    <button class="btn btn-outline btn-lg" id="favBtn" onclick="toggleFav()">
                        <svg id="favIcon" width="18" height="18" viewBox="0 0 24 24" fill="<?php echo $isFavorited ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <?php if ($isFavorited): ?>
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            <?php else: ?>
                            <line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>
                            <?php endif; ?>
                        </svg>
                        <span id="favText"><?php echo $isFavorited ? '已收藏' : '收藏'; ?></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<main class="container" style="padding:0 24px 80px;">

    <?php if ($isTv): ?>
    <!-- 配音选择（仅外片显示） -->
    <?php if ($hasMandarin): ?>
    <div class="dub-selector panel" style="padding:20px 24px;">
        <div class="selector-label">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path><path d="M19.07 4.93a10 10 0 0 1 0 14.14"></path></svg>
            播放语言
        </div>
        <div class="dub-tabs">
            <button class="dub-tab active" data-dub="original">🔊 原声版</button>
            <button class="dub-tab" data-dub="mandarin">🇨🇳 普通话版</button>
        </div>
    </div>
    <?php endif; ?>

    <!-- 分季选择 -->
    <?php if (!empty($seasons)): ?>
    <div class="season-selector panel" style="padding:20px 24px;">
        <div class="selector-label">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
            选择季
        </div>
        <div class="season-tabs">
            <?php 
            foreach ($seasons as $s): 
                $sn = $s['season_number'];
                $seasonName = '第' . $sn . '季';
                if (!empty($s['name'])) {
                    // 使用TMDB名字，但如果包含season则简化
                    $seasonName = (strpos($s['name'], '第') === 0 || strpos($s['name'], 'Season') === 0) ? $seasonName : $s['name'];
                }
            ?>
            <button class="season-tab <?php echo $sn == $season ? 'active' : ''; ?>" 
                    onclick="changeSeason(<?php echo $sn; ?>)">
                <?php echo htmlspecialchars($seasonName); ?>
                <small style="opacity:0.6;margin-left:4px;">(<?php echo $s['episode_count'] ?? count($s['episodes'] ?? []); ?>集)</small>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 季介绍 -->
    <?php if ($seasonDetail && !empty($seasonDetail['overview'])): ?>
    <div class="panel" style="padding:20px 24px;margin-bottom:24px;">
        <div class="selector-label" style="margin-bottom:10px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
            本季简介 · 
            <?php if (!empty($seasonDetail['air_date'])) echo date('Y', strtotime($seasonDetail['air_date'])); ?>
            <?php if (!empty($seasonDetail['vote_average']) && $seasonDetail['vote_average'] > 0): ?>
            · ⭐ <?php echo round($seasonDetail['vote_average'], 1); ?>
            <?php endif; ?>
        </div>
        <div style="color:var(--text-secondary);line-height:1.8;font-size:14px;"><?php echo htmlspecialchars($seasonDetail['overview']); ?></div>
    </div>
    <?php endif; ?>

    <!-- 剧集列表 -->
    <?php if (!empty($episodes)): ?>
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-title-icon">📽️</span>
                选集播放 <small style="font-size:14px;color:var(--text-muted);font-weight:400;">共 <?php echo count($episodes); ?> 集</small>
            </h2>
        </div>
        <div class="episode-grid">
            <?php 
            foreach ($episodes as $ep): 
                $epNum = $ep['episode_number'];
                $epName = $ep['name'] ?? ('第' . $epNum . '集');
                $epStill = TMDB::getImageUrl($ep['still_path'] ?? '', 'w300');
                if (!$epStill) {
                    // 使用主backdrop作为备选
                    $epStill = TMDB::getImageUrl($detail['backdrop_path'] ?? '', 'w300');
                }
                if (!$epStill) {
                    $epStill = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180"><rect fill="#2a2a3a" width="320" height="180"/><text x="160" y="90" text-anchor="middle" fill="#6b7280" font-size="14">E' . $epNum . '</text></svg>');
                }
                $epDate = Utils::formatDate($ep['air_date'] ?? '');
                $playUrl = "play.php?id={$id}&type={$type}&season={$season}&episode={$epNum}";
            ?>
            <div class="episode-card" onclick="location.href='<?php echo $playUrl; ?>'">
                <div class="episode-thumb">
                    <img src="<?php echo htmlspecialchars($epStill); ?>" alt="<?php echo htmlspecialchars($epName); ?>" loading="lazy" onerror="this.src='data:image/svg+xml;base64,<?php echo base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180"><rect fill="%232a2a3a" width="320" height="180"/></svg>'); ?>'">
                    <span class="episode-number">第<?php echo $epNum; ?>集</span>
                    <div class="media-play-overlay" style="opacity:1;"><div class="media-play-btn" style="width:40px;height:40px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg></div></div>
                </div>
                <div class="episode-info">
                    <div class="episode-title"><?php echo htmlspecialchars($epName); ?></div>
                    <?php if ($epDate): ?><div class="episode-date"><?php echo $epDate; ?></div><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php else: ?>
    <!-- 电影 - 没有分集直接播放 -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-title-icon">▶️</span>
                立即播放
            </h2>
        </div>
        <div class="panel" style="padding:32px;text-align:center;">
            <a href="play.php?id=<?php echo $id; ?>&type=<?php echo $type; ?>" class="btn btn-primary btn-lg hero-play-btn" style="padding:18px 44px;font-size:18px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                播放正片
            </a>
            <?php if ($hasMandarin): ?>
            <div style="margin-top:16px;display:flex;justify-content:center;">
                <div class="dub-tabs">
                    <button class="dub-tab active" data-dub="original">🔊 原声版</button>
                    <button class="dub-tab" data-dub="mandarin">🇨🇳 普通话版</button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- 相似推荐 -->
    <?php if (!empty($similar)): ?>
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-title-icon">💡</span>
                相关推荐
            </h2>
        </div>
        <div class="media-scroll">
            <?php
            foreach (array_slice($similar, 0, 12) as $item) {
                $mediaId = $item['id'];
                $mType = $apiType;
                $mTitle = $item['title'] ?? $item['name'] ?? '';
                $mPoster = TMDB::getImageUrl($item['poster_path'] ?? '', 'w500');
                if (!$mPoster) $mPoster = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="300" height="450"><rect fill="#2a2a3a" width="300" height="450"/></svg>');
                $mYear = substr($item['release_date'] ?? $item['first_air_date'] ?? '', 0, 4);
                $mRating = round($item['vote_average'] ?? 0, 1);
                $mUrl = "detail.php?id={$mediaId}&type={$mType}";
                echo '<div class="media-card" onclick="location.href=\'' . $mUrl . '\'">
                    <div class="media-poster">
                        <img src="' . htmlspecialchars($mPoster) . '" loading="lazy" onerror="this.src=\'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="300" height="450"><rect fill="%232a2a3a" width="300" height="450"/></svg>') . '\'">
                        ' . ($mRating > 0 ? '<div class="media-badge-rating">' . $mRating . '</div>' : '') . '
                        <div class="media-play-overlay"><div class="media-play-btn"><svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg></div></div>
                    </div>
                    <div class="media-info">
                        <div class="media-title">' . htmlspecialchars($mTitle) . '</div>
                        <div class="media-subtitle"><span>' . ($mYear ?: '----') . '</span></div>
                    </div>
                </div>';
            }
            ?>
        </div>
    </section>
    <?php endif; ?>

</main>

<script>
const MEDIA_ID = <?php echo $id; ?>;
const MEDIA_TYPE = '<?php echo $type; ?>';
const MEDIA_TITLE = <?php echo json_encode($title); ?>;
const MEDIA_POSTER = <?php echo json_encode(TMDB::getImageUrl($detail['poster_path'] ?? '')); ?>;
let currentDub = 'original';

// 切换季
function changeSeason(s) {
    location.href = 'detail.php?id=' + MEDIA_ID + '&type=' + MEDIA_TYPE + '&season=' + s;
}

// 配音选择
document.querySelectorAll('.dub-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.dub-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        currentDub = tab.dataset.dub;
        // 更新所有播放链接
        document.querySelectorAll('a[href^="play.php"]').forEach(a => {
            const url = new URL(a.href, location.origin);
            url.searchParams.set('dub', currentDub);
            a.href = url.pathname + url.search;
        });
    });
});

// 收藏切换
async function toggleFav() {
    <?php if (!Auth::isLoggedIn()): ?>
    showToast('请先登录后再收藏哦', 'warning');
    setTimeout(() => location.href = 'login.php', 1000);
    return;
    <?php endif; ?>
    
    try {
        const res = await fetch('ajax/toggle_favorite.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                media_id: MEDIA_ID,
                media_type: MEDIA_TYPE,
                title: MEDIA_TITLE,
                poster: MEDIA_POSTER
            })
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            const favIcon = document.getElementById('favIcon');
            const favText = document.getElementById('favText');
            if (data.data.favorited) {
                favIcon.setAttribute('fill', 'currentColor');
                favIcon.innerHTML = '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>';
                favText.textContent = '已收藏';
            } else {
                favIcon.setAttribute('fill', 'none');
                favIcon.innerHTML = '<line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>';
                favText.textContent = '收藏';
            }
        } else {
            showToast(data.message, 'error');
        }
    } catch (e) {
        showToast('操作失败', 'error');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
