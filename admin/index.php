<?php $pageName = '仪表盘'; include __DIR__ . '/admin_header.php'; ?>

<div class="admin-content">

    <!-- 统计卡片 -->
    <div class="dashboard-grid">
        <div class="stat-card purple">
            <div class="stat-header">
                <div class="stat-icon">👥</div>
                <span class="stat-label">总用户数</span>
            </div>
            <div class="stat-value"><?php echo $userCount; ?></div>
            <div class="stat-change">封禁用户：<?php echo $bannedCount; ?></div>
        </div>
        <div class="stat-card blue">
            <div class="stat-header">
                <div class="stat-icon">💬</div>
                <span class="stat-label">反馈总数</span>
            </div>
            <div class="stat-value"><?php echo $feedbackCount; ?></div>
            <div class="stat-change" style="color:var(--warning);">待处理：<?php echo $pendingFb; ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-header">
                <div class="stat-icon">⭐</div>
                <span class="stat-label">收藏总数</span>
            </div>
            <div class="stat-value"><?php echo $favCount; ?></div>
            <div class="stat-change">公告：<?php echo $annCount; ?> 条</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-header">
                <div class="stat-icon">📺</div>
                <span class="stat-label">观看记录</span>
            </div>
            <div class="stat-value"><?php echo $historyCount; ?></div>
            <div class="stat-change">播放源：<?php echo $sourceCount; ?> 个</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:24px;">
        <!-- 最新注册用户 -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">🆕 最新注册用户</div>
                <a href="users.php" class="btn btn-outline btn-sm">查看全部</a>
            </div>
            <div class="panel-body" style="padding:0;">
                <table class="data-table">
                    <thead><tr><th>用户</th><th>邮箱</th><th>注册时间</th><th>状态</th></tr></thead>
                    <tbody>
                        <?php foreach ($newUsers as $u): ?>
                        <tr>
                            <td>
                                <div class="table-user">
                                    <div class="table-avatar">
                                        <?php if (!empty($u['avatar'])): ?>
                                            <img src="../<?php echo htmlspecialchars($u['avatar']); ?>" alt="">
                                        <?php else: echo mb_substr($u['username'], 0, 1); endif; ?>
                                    </div>
                                    <span><?php echo htmlspecialchars($u['username']); ?></span>
                                </div>
                            </td>
                            <td style="color:var(--text-muted);font-size:13px;"><?php echo htmlspecialchars($u['email']); ?></td>
                            <td style="color:var(--text-muted);font-size:13px;"><?php echo date('m-d H:i', strtotime($u['created_at'])); ?></td>
                            <td><span class="badge badge-success">正常</span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($newUsers)): ?><tr><td colspan="4" style="text-align:center;padding:32px;color:var(--text-muted);">暂无数据</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 最新反馈 -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">💬 最新反馈</div>
                <a href="feedbacks.php" class="btn btn-outline btn-sm">查看全部</a>
            </div>
            <div class="panel-body" style="padding:0;">
                <table class="data-table">
                    <thead><tr><th>用户</th><th>标题</th><th>时间</th><th>状态</th></tr></thead>
                    <tbody>
                        <?php foreach ($newFeedbacks as $f): ?>
                        <tr onclick="location.href='feedbacks.php#fb_<?php echo $f['id']; ?>'" style="cursor:pointer;">
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($f['username'] ?? 'U' . $f['user_id']); ?></span></td>
                            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($f['title']); ?></td>
                            <td style="color:var(--text-muted);font-size:13px;"><?php echo date('m-d H:i', strtotime($f['created_at'])); ?></td>
                            <td><?php echo $f['status'] ? '<span class="badge badge-success">已回复</span>' : '<span class="badge badge-warning">待处理</span>'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($newFeedbacks)): ?><tr><td colspan="4" style="text-align:center;padding:32px;color:var(--text-muted);">暂无数据</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 观看历史 -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">⏱️ 最新观看历史</div>
                <a href="history.php" class="btn btn-outline btn-sm">查看全部</a>
            </div>
            <div class="panel-body" style="padding:16px 20px;max-height:360px;overflow-y:auto;">
                <?php foreach ($newHistories as $h): ?>
                <div style="display:flex;gap:12px;padding:10px 0;border-bottom:1px solid var(--border-color);align-items:center;">
                    <div style="width:44px;aspect-ratio:2/3;border-radius:6px;overflow:hidden;background:var(--bg-dark);flex-shrink:0;">
                        <?php if (!empty($h['poster'])): ?>
                            <img src="<?php echo htmlspecialchars($h['poster']); ?>" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none'">
                        <?php endif; ?>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:500;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($h['title']); ?></div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:3px;">
                            用户：<span class="badge badge-info" style="font-size:10px;"><?php echo htmlspecialchars($h['username'] ?? 'U' . $h['user_id']); ?></span>
                            <?php if ($h['season_number'] > 0) echo " · S{$h['season_number']}E{$h['episode_number']}"; ?>
                        </div>
                    </div>
                    <div style="font-size:11px;color:var(--text-muted);flex-shrink:0;"><?php echo date('m-d H:i', strtotime($h['watched_at'])); ?></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($newHistories)): ?><div style="text-align:center;padding:32px;color:var(--text-muted);">暂无数据</div><?php endif; ?>
            </div>
        </div>

        <!-- 用户收藏 -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">⭐ 最新收藏</div>
                <a href="favorites.php" class="btn btn-outline btn-sm">查看全部</a>
            </div>
            <div class="panel-body" style="padding:16px 20px;max-height:360px;overflow-y:auto;">
                <?php foreach ($newFavorites as $f): ?>
                <div style="display:flex;gap:12px;padding:10px 0;border-bottom:1px solid var(--border-color);align-items:center;">
                    <div style="width:44px;aspect-ratio:2/3;border-radius:6px;overflow:hidden;background:var(--bg-dark);flex-shrink:0;">
                        <?php if (!empty($f['poster'])): ?>
                            <img src="<?php echo htmlspecialchars($f['poster']); ?>" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none'">
                        <?php endif; ?>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:500;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($f['title']); ?></div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:3px;">
                            用户：<span class="badge badge-info" style="font-size:10px;"><?php echo htmlspecialchars($f['username'] ?? 'U' . $f['user_id']); ?></span>
                            · <?php echo strtoupper($f['media_type']); ?>
                        </div>
                    </div>
                    <div style="font-size:11px;color:var(--text-muted);flex-shrink:0;"><?php echo date('m-d H:i', strtotime($f['created_at'])); ?></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($newFavorites)): ?><div style="text-align:center;padding:32px;color:var(--text-muted);">暂无数据</div><?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/admin_footer.php'; ?>
