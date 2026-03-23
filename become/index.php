<?php
/**
 * become/index.php — Training Dashboard — Skill Tree View
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

// Get all modules with their unlock rules for the skill tree
$db = Database::getInstance();
$s = $db->prepare("SELECT id, title, icon, folder_id, unlock_rule, module_order, xp_reward FROM modules WHERE is_active=1 ORDER BY module_order");
$s->execute();
$allModules = $s->fetchAll();

// Get completed module IDs
$compMods = $engine->getCompletedModuleIds($userId);
$compSegs = $engine->getCompletedSegmentIds($userId);

// Get thresholds
$s = $db->prepare("SELECT * FROM level_thresholds ORDER BY level");
$s->execute();
$thresholds = $s->fetchAll();

// Get folders for names
$s = $db->prepare("SELECT id, title, icon, folder_type FROM folders WHERE is_active=1");
$s->execute();
$folders = [];
foreach ($s->fetchAll() as $f) $folders[$f['id']] = $f;

// Get segment counts per module
$s = $db->prepare("SELECT module_id, COUNT(*) c FROM segments WHERE is_active=1 GROUP BY module_id");
$s->execute();
$segCounts = [];
foreach ($s->fetchAll() as $r) $segCounts[$r['module_id']] = (int)$r['c'];

// Get completed segment counts per module
$s = $db->prepare("SELECT sg.module_id, COUNT(*) c FROM completed_segments cs JOIN segments sg ON cs.segment_id=sg.id WHERE cs.user_id=? GROUP BY sg.module_id");
$s->execute([$userId]);
$compSegCounts = [];
foreach ($s->fetchAll() as $r) $compSegCounts[$r['module_id']] = (int)$r['c'];

// Group modules by level
$levelGroups = [];
$openModules = [];
$userLevel = (int)$stats['level'];

foreach ($allModules as &$m) {
    $rule = json_decode($m['unlock_rule'] ?? 'null', true);
    $m['_rule'] = $rule;
    $m['_level'] = ($rule && ($rule['kind'] ?? '') === 'level') ? (int)$rule['value'] : 0;
    $m['_open'] = $rule && ($rule['kind'] ?? '') === 'open';
    $m['_done'] = in_array((int)$m['id'], $compMods);
    $m['_segs'] = $segCounts[$m['id']] ?? 0;
    $m['_segs_done'] = $compSegCounts[$m['id']] ?? 0;
    $m['_folder'] = $folders[$m['folder_id']] ?? null;
    $m['_locked'] = !$m['_open'] && $m['_level'] > $userLevel;
    $m['_is_side'] = $m['_folder'] && ($m['_folder']['folder_type'] ?? 'core') === 'sidequest';
    
    if ($m['_open']) {
        $openModules[] = $m;
    } else {
        $lvl = $m['_level'];
        if (!isset($levelGroups[$lvl])) $levelGroups[$lvl] = [];
        $levelGroups[$lvl][] = $m;
    }
}
unset($m);
ksort($levelGroups);

// Level colors
$levelColors = ['#22A8B3', '#06D6A0', '#FFB703', '#FB9B47', '#8ECAE6', '#22A8B3', '#06D6A0', '#FFB703'];

// Content for gallery (reuse from engine)
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
<style>
/* ─── SKILL TREE STYLES ─── */
.tree-view{position:relative;z-index:1;max-width:600px;margin:0 auto;padding:0 1.5rem 4rem}

/* Gallery toggle button */
.gallery-toggle{
    position:fixed;top:1rem;left:1rem;z-index:50;
    background:rgba(34,168,179,0.15);border:1px solid rgba(34,168,179,0.3);
    color:var(--teal);padding:.5rem .9rem;border-radius:10px;
    font-weight:700;font-size:.82rem;cursor:pointer;font-family:var(--bf);
    backdrop-filter:blur(10px);transition:all .2s;display:flex;align-items:center;gap:.4rem;
}
.gallery-toggle:hover{background:rgba(34,168,179,0.25);transform:translateY(-1px)}
.gallery-toggle.active{background:var(--teal);color:#fff}

/* Top header */
.tree-header{text-align:center;padding:2rem 0 1rem;position:relative;z-index:2}
.tree-header h1{font-family:var(--hf);font-size:1.6rem;background:linear-gradient(135deg,var(--teal),var(--orange));-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:.25rem}
.tree-header .level-badge{display:inline-flex;align-items:center;gap:.4rem;padding:.35rem .85rem;border-radius:20px;font-size:.85rem;font-weight:600;margin-bottom:.5rem}
.tree-xp{margin:1rem auto;max-width:300px}
.tree-xp .xp-bar{height:8px;background:rgba(255,255,255,0.08);border-radius:4px;overflow:hidden}
.tree-xp .xp-fill{height:100%;border-radius:4px;transition:width .8s ease}
.tree-xp .xp-text{font-size:.75rem;color:var(--dim);text-align:center;margin-top:.3rem}

/* Leader nav */
.tree-nav{display:flex;justify-content:center;gap:.75rem;margin-bottom:1.5rem}
.tree-nav a{color:var(--dim);font-size:.82rem;text-decoration:none;transition:color .2s}
.tree-nav a:hover{color:var(--teal)}

/* The vertical path line */
.tree-path{position:relative;padding-left:40px}
.tree-path::before{
    content:'';position:absolute;left:18px;top:0;bottom:0;width:3px;
    background:linear-gradient(180deg,var(--teal) 0%,rgba(255,255,255,0.06) 100%);
    border-radius:2px;
}

/* Level gate */
.tree-gate{
    position:relative;margin:2rem 0 1.5rem -40px;text-align:center;
    padding:.6rem 1.5rem;border-radius:12px;
    font-family:var(--hf);font-size:1rem;font-weight:700;
    border:2px solid;z-index:2;
}
.tree-gate::before{content:'';position:absolute;left:50%;top:-2rem;width:3px;height:2rem;transform:translateX(-50%)}
.tree-gate--active{animation:gateGlow 2s ease infinite}
@keyframes gateGlow{0%,100%{box-shadow:0 0 15px var(--gate-color,rgba(34,168,179,0.3))}50%{box-shadow:0 0 30px var(--gate-color,rgba(34,168,179,0.5))}}

/* Node (module) */
.tree-node{position:relative;margin-bottom:1.25rem;display:flex;align-items:flex-start;gap:.75rem}
.tree-node__dot{
    position:relative;z-index:2;width:36px;height:36px;border-radius:50%;
    display:flex;align-items:center;justify-content:center;font-size:1rem;
    flex-shrink:0;margin-left:-18px;transition:all .3s;
    border:3px solid;
}
.tree-node--done .tree-node__dot{background:rgba(6,214,160,0.2);border-color:var(--green)}
.tree-node--active .tree-node__dot{border-color:var(--teal);animation:nodePulse 2s ease infinite}
.tree-node--locked .tree-node__dot{background:rgba(255,255,255,0.03);border-color:rgba(255,255,255,0.1)}
.tree-node--side .tree-node__dot{border-style:dashed}
@keyframes nodePulse{0%,100%{box-shadow:0 0 10px rgba(34,168,179,0.3)}50%{box-shadow:0 0 25px rgba(34,168,179,0.6)}}

.tree-node__card{
    flex:1;background:var(--card);border:1px solid var(--bdr);border-radius:10px;
    padding:.75rem 1rem;transition:all .3s;text-decoration:none;color:var(--txt);display:block;
}
.tree-node--done .tree-node__card{border-color:rgba(6,214,160,0.15);opacity:.7}
.tree-node--active .tree-node__card{border-color:var(--bdr-a);background:rgba(34,168,179,0.05)}
.tree-node--active .tree-node__card:hover{transform:translateX(4px);box-shadow:0 4px 20px rgba(34,168,179,0.15)}
.tree-node--locked .tree-node__card{opacity:.35;cursor:default}
.tree-node--side .tree-node__card{border-left:3px solid var(--gold);margin-left:.5rem}

.tree-node__title{font-weight:700;font-size:.9rem;margin-bottom:.15rem}
.tree-node__folder{font-size:.72rem;color:var(--dim)}
.tree-node__prog{display:flex;align-items:center;gap:.5rem;margin-top:.3rem}
.tree-node__prog-bar{flex:1;height:3px;background:rgba(255,255,255,0.08);border-radius:2px;overflow:hidden}
.tree-node__prog-fill{height:100%;border-radius:2px}
.tree-node__prog-text{font-size:.7rem;color:var(--dim);white-space:nowrap}

/* Side quest connector */
.tree-side-branch{position:relative;margin-left:0;margin-bottom:1rem;padding-left:20px;border-left:2px dashed rgba(255,183,3,0.2)}
.tree-side-label{font-size:.72rem;color:var(--gold);font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.5rem;padding-left:.25rem}

/* Gallery overlay */
.gallery-overlay{
    position:fixed;inset:0;z-index:40;background:rgba(10,10,15,0.97);
    backdrop-filter:blur(10px);overflow-y:auto;padding:1.5rem;
    transform:translateX(-100%);transition:transform .35s cubic-bezier(0.4,0,0.2,1);
}
.gallery-overlay.open{transform:translateX(0)}
.gallery-close{position:absolute;top:1rem;right:1.5rem;background:none;border:none;color:var(--dim);font-size:1.5rem;cursor:pointer}
.gallery-inner{max-width:900px;margin:0 auto;padding-top:1rem}
.gallery-title{font-family:var(--hf);font-size:1.4rem;margin-bottom:1rem}

/* Scroll indicator */
.scroll-to-current{
    position:fixed;bottom:2rem;right:1.5rem;z-index:30;
    background:var(--teal);color:#fff;border:none;border-radius:50%;
    width:48px;height:48px;display:flex;align-items:center;justify-content:center;
    font-size:1.2rem;cursor:pointer;box-shadow:0 4px 20px rgba(34,168,179,0.4);
    transition:transform .2s;
}
.scroll-to-current:hover{transform:translateY(-2px)}
</style>
</head>
<body>

<!-- GALLERY TOGGLE BUTTON -->
<button class="gallery-toggle" id="galleryBtn">📚 Library</button>

<!-- GALLERY OVERLAY -->
<div class="gallery-overlay" id="galleryOverlay">
    <button class="gallery-close" id="galleryClose">✕</button>
    <div class="gallery-inner">
        <h2 class="gallery-title">📚 Training Library</h2>
        <p style="color:var(--dim);margin-bottom:1.5rem;font-size:.9rem">All your training materials. Completed modules are accessible anytime for reference.</p>
        <div class="folder-grid">
            <?php foreach ($core as $f): ?><?= gallery_folder($f) ?><?php endforeach; ?>
            <?php foreach ($side as $f): ?><?= gallery_folder($f) ?><?php endforeach; ?>
        </div>
    </div>
</div>

<div class="portal">
    <!-- HEADER -->
    <div class="tree-header">
        <h1>Level Up, <?= $name ?></h1>
        <div class="level-badge" style="background:<?= $levelColors[$userLevel % count($levelColors)] ?>22;color:<?= $levelColors[$userLevel % count($levelColors)] ?>;border:1px solid <?= $levelColors[$userLevel % count($levelColors)] ?>44">
            <?= $stats['level_icon'] ?> Level <?= $stats['level'] ?> — <?= $stats['level_title'] ?>
        </div>
        <div class="tree-xp">
            <div class="xp-bar"><div class="xp-fill" style="width:<?= $stats['level_progress'] ?>%;background:<?= $levelColors[$userLevel % count($levelColors)] ?>"></div></div>
            <div class="xp-text">
                <?php if ($stats['next_level_title']): ?>
                    <?= $stats['xp_in_level'] ?> / <?= $stats['xp_for_next_level'] ?> XP to <?= $stats['next_level_icon'] ?> <?= $stats['next_level_title'] ?>
                <?php else: ?>
                    <?= number_format($stats['xp']) ?> XP — Max level!
                <?php endif; ?>
            </div>
        </div>
        <div class="tree-nav">
            <?php if ($isLeader): ?><a href="/become/manage.php">⚙️ Manage</a><?php endif; ?>
            <a href="/become/logout.php">Log Out</a>
        </div>
    </div>

    <!-- SKILL TREE -->
    <div class="tree-view">
        <div class="tree-path">
            <?php
            $prevLevel = -1;
            $currentNodeId = null;
            
            // Find the current active module (first incomplete, unlocked)
            foreach ($levelGroups as $lvl => $mods) {
                foreach ($mods as $m) {
                    if (!$m['_done'] && !$m['_locked'] && !$m['_is_side']) {
                        $currentNodeId = $m['id'];
                        break 2;
                    }
                }
            }
            
            foreach ($levelGroups as $lvl => $mods):
                $th = null;
                foreach ($thresholds as $t) { if ((int)$t['level'] === $lvl) $th = $t; }
                $color = $levelColors[$lvl % count($levelColors)];
                $isCurrentLevel = ($lvl === $userLevel);
                $isPast = ($lvl < $userLevel);
                $isFuture = ($lvl > $userLevel);
                
                // Separate core and side quest modules
                $coreMods = array_filter($mods, fn($m) => !$m['_is_side']);
                $sideMods = array_filter($mods, fn($m) => $m['_is_side']);
            ?>
                
                <!-- LEVEL GATE -->
                <div class="tree-gate <?= $isCurrentLevel ? 'tree-gate--active' : '' ?>"
                     style="background:<?= $color ?>15;border-color:<?= $color ?><?= $isFuture ? '33' : '' ?>;color:<?= $color ?><?= $isFuture ? '66' : '' ?>;--gate-color:<?= $color ?>44"
                     <?= $isCurrentLevel ? 'id="current-level"' : '' ?>>
                    <?= $th ? $th['badge_icon'] : '' ?> Level <?= $lvl ?> <?= $th ? '— ' . htmlspecialchars($th['title']) : '' ?>
                    <?php if ($isFuture && $th): ?>
                        <span style="font-family:var(--bf);font-size:.7rem;font-weight:400;display:block;opacity:.6"><?= $th['xp_required'] ?> XP to unlock</span>
                    <?php endif; ?>
                </div>

                <!-- CORE MODULES -->
                <?php foreach ($coreMods as $m):
                    $isDone = $m['_done'];
                    $isActive = !$isDone && !$m['_locked'] && ((int)$m['id'] === (int)$currentNodeId || $lvl <= $userLevel);
                    $isCurrent = (int)$m['id'] === (int)$currentNodeId;
                    $folder = $m['_folder'];
                    $pct = $m['_segs'] > 0 ? round(($m['_segs_done'] / $m['_segs']) * 100) : 0;
                    
                    $nodeCls = 'tree-node';
                    if ($isDone) $nodeCls .= ' tree-node--done';
                    elseif ($isActive) $nodeCls .= ' tree-node--active';
                    else $nodeCls .= ' tree-node--locked';
                ?>
                <div class="<?= $nodeCls ?>" <?= $isCurrent ? 'id="current-node"' : '' ?>>
                    <div class="tree-node__dot" style="<?= $isDone ? '' : ($isActive ? "border-color:{$color}" : '') ?>">
                        <?= $isDone ? '✅' : ($m['_locked'] ? '🔒' : ($m['icon'] ?: '📄')) ?>
                    </div>
                    <?php if (!$m['_locked']): ?>
                    <a href="/become/module.php?id=<?= $m['id'] ?>" class="tree-node__card">
                    <?php else: ?>
                    <div class="tree-node__card">
                    <?php endif; ?>
                        <div class="tree-node__title"><?= htmlspecialchars($m['title']) ?></div>
                        <div class="tree-node__folder"><?= $folder ? ($folder['icon'] ?? '📁') . ' ' . htmlspecialchars($folder['title']) : '' ?></div>
                        <?php if (!$m['_locked'] && !$isDone && $m['_segs'] > 0): ?>
                        <div class="tree-node__prog">
                            <div class="tree-node__prog-bar"><div class="tree-node__prog-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div></div>
                            <span class="tree-node__prog-text"><?= $m['_segs_done'] ?>/<?= $m['_segs'] ?></span>
                        </div>
                        <?php elseif ($m['_locked']): ?>
                        <div class="tree-node__folder" style="margin-top:.2rem">🔒 Unlocks at Level <?= $m['_level'] ?></div>
                        <?php endif; ?>
                    <?php if (!$m['_locked']): ?>
                    </a>
                    <?php else: ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <!-- SIDE QUESTS (branching off) -->
                <?php if ($sideMods): ?>
                <div class="tree-side-branch">
                    <div class="tree-side-label">🗺️ Side Quest<?= count($sideMods) > 1 ? 's' : '' ?></div>
                    <?php foreach ($sideMods as $m):
                        $isDone = $m['_done'];
                        $isActive = !$isDone && !$m['_locked'];
                        $folder = $m['_folder'];
                        $nodeCls = 'tree-node tree-node--side';
                        if ($isDone) $nodeCls .= ' tree-node--done';
                        elseif ($isActive) $nodeCls .= ' tree-node--active';
                        else $nodeCls .= ' tree-node--locked';
                    ?>
                    <div class="<?= $nodeCls ?>">
                        <div class="tree-node__dot" style="border-color:var(--gold)">
                            <?= $isDone ? '✅' : ($m['_locked'] ? '🔒' : '🗺️') ?>
                        </div>
                        <?php if (!$m['_locked']): ?>
                        <a href="/become/module.php?id=<?= $m['id'] ?>" class="tree-node__card">
                        <?php else: ?>
                        <div class="tree-node__card">
                        <?php endif; ?>
                            <div class="tree-node__title"><?= htmlspecialchars($m['title']) ?></div>
                            <div class="tree-node__folder"><?= $folder ? ($folder['icon'] ?? '📁') . ' ' . htmlspecialchars($folder['title']) : '' ?> · Optional</div>
                        <?php echo !$m['_locked'] ? '</a>' : '</div>'; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

            <?php endforeach; ?>

            <!-- OPEN TO ALL (at the bottom) -->
            <?php if ($openModules): ?>
            <div class="tree-gate" style="background:rgba(255,255,255,0.03);border-color:rgba(255,255,255,0.1);color:var(--dim);margin-top:2rem">
                📚 Always Available
            </div>
            <?php foreach ($openModules as $m):
                $folder = $m['_folder'];
                $isDone = $m['_done'];
            ?>
            <div class="tree-node <?= $isDone ? 'tree-node--done' : 'tree-node--active' ?>">
                <div class="tree-node__dot" style="border-color:var(--dim)"><?= $isDone ? '✅' : ($m['icon'] ?: '📄') ?></div>
                <a href="/become/module.php?id=<?= $m['id'] ?>" class="tree-node__card">
                    <div class="tree-node__title"><?= htmlspecialchars($m['title']) ?></div>
                    <div class="tree-node__folder"><?= $folder ? ($folder['icon'] ?? '📁') . ' ' . htmlspecialchars($folder['title']) : '' ?> · Reference</div>
                </a>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

        </div><!-- .tree-path -->
    </div><!-- .tree-view -->
</div><!-- .portal -->

<!-- Scroll to current button -->
<button class="scroll-to-current" id="scrollBtn" title="Jump to current">📍</button>

<script data-cfasync="false" src="/become/portal.js"></script>
<script data-cfasync="false">
// Gallery toggle
document.getElementById('galleryBtn').addEventListener('click', function() {
    document.getElementById('galleryOverlay').classList.toggle('open');
    this.classList.toggle('active');
});
document.getElementById('galleryClose').addEventListener('click', function() {
    document.getElementById('galleryOverlay').classList.remove('open');
    document.getElementById('galleryBtn').classList.remove('active');
});

// Scroll to current node on load
var currentNode = document.getElementById('current-node') || document.getElementById('current-level');
if (currentNode) {
    setTimeout(function() {
        currentNode.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 500);
}

// Scroll button
document.getElementById('scrollBtn').addEventListener('click', function() {
    var target = document.getElementById('current-node') || document.getElementById('current-level');
    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
});
</script>
</body>
</html>
<?php
// Gallery folder card (reused from previous version)
function gallery_folder($f) {
    $id = (int)$f['id'];
    $locked = !empty($f['locked']);
    $icon = htmlspecialchars($f['icon'] ?? '📁');
    $title = htmlspecialchars($f['title'] ?? '');
    $desc = htmlspecialchars($f['description'] ?? '');
    $pct = (int)($f['progress'] ?? 0);
    $mt = (int)($f['modules_total'] ?? 0);
    $md = (int)($f['modules_completed'] ?? 0);

    $cls = 'gal-folder';
    if ($locked) $cls .= ' gal-folder--locked';
    if ($pct >= 100 && $mt > 0) $cls .= ' gal-folder--done';

    $o = "<div class='{$cls}' data-id='{$id}'>";
    $o .= "<div class='gal-folder__top'><span class='gal-folder__icon'>{$icon}</span>";
    if ($locked) $o .= "<span class='gal-folder__lock'>🔒</span>";
    elseif ($mt > 0) $o .= "<span class='gal-folder__pct'>{$pct}%</span>";
    $o .= "</div>";
    if (!$locked && $mt > 0) $o .= "<div class='gal-folder__bar'><div class='gal-folder__bar-fill' style='width:{$pct}%'></div></div>";
    $o .= "<h3 class='gal-folder__title'>{$title}</h3>";
    if ($desc) $o .= "<p class='gal-folder__desc'>{$desc}</p>";
    $o .= "<span class='gal-folder__meta'>{$md}/{$mt} modules</span>";
    $o .= "<div class='gal-folder__mods'>";
    foreach ($f['modules'] ?? [] as $m) {
        $mid = (int)$m['id'];
        $mLocked = !empty($m['locked']);
        $mDone = !empty($m['completed']);
        $mTitle = htmlspecialchars($m['title'] ?? '');
        $mIco = $mLocked ? '🔒' : ($mDone ? '✅' : '📖');
        $mCls = 'gal-mod' . ($mLocked ? ' gal-mod--locked' : '') . ($mDone ? ' gal-mod--done' : '');
        $mTag = $mLocked ? 'div' : 'a';
        $mHref = $mLocked ? '' : " href='/become/module.php?id={$mid}'";
        $o .= "<{$mTag} class='{$mCls}'{$mHref}><span class='gal-mod__ico'>{$mIco}</span><div class='gal-mod__info'><span class='gal-mod__title'>{$mTitle}</span></div></{$mTag}>";
    }
    $o .= "</div></div>";
    return $o;
}
?>
