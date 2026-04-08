<?php
/**
 * become/index.php — Skill Tree Dashboard
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ProgressionEngine.php';

$engine = new ProgressionEngine();
$userId = (int)$current_user['id'];
$stats  = $engine->getUserStats($userId);
$next   = $engine->resolveNextAction($userId);
$content = $engine->getAccessibleContent($userId);
$isLeader = is_leader();
$name = htmlspecialchars($current_user['first_name'] ?: $current_user['username']);
$userLevel = (int)$stats['level'];

$db = Database::getInstance();

// All modules
$s = $db->prepare("SELECT * FROM modules WHERE is_active=1 ORDER BY module_order");
$s->execute();
$allMods = $s->fetchAll();
$compMods = $engine->getCompletedModuleIds($userId);

// Thresholds
$s = $db->prepare("SELECT * FROM level_thresholds ORDER BY level");
$s->execute();
$thresholds = $s->fetchAll();

// Folders
$s = $db->prepare("SELECT id, title, icon, folder_type FROM folders WHERE is_active=1");
$s->execute();
$fMap = [];
foreach ($s->fetchAll() as $f) $fMap[$f['id']] = $f;

// Segment counts
$s = $db->prepare("SELECT module_id, COUNT(*) c FROM segments WHERE is_active=1 GROUP BY module_id");
$s->execute();
$segC = [];
foreach ($s->fetchAll() as $r) $segC[$r['module_id']] = (int)$r['c'];

$s = $db->prepare("SELECT sg.module_id, COUNT(*) c FROM completed_segments cs JOIN segments sg ON cs.segment_id=sg.id WHERE cs.user_id=? GROUP BY sg.module_id");
$s->execute([$userId]);
$doneC = [];
foreach ($s->fetchAll() as $r) $doneC[$r['module_id']] = (int)$r['c'];

// Group by level
$lvlGroups = [];
$openMods = [];
foreach ($allMods as &$m) {
    $r = json_decode($m['unlock_rule'] ?? 'null', true);
    $m['_lvl'] = ($r && ($r['kind'] ?? '') === 'level') ? (int)$r['value'] : 0;
    $m['_open'] = $r && ($r['kind'] ?? '') === 'open';
    $m['_done'] = in_array((int)$m['id'], $compMods);
    $m['_st'] = $segC[$m['id']] ?? 0;
    $m['_sd'] = $doneC[$m['id']] ?? 0;
    $m['_pct'] = $m['_st'] > 0 ? round(($m['_sd'] / $m['_st']) * 100) : 0;
    $m['_f'] = $fMap[$m['folder_id']] ?? null;
    $m['_side'] = $m['_f'] && ($m['_f']['folder_type'] ?? '') === 'sidequest';
    $m['_prereqs'] = json_decode($m['prerequisites'] ?? 'null', true);
    $m['_locked'] = false; // computed below
    if ($m['_open']) $openMods[] = $m;
    else { $lvlGroups[$m['_lvl']][] = $m; }
}
unset($m);
ksort($lvlGroups);

// Compute locked state: stage-based within each level
// Same module_order = same stage (parallel, all must complete)
// Higher module_order = later stage (locked until previous stage done)
foreach ($lvlGroups as $lvl => &$mods) {
    $levelLocked = $lvl > $userLevel;
    if ($levelLocked) {
        foreach ($mods as &$m) { $m['_locked'] = true; }
        unset($m);
        continue;
    }

    // Group by stage (module_order)
    $stages = [];
    foreach ($mods as &$m) {
        $stage = (int)($m['module_order'] ?? 1);
        $m['_stage'] = $stage;
        if (!isset($stages[$stage])) $stages[$stage] = [];
        $stages[$stage][] = &$m;
    }
    unset($m);
    ksort($stages);

    $prevStageDone = true; // first stage is always unlocked (within an unlocked level)
    foreach ($stages as $stageNum => $stageMods) {
        // Check if all mods in this stage are done
        $allDone = true;
        foreach ($stageMods as &$sm) {
            if ($sm['_open']) {
                $sm['_locked'] = false;
            } elseif ($sm['_side']) {
                $sm['_locked'] = false; // side quests don't block
            } else {
                $sm['_locked'] = !$prevStageDone;
            }
            if (!$sm['_done']) $allDone = false;
        }
        unset($sm);
        // Only advance to next stage if ALL non-side modules in this stage are done
        $coreInStage = array_filter($stageMods, fn($m) => !$m['_side'] && !$m['_open']);
        $coreDone = !count($coreInStage) || !array_filter($coreInStage, fn($m) => !$m['_done']);
        $prevStageDone = $coreDone ? true : false;
    }
}
unset($mods);

$colors = ['#22A8B3','#06D6A0','#FFB703','#FB9B47','#8ECAE6','#22A8B3','#06D6A0','#FFB703'];
$curColor = $colors[$userLevel % count($colors)];

// Find current node (first unlocked, incomplete, non-side module)
$currentId = null;
foreach ($lvlGroups as $mods) {
    foreach ($mods as $m) {
        if (!$m['_done'] && !$m['_locked'] && !$m['_side']) { $currentId = (int)$m['id']; break 2; }
    }
}
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
/* ═══ SCROLL REVEAL ═══ */
.reveal{opacity:0;transform:translateY(50px);transition:opacity .7s ease,transform .7s ease}
.reveal.visible{opacity:1;transform:translateY(0)}
.reveal-left{opacity:0;transform:translateX(-40px);transition:opacity .6s ease,transform .6s ease}
.reveal-left.visible{opacity:1;transform:translateX(0)}
.reveal-right{opacity:0;transform:translateX(40px);transition:opacity .6s ease,transform .6s ease}
.reveal-right.visible{opacity:1;transform:translateX(0)}
.reveal-scale{opacity:0;transform:scale(.85);transition:opacity .5s ease,transform .5s ease}
.reveal-scale.visible{opacity:1;transform:scale(1)}

/* ═══ LAYOUT ═══ */
.sk{position:relative;z-index:1;max-width:520px;margin:0 auto;padding:0 1rem 6rem}

/* Library button */
.lib-btn{position:fixed;top:1rem;left:1rem;z-index:50;background:rgba(34,168,179,.15);border:1px solid rgba(34,168,179,.3);color:var(--teal);padding:.75rem 1.25rem;border-radius:12px;font-weight:700;font-size:1rem;cursor:pointer;font-family:var(--bf);backdrop-filter:blur(12px);display:flex;align-items:center;gap:.5rem;transition:all .2s;box-shadow:0 4px 16px rgba(34,168,179,.15)}
.lib-btn:hover{background:rgba(34,168,179,.25);transform:translateY(-2px);box-shadow:0 6px 24px rgba(34,168,179,.25)}

/* Header */
.sk-hdr{text-align:center;padding:3rem 0 2rem;position:relative}
.sk-hdr h1{font-family:var(--hf);font-size:1.5rem;background:linear-gradient(135deg,var(--teal),var(--orange));-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:.3rem}
.sk-badge{display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .75rem;border-radius:16px;font-size:.82rem;font-weight:600}
.sk-xp{max-width:280px;margin:.75rem auto 0}
.sk-xp-bar{height:6px;background:rgba(255,255,255,.07);border-radius:3px;overflow:hidden}
.sk-xp-fill{height:100%;border-radius:3px;transition:width .8s}
.sk-xp-txt{font-size:.72rem;color:var(--dim);text-align:center;margin-top:.25rem}
.sk-nav{display:flex;justify-content:center;gap:.75rem;margin-top:.75rem}
.sk-nav a{color:var(--dim);font-size:.8rem;text-decoration:none}
.sk-nav a:hover{color:var(--teal)}

/* ═══ PATH ═══ */
.path{position:relative;padding-left:28px}
.path::before{content:'';position:absolute;left:13px;top:0;bottom:0;width:2px;background:rgba(255,255,255,.06);border-radius:1px}
/* Animated path fill */
.path-glow{position:absolute;left:13px;top:0;width:2px;border-radius:1px;transition:height 1.5s ease;z-index:1}

/* Level Gate */
/* Collapsible levels */
.level-group{position:relative}
.level-content{transition:max-height .4s ease,opacity .3s ease;overflow:hidden}
.level--collapsed .level-content{max-height:0;opacity:0}
.level--collapsed .gate__badge{opacity:.6}
.gate{position:relative;margin:2rem 0 1rem -28px;text-align:center;z-index:3;cursor:pointer}
.gate__line{height:2px;margin-bottom:-.5rem}
.gate__badge{display:inline-block;padding:.55rem 1.5rem;border-radius:14px;font-family:var(--hf);font-size:.95rem;border:2px solid;position:relative;backdrop-filter:blur(8px)}
.gate--now .gate__badge{animation:gatePulse 2.5s ease infinite}
@keyframes gatePulse{0%,100%{box-shadow:0 0 15px var(--gc,rgba(34,168,179,.3))}50%{box-shadow:0 0 35px var(--gc,rgba(34,168,179,.5))}}
.gate--future .gate__badge{opacity:.4}
.gate__sub{display:block;font-family:var(--bf);font-size:.65rem;font-weight:400;opacity:.6;margin-top:.15rem}

/* Node */
.node{position:relative;display:flex;gap:.65rem;margin-bottom:.85rem;align-items:flex-start}
.node__dot{position:relative;z-index:2;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0;margin-left:-14px;border:2px solid;transition:all .4s}
.node--done .node__dot{background:rgba(6,214,160,.15);border-color:var(--green)}
.node--now .node__dot{animation:dotPulse 2s ease infinite}
@keyframes dotPulse{0%,100%{box-shadow:0 0 8px var(--nc,rgba(34,168,179,.3))}50%{box-shadow:0 0 22px var(--nc,rgba(34,168,179,.6))}}
.node--locked .node__dot{background:rgba(255,255,255,.02);border-color:rgba(255,255,255,.08)}
.node--side .node__dot{border-style:dashed}

.node__card{flex:1;background:var(--card);border:1px solid var(--bdr);border-radius:10px;padding:.65rem .85rem;transition:all .3s;text-decoration:none;color:var(--txt);display:block;min-width:0}
.node--now .node__card{border-color:var(--bdr-a);background:rgba(34,168,179,.04)}
.node--now .node__card:hover{transform:translateX(3px);box-shadow:0 4px 16px rgba(34,168,179,.12)}
.node--done .node__card{opacity:.55}
.node--locked .node__card{opacity:.25;cursor:default}
.node--side .node__card{border-left:2px solid var(--gold);opacity:.7}
.node--side.node--locked .node__card{opacity:.2}

.node__title{font-weight:700;font-size:.85rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.node__sub{font-size:.7rem;color:var(--dim);margin-top:.1rem}
.node__bar{height:3px;background:rgba(255,255,255,.07);border-radius:2px;overflow:hidden;margin-top:.3rem}
.node__bar-fill{height:100%;border-radius:2px;transition:width .6s}

/* Side quest branch */
.side-branch{margin:.5rem 0 1rem;padding-left:1rem;border-left:1px dashed rgba(255,183,3,.2)}
.side-label{font-size:.68rem;color:var(--gold);font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.4rem}

/* Branch group — parallel modules */
.branch-group{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.5rem;margin-bottom:.85rem;padding:.5rem;background:rgba(255,255,255,.015);border-radius:12px;border:1px dashed rgba(255,255,255,.06)}
.branch-group .node{margin-bottom:.25rem}
.branch-group .node__dot{margin-left:0;width:24px;height:24px;font-size:.7rem}
.branch-group .node__card{padding:.5rem .65rem}
.branch-group .node__title{font-size:.82rem}
.branch-group .node__sub{font-size:.65rem}
.branch-label{font-size:.7rem;color:var(--teal);font-weight:600;text-align:center;margin-bottom:.35rem;opacity:.7}
.merge-label{font-size:.65rem;color:var(--dim);text-align:center;margin:.25rem 0 .65rem;font-style:italic}

/* ═══ LIBRARY OVERLAY ═══ */
.lib{position:fixed;inset:0;z-index:40;background:rgba(10,10,15,.97);backdrop-filter:blur(12px);overflow-y:auto;padding:1.5rem;transform:translateX(-100%);transition:transform .35s cubic-bezier(.4,0,.2,1)}
.lib.open{transform:translateX(0)}
.lib__close{position:fixed;top:1rem;right:1.5rem;background:rgba(255,255,255,.08);border:none;color:var(--dim);font-size:1.2rem;cursor:pointer;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;z-index:41}
.lib__inner{max-width:800px;margin:0 auto;padding-top:1rem}
.lib__title{font-family:var(--hf);font-size:1.3rem;margin-bottom:.75rem}
.lib-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:.75rem}
.lib-card{background:var(--card);border:1px solid var(--bdr);border-radius:10px;padding:1rem;transition:all .2s}
.lib-card:hover{border-color:var(--bdr-a)}
.lib-card--locked{opacity:.35}
.lib-card__icon{font-size:1.5rem;margin-bottom:.4rem}
.lib-card__title{font-weight:700;font-size:.88rem;margin-bottom:.15rem}
.lib-card__meta{font-size:.72rem;color:var(--dim)}
.lib-card__mods{margin-top:.5rem;border-top:1px solid rgba(255,255,255,.04);padding-top:.4rem;display:none}
.lib-card.expanded .lib-card__mods{display:block}
.lib-mod{display:flex;align-items:center;gap:.4rem;padding:.3rem;font-size:.78rem;border-radius:4px;text-decoration:none;color:var(--txt);transition:background .2s}
.lib-mod:hover{background:rgba(255,255,255,.04)}
.lib-mod--locked{opacity:.35}

/* Scroll button */
.scroll-btn{position:fixed;bottom:1.5rem;right:1.5rem;z-index:30;background:var(--teal);color:#fff;border:none;border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;font-size:1rem;cursor:pointer;box-shadow:0 4px 16px rgba(34,168,179,.35);transition:transform .2s}
.scroll-btn:hover{transform:translateY(-2px)}

@media(max-width:500px){
    .sk{padding:0 .5rem 4rem}
    .node__card{padding:.6rem .7rem}
    .node__title{font-size:.88rem}
    .gate__badge{padding:.5rem 1.1rem;font-size:.88rem}
    .lib-btn{top:.75rem;left:.75rem;padding:.65rem 1rem;font-size:.95rem}
    .branch-group{grid-template-columns:1fr;padding:.4rem}
    .sk-hdr h1{font-size:1.3rem}
    .scroll-btn{width:40px;height:40px;bottom:1rem;right:1rem}
    .node{margin-bottom:1rem}
    .node__dot{width:30px;height:30px;font-size:.85rem}
}
</style>
</head>
<body>

<button class="lib-btn" id="libBtn">📚 Library</button>

<!-- LIBRARY OVERLAY -->
<div class="lib" id="libPanel">
<button class="lib__close" id="libClose">✕</button>
<div class="lib__inner">
<h2 class="lib__title">📚 Training Library</h2>
<p style="color:var(--dim);font-size:.85rem;margin-bottom:1.25rem">Browse all training. Completed modules available for reference.</p>
<div class="lib-grid">
<?php foreach ($content as $f):
    $fLocked = !empty($f['locked']);
    $fPct = (int)($f['progress'] ?? 0);
    $fMt = (int)($f['modules_total'] ?? 0);
    $fMd = (int)($f['modules_completed'] ?? 0);
?>
<div class="lib-card <?= $fLocked ? 'lib-card--locked' : '' ?>" data-lib-card>
    <div class="lib-card__icon"><?= htmlspecialchars($f['icon'] ?? '📁') ?></div>
    <div class="lib-card__title"><?= htmlspecialchars($f['title'] ?? '') ?></div>
    <div class="lib-card__meta"><?= $fMd ?>/<?= $fMt ?> modules <?= !$fLocked && $fMt ? "· {$fPct}%" : '' ?></div>
    <?php if (!$fLocked && $fMt): ?>
    <div style="height:3px;background:rgba(255,255,255,.06);border-radius:2px;margin-top:.4rem;overflow:hidden"><div style="height:100%;width:<?= $fPct ?>%;background:var(--teal);border-radius:2px"></div></div>
    <?php endif; ?>
    <div class="lib-card__mods">
    <?php foreach ($f['modules'] ?? [] as $m):
        $mL = !empty($m['locked']); $mD = !empty($m['completed']);
    ?>
    <?php if (!$mL): ?>
    <a class="lib-mod <?= $mD?'lib-mod--done':'' ?>" href="/become/module.php?id=<?= $m['id'] ?>"><?= $mD?'✅':'📖' ?> <?= htmlspecialchars($m['title']) ?></a>
    <?php else: ?>
    <div class="lib-mod lib-mod--locked">🔒 <?= htmlspecialchars($m['title']) ?></div>
    <?php endif; ?>
    <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
</div>
</div>

<div class="portal">
<div class="sk">

<!-- HEADER -->
<div class="sk-hdr reveal">
    <h1>Level Up, <?= $name ?></h1>
    <div class="sk-badge" style="background:<?= $curColor ?>18;color:<?= $curColor ?>;border:1px solid <?= $curColor ?>33">
        <?= $stats['level_icon'] ?> Level <?= $userLevel ?> — <?= $stats['level_title'] ?>
    </div>
    <div class="sk-xp">
        <div class="sk-xp-bar"><div class="sk-xp-fill" style="width:<?= $stats['level_progress'] ?>%;background:<?= $curColor ?>"></div></div>
        <div class="sk-xp-txt"><?php if($stats['next_level_title']):?><?= $stats['xp_in_level'] ?>/<?= $stats['xp_for_next_level'] ?> XP to <?= $stats['next_level_icon'] ?> <?= $stats['next_level_title'] ?><?php else:?><?= number_format($stats['xp']) ?> XP — Max level!<?php endif;?></div>
    </div>
    <div class="sk-nav">
        <?php if($isLeader):?><a href="/become/manage.php">⚙️ Manage</a><?php endif;?>
        <a href="/become/logout.php">Log Out</a>
    </div>
</div>

<!-- SKILL TREE -->
<div class="path">
<div class="path-glow" id="pathGlow" style="background:<?= $curColor ?>"></div>

<?php
$nodeIdx = 0;
foreach ($lvlGroups as $lvl => $mods):
    $th = null;
    foreach ($thresholds as $t) if ((int)$t['level'] === $lvl) $th = $t;
    $c = $colors[$lvl % count($colors)];
    $isNow = ($lvl === $userLevel);
    $isPast = ($lvl < $userLevel);
    $isFuture = ($lvl > $userLevel);
    
    $coreMods = array_values(array_filter($mods, fn($m) => !$m['_side']));
    $sideMods = array_values(array_filter($mods, fn($m) => $m['_side']));

    // Group core mods by stage
    $stages = [];
    foreach ($coreMods as $m) {
        $sn = (int)($m['module_order'] ?? 1);
        if (!isset($stages[$sn])) $stages[$sn] = [];
        $stages[$sn][] = $m;
    }
    ksort($stages);
?>

<!-- LEVEL GATE -->
<div class="level-group <?= $isPast ? 'level--collapsed' : '' ?> <?= $isNow ? 'level--current' : '' ?>" data-level="<?= $lvl ?>" id="level-<?= $lvl ?>">
<div class="gate <?= $isNow ? 'gate--now' : ($isFuture ? 'gate--future' : '') ?> reveal-scale" style="--gc:<?= $c ?>44" data-toggle="level-collapse">
    <div class="gate__badge" style="background:<?= $c ?>12;border-color:<?= $c ?><?= $isFuture?'44':'' ?>;color:<?= $c ?><?= $isFuture?'77':'' ?>">
        <?= $th ? $th['badge_icon'] : '' ?> Level <?= $lvl ?><?= $th ? ' — '.htmlspecialchars($th['title']) : '' ?>
        <?php if($isFuture && $th):?><span class="gate__sub"><?= $th['xp_required'] ?> XP to unlock</span><?php endif;?>
        <?php if($isPast):?><span class="gate__sub">✅ Complete · tap to expand</span><?php endif;?>
    </div>
</div>
<div class="level-content">

<?php foreach ($stages as $stageNum => $stageMods):
    $isMulti = count($stageMods) > 1;
    if ($isMulti): ?>
    <div class="branch-label reveal">↓ Complete all ↓</div>
    <div class="branch-group reveal">
    <?php endif;
    
    foreach ($stageMods as $m):
        $iD = $m['_done'];
        $iA = !$iD && !$m['_locked'];
        $iC = (int)$m['id'] === $currentId;
        $nc = $iD ? 'var(--green)' : ($iA ? $c : 'rgba(255,255,255,.08)');
        $cls = 'node';
        if ($iD) $cls .= ' node--done';
        elseif ($iC) $cls .= ' node--now';
        elseif ($iA) $cls .= ' node--now';
        else $cls .= ' node--locked';
        $dir = $nodeIdx % 2 === 0 ? 'reveal-left' : 'reveal-right';
        $tag = $m['_locked'] ? 'div' : 'a';
        $hr = $m['_locked'] ? '' : " href=\"/become/module.php?id={$m['id']}\"";
        $f = $m['_f'];
    ?>
    <div class="<?= $cls ?> <?= $dir ?>" <?= $iC?'id="current-node"':'' ?>>
        <div class="node__dot" style="border-color:<?= $nc ?>;--nc:<?= $c ?>44"><?= $iD ? '✅' : ($m['_locked'] ? '🔒' : ($m['icon'] ?: '📄')) ?></div>
        <<?= $tag ?> class="node__card"<?= $hr ?>>
            <div class="node__title"><?= htmlspecialchars($m['title']) ?></div>
            <div class="node__sub"><?= $f ? htmlspecialchars(($f['icon']??'').' '.$f['title']) : '' ?><?= $m['_locked'] ? " · 🔒 Level {$lvl}" : '' ?></div>
            <?php if($iA && !$iD && $m['_st']>0):?>
            <div class="node__bar"><div class="node__bar-fill" style="width:<?= $m['_pct'] ?>%;background:<?= $c ?>"></div></div>
            <?php endif;?>
        </<?= $tag ?>>
    </div>
    <?php $nodeIdx++; endforeach;
    
    if ($isMulti): ?>
    </div>
    <div class="merge-label reveal">↑ All required ↑</div>
    <?php endif;
endforeach; ?>

<?php if($sideMods):?>
<div class="side-branch reveal">
    <div class="side-label">🗺️ Side Quest<?= count($sideMods)>1?'s':'' ?></div>
    <?php foreach($sideMods as $m):
        $iD=$m['_done'];$iA=!$iD&&!$m['_locked'];
        $cls='node node--side'.($iD?' node--done':($iA?' node--now':' node--locked'));
        $tag=$m['_locked']?'div':'a';$hr=$m['_locked']?'':" href=\"/become/module.php?id={$m['id']}\"";
    ?>
    <div class="<?= $cls ?> reveal">
        <div class="node__dot" style="border-color:var(--gold)"><?= $iD?'✅':($m['_locked']?'🔒':'🗺️') ?></div>
        <<?= $tag ?> class="node__card"<?= $hr ?>><div class="node__title"><?= htmlspecialchars($m['title']) ?></div><div class="node__sub"><?= $m['_f']?htmlspecialchars($m['_f']['icon'].' '.$m['_f']['title']):'' ?> · Optional</div></<?= $tag ?>>
    </div>
    <?php endforeach;?>
</div>
<?php endif;?>

</div><!-- .level-content -->
</div><!-- .level-group -->
<?php endforeach;?>

<?php if($openMods):?>
<div class="gate reveal-scale" style="margin-top:2rem">
    <div class="gate__badge" style="background:rgba(255,255,255,.03);border-color:var(--mute);color:var(--dim)">📚 Always Available</div>
</div>
<?php foreach($openMods as $i=>$m):$iD=$m['_done'];$dir=$i%2===0?'reveal-left':'reveal-right';?>
<div class="node <?= $iD?'node--done':'node--now' ?> <?= $dir ?>">
    <div class="node__dot" style="border-color:var(--dim)"><?= $iD?'✅':($m['icon']?:'📄') ?></div>
    <a class="node__card" href="/become/module.php?id=<?= $m['id'] ?>"><div class="node__title"><?= htmlspecialchars($m['title']) ?></div><div class="node__sub"><?= $m['_f']?htmlspecialchars($m['_f']['icon'].' '.$m['_f']['title']):'' ?> · Reference</div></a>
</div>
<?php endforeach;endif;?>

</div><!-- .path -->
</div><!-- .sk -->
</div><!-- .portal -->

<button class="scroll-btn" id="scrollBtn">📍</button>

<script data-cfasync="false" src="/become/portal.js"></script>
<script data-cfasync="false">
// Scroll reveal
var reveals = document.querySelectorAll('.reveal,.reveal-left,.reveal-right,.reveal-scale');
var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(e) {
        if (e.isIntersecting) {
            e.target.classList.add('visible');
            var children = e.target.querySelectorAll('.reveal,.reveal-left,.reveal-right,.reveal-scale');
            children.forEach(function(c, i) {
                setTimeout(function() { c.classList.add('visible'); }, i * 80);
            });
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
reveals.forEach(function(el) { observer.observe(el); });

// Set max-height for expanded levels so transition works
document.querySelectorAll('.level-group:not(.level--collapsed) .level-content').forEach(function(el) {
    el.style.maxHeight = el.scrollHeight + 2000 + 'px';
    el.style.opacity = '1';
});

// Animate path glow line to current node
var currentNode = document.getElementById('current-node');
var pathGlow = document.getElementById('pathGlow');
if (currentNode && pathGlow) {
    var pathTop = pathGlow.parentElement.getBoundingClientRect().top;
    var nodeTop = currentNode.getBoundingClientRect().top;
    pathGlow.style.height = (nodeTop - pathTop + 14) + 'px';
}

// Scroll to current on load
if (currentNode) setTimeout(function() { currentNode.scrollIntoView({behavior:'smooth',block:'center'}); }, 600);

// Event delegation for everything
document.addEventListener('click', function(e) {
    // Level collapse/expand toggle
    var gate = e.target.closest('[data-toggle="level-collapse"]');
    if (gate) {
        var group = gate.closest('.level-group');
        if (group) {
            var content = group.querySelector('.level-content');
            if (group.classList.contains('level--collapsed')) {
                group.classList.remove('level--collapsed');
                content.style.maxHeight = content.scrollHeight + 2000 + 'px';
                content.style.opacity = '1';
                // Reveal hidden nodes
                content.querySelectorAll('.reveal,.reveal-left,.reveal-right,.reveal-scale').forEach(function(el) {
                    el.classList.add('visible');
                });
            } else {
                group.classList.add('level--collapsed');
                content.style.maxHeight = '0';
                content.style.opacity = '0';
            }
        }
        return;
    }

    // Scroll button
    if (e.target.closest('#scrollBtn')) {
        var t = document.getElementById('current-node');
        if (t) {
            // Make sure current level is expanded
            var lvlGroup = t.closest('.level-group');
            if (lvlGroup && lvlGroup.classList.contains('level--collapsed')) {
                lvlGroup.classList.remove('level--collapsed');
                var ct = lvlGroup.querySelector('.level-content');
                ct.style.maxHeight = ct.scrollHeight + 2000 + 'px';
                ct.style.opacity = '1';
            }
            setTimeout(function() { t.scrollIntoView({behavior:'smooth',block:'center'}); }, 100);
        }
        return;
    }

    // Library toggle
    if (e.target.closest('#libBtn')) {
        document.getElementById('libPanel').classList.toggle('open');
        return;
    }
    if (e.target.closest('#libClose')) {
        document.getElementById('libPanel').classList.remove('open');
        return;
    }

    // Library card expand
    var card = e.target.closest('[data-lib-card]');
    if (card && !e.target.closest('.lib-mod')) {
        card.classList.toggle('expanded');
        return;
    }
});
</script>
</body>
</html>
