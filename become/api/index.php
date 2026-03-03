<?php
/**
 * become/index.php — Training Dashboard
 * Location: public_html/become/index.php
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ProgressionEngine.php';

$engine   = new ProgressionEngine();
$userId   = (int)$current_user['id'];
$stats    = $engine->getUserStats($userId);
$next     = $engine->resolveNextAction($userId);
$content  = $engine->getAccessibleContent($userId);
$isLeader = is_leader();
$name     = htmlspecialchars($current_user['first_name'] ?: $current_user['username']);

$core = array_filter($content, fn($f) => ($f['folder_type'] ?? 'core') === 'core');
$side = array_filter($content, fn($f) => ($f['folder_type'] ?? 'core') === 'sidequest');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become — Your Energy Best</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/become/portal.css">
</head>
<body>
<div class="portal">

    <!-- HEADER -->
    <header class="hdr">
        <div>
            <h1 class="hdr-title">Level Up, <?= $name ?></h1>
            <p class="hdr-sub"><?= $stats['level_icon'] ?> Level <?= $stats['level'] ?> — <?= $stats['level_title'] ?></p>
        </div>
        <div class="hdr-right">
            <?php if ($isLeader): ?><span class="badge-leader">✏️ Leader</span><?php endif; ?>
            <a href="/become/logout.php" class="hdr-logout">Log Out</a>
        </div>
    </header>

    <!-- XP BAR -->
    <div class="card xp-card">
        <div class="xp-top">
            <span class="xp-label"><?= $stats['level_icon'] ?> Level <?= $stats['level'] ?></span>
            <span class="xp-num"><?= number_format($stats['xp']) ?> XP</span>
        </div>
        <div class="bar"><div class="bar-fill" style="width:<?= $stats['level_progress'] ?>%"></div></div>
        <p class="xp-hint">
            <?php if ($stats['next_level_title']): ?>
                <?= $stats['xp_in_level'] ?> / <?= $stats['xp_for_next_level'] ?> XP to <?= $stats['next_level_icon'] ?> <?= $stats['next_level_title'] ?>
            <?php else: ?>
                Max level reached!
            <?php endif; ?>
        </p>
    </div>

    <!-- NEXT ACTION -->
    <?php if ($next): ?>
    <div class="card next-card">
        <div class="next-top"><span class="next-icon">🎯</span><span class="next-label">NEXT ACTION</span></div>
        <p class="next-text"><?= htmlspecialchars($next['label']) ?></p>
        <?php if (($next['type'] ?? '') === 'segment'): ?>
            <a href="/become/module.php?id=<?= $next['module_id'] ?>#seg-<?= $next['segment_id'] ?>" class="btn-teal">Continue →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- QUICK STATS -->
    <div class="stats-grid">
        <div class="stat"><span class="stat-n"><?= $stats['completed_segments'] ?></span><span class="stat-l">Segments</span></div>
        <div class="stat"><span class="stat-n"><?= $stats['completed_modules'] ?></span><span class="stat-l">Modules</span></div>
        <div class="stat"><span class="stat-n"><?= $stats['completed_folders'] ?></span><span class="stat-l">Folders</span></div>
        <div class="stat"><span class="stat-n"><?= $stats['days_active'] ?></span><span class="stat-l">Days</span></div>
    </div>

    <!-- CORE TRAINING -->
    <section class="section">
        <h2 class="sec-title">📚 Core Training</h2>
        <?php foreach ($core as $f): ?><?= folder_card($f) ?><?php endforeach; ?>
    </section>

    <!-- SIDE QUESTS -->
    <?php if ($side): ?>
    <section class="section sq-section">
        <h2 class="sec-title">🗺️ Side Quests</h2>
        <p class="sec-sub">Bonus training that unlocks over time</p>
        <?php foreach ($side as $f): ?><?= folder_card($f) ?><?php endforeach; ?>
    </section>
    <?php endif; ?>

</div>
<script src="/become/portal.js"></script>
</body>
</html>
<?php

function folder_card($f, $depth = 0) {
    $id = (int)$f['id'];
    $locked = !empty($f['locked']);
    $icon = htmlspecialchars($f['icon'] ?? '📁');
    $title = htmlspecialchars($f['title'] ?? '');
    $pct = (int)($f['progress'] ?? 0);
    $mt = (int)($f['modules_total'] ?? 0);
    $md = (int)($f['modules_completed'] ?? 0);
    $isSQ = ($f['folder_type'] ?? '') === 'sidequest';

    $cls = 'folder';
    if ($locked) $cls .= ' folder--locked';
    if ($isSQ)   $cls .= ' folder--sq';
    if ($depth)  $cls .= ' folder--child';

    $o = "<div class='{$cls}' data-id='{$id}'>";

    if ($locked) $o .= "<div class='folder-lock'>🔒</div>";

    $o .= "<div class='folder-hdr' onclick='toggleFolder(this)'>";
    $o .= "<span class='folder-icon'>{$icon}</span>";
    $o .= "<div class='folder-info'><h3>{$title}</h3><span class='folder-meta'>{$md}/{$mt} modules</span></div>";
    if (!$locked && $mt) $o .= "<span class='folder-pct'>{$pct}%</span>";
    $o .= "<span class='folder-arrow'>▸</span>";
    $o .= "</div>";

    if (!$locked) {
        $o .= "<div class='bar bar--sm'><div class='bar-fill' style='width:{$pct}%'></div></div>";
        $o .= "<div class='folder-body'>";
        foreach ($f['modules'] ?? [] as $m) $o .= module_item($m);

        // Children (subfolders)
        foreach ($f['children'] ?? [] as $child) $o .= folder_card($child, $depth + 1);

        $o .= "</div>";
    }

    $o .= "</div>";
    return $o;
}

function module_item($m) {
    $id = (int)$m['id'];
    $locked = !empty($m['locked']);
    $done = !empty($m['completed']);
    $title = htmlspecialchars($m['title'] ?? '');
    $pct = (int)($m['progress'] ?? 0);
    $st = (int)($m['segments_total'] ?? 0);
    $sd = (int)($m['segments_completed'] ?? 0);

    $ico = $locked ? '🔒' : ($done ? '✅' : '📖');
    $cls = 'mod';
    if ($locked) $cls .= ' mod--locked';
    if ($done)   $cls .= ' mod--done';

    $tag = $locked ? 'div' : 'a';
    $href = $locked ? '' : " href='/become/module.php?id={$id}'";

    $o = "<{$tag} class='{$cls}'{$href}>";
    $o .= "<span class='mod-ico'>{$ico}</span>";
    $o .= "<div class='mod-info'><span class='mod-title'>{$title}</span>";
    if (!$locked) $o .= "<span class='mod-meta'>{$sd}/{$st} segments</span>";
    $o .= "</div>";
    if (!$locked && !$done && $st) $o .= "<div class='mod-bar'><div class='mod-bar-fill' style='width:{$pct}%'></div></div>";
    if (!$locked && !$done) $o .= "<span class='mod-xp'>+{$m['xp_reward']} XP</span>";
    $o .= "</{$tag}>";
    return $o;
}
?>
