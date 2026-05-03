<?php
/**
 * become/module.php — Module View
 * Location: public_html/become/module.php
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ProgressionEngine.php';

$engine   = new ProgressionEngine();
$userId   = (int)$current_user['id'];
$modId    = (int)($_GET['id'] ?? 0);
$isLeader = is_leader();

if (!$modId) { header('Location: /become/'); exit; }

$db = Database::getInstance();

// Module + folder info
$s = $db->prepare("SELECT m.*, f.title AS ftitle, f.icon AS ficon FROM modules m JOIN folders f ON m.folder_id=f.id WHERE m.id=?");
$s->execute([$modId]);
$mod = $s->fetch();
if (!$mod) { header('Location: /become/'); exit; }

// Segments
$s = $db->prepare("SELECT * FROM segments WHERE module_id=? AND is_active=1 ORDER BY segment_order");
$s->execute([$modId]);
$segs = $s->fetchAll();

// Media grouped by segment
$media = [];
$sids = array_column($segs, 'id');
if ($sids) {
    $ph = implode(',', array_fill(0, count($sids), '?'));
    $s = $db->prepare("SELECT * FROM segment_media WHERE segment_id IN ({$ph}) ORDER BY media_order");
    $s->execute($sids);
    foreach ($s->fetchAll() as $r) $media[$r['segment_id']][] = $r;
}

// Completion status
$compSegs = $engine->getCompletedSegmentIds($userId);
$total = count($segs);
$done = 0;
foreach ($segs as &$seg) {
    $seg['done'] = in_array((int)$seg['id'], $compSegs);
    $seg['media'] = $media[$seg['id']] ?? [];
    if ($seg['done']) $done++;
}
unset($seg);
$pct = $total ? round(($done/$total)*100) : 0;
$stats = $engine->getUserStats($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($mod['title']) ?> — Become</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/become/portal.css">
    <?php if ($isLeader): ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body>
<div class="portal">

    <!-- BREADCRUMB -->
    <nav class="crumb">
        <a href="/become/">← Dashboard</a>
        <span>/</span>
        <span><?= htmlspecialchars($mod['ficon'].' '.$mod['ftitle']) ?></span>
        <span>/</span>
        <span class="crumb-cur"><?= htmlspecialchars($mod['title']) ?></span>
    </nav>

    <!-- MODULE HEADER -->
    <div class="card mod-hdr-card">
        <div class="mod-hdr-top">
            <h1 class="mod-page-title" data-mod="<?= $modId ?>"><?= htmlspecialchars($mod['title']) ?></h1>
            <?php if ($isLeader): ?>
                <button class="edit-btn" onclick="toggleTitleEdit(this)" title="Edit title">✏️</button>
            <?php endif; ?>
        </div>
        <?php if ($mod['description']): ?>
            <p class="mod-desc"><?= $mod['description'] ?></p>
        <?php endif; ?>
        <div class="prog-wrap">
            <div class="prog-info"><span><?= $done ?>/<?= $total ?> segments</span><span><?= $pct ?>%</span></div>
            <div class="bar"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
        </div>
    </div>

    <!-- SEGMENTS -->
    <div class="seg-list">
        <?php foreach ($segs as $i => $seg):
            $sid = (int)$seg['id'];
            $isDone = $seg['done'];
            $prevDone = ($i === 0) ? true : $segs[$i-1]['done'];
            $isLocked = !$isDone && $i > 0 && !$prevDone;

            $cls = 'seg';
            if ($isDone)   $cls .= ' seg--done';
            elseif ($isLocked) $cls .= ' seg--locked';
            else $cls .= ' seg--active';

            $ico = $isDone ? '✅' : ($isLocked ? '🔒' : (($seg['segment_type'] ?? 'lesson') === 'quiz' ? '📝' : (($seg['segment_type'] ?? '') === 'passoff' ? '🎯' : '📄')));
        ?>
        <div class="<?= $cls ?>" id="seg-<?= $sid ?>" data-seg="<?= $sid ?>">

            <div class="seg-hdr">
                <span class="seg-ico"><?= $ico ?></span>
                <h3 class="seg-title"><?= htmlspecialchars($seg['title']) ?></h3>
                <?php if (!$isLocked && !$isDone): ?>
                    <span class="seg-xp">+<?= $seg['xp_reward'] ?> XP</span>
                <?php endif; ?>
            </div>

            <?php if (!$isLocked): ?>
            <div class="seg-body" id="body-<?= $sid ?>">

                <?php if ($seg['content_html']): ?>
                    <div class="seg-rich"><?= $seg['content_html'] ?></div>
                <?php endif; ?>

                <?php if (($seg['segment_type'] ?? 'lesson') === 'passoff' && !$seg['content_html']): ?>
                    <div class="seg-rich" style="color:var(--dim);font-style:italic;padding:1rem">
                        🎯 This is a leader pass-off segment. Review the material above, then request a pass-off when you're ready.
                    </div>
                <?php endif; ?>

                <?php if (($seg['segment_type'] ?? 'lesson') === 'quiz' && $seg['customer_quote']): ?>
                    <?php
                    // Render quiz
                    $quizData = json_decode($seg['customer_quote'], true);
                    if (is_array($quizData) && count($quizData) > 0):
                    ?>
                    <div class="seg-quiz" id="quiz-<?= $sid ?>" data-quiz='<?= htmlspecialchars(json_encode($quizData), ENT_QUOTES) ?>'>
                        <?php foreach ($quizData as $qi => $q): ?>
                        <div class="quiz-q" data-qi="<?= $qi ?>">
                            <p class="quiz-q-text"><strong><?= $qi+1 ?>.</strong> <?= htmlspecialchars($q['question'] ?? '') ?></p>
                            <div class="quiz-options">
                                <?php foreach ($q['options'] ?? [] as $oi => $opt): ?>
                                <label class="quiz-opt">
                                    <input type="radio" name="quiz-<?= $sid ?>-<?= $qi ?>" value="<?= $oi ?>" data-correct="<?= ($q['correct'] ?? 0) == $oi ? '1' : '0' ?>">
                                    <span><?= htmlspecialchars($opt) ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="quiz-feedback" style="display:none"></div>
                        </div>
                        <?php endforeach; ?>
                        <button class="btn-complete" data-action="check-quiz" data-seg-id="<?= $sid ?>" style="background:linear-gradient(135deg,var(--gold),var(--orange));margin-top:1rem">📝 Check Answers</button>
                    </div>
                    <?php endif; ?>

                <?php elseif ($seg['customer_quote'] && ($seg['segment_type'] ?? 'lesson') !== 'quiz'): ?>
                <div class="seg-convo">
                    <div class="bubble bubble--cust">
                        <span class="bubble-who">Customer:</span>
                        <p><?= $seg['customer_quote'] ?></p>
                    </div>
                    <?php if ($seg['rep_response']): ?>
                    <div class="bubble bubble--rep">
                        <span class="bubble-who">You:</span>
                        <p><?= $seg['rep_response'] ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($seg['tip']): ?>
                    <div class="bubble bubble--tip">
                        <span class="bubble-who">💡 Tip:</span>
                        <p><?= $seg['tip'] ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php foreach ($seg['media'] as $m): ?>
                <div class="seg-media">
                    <?php if ($m['media_type'] === 'image'): ?>
                        <img src="<?= htmlspecialchars($m['url']) ?>" alt="" class="seg-img" loading="lazy">
                    <?php elseif (in_array($m['media_type'], ['youtube','vimeo','video'])): ?>
                        <div class="seg-vid"><iframe src="<?= htmlspecialchars($m['url']) ?>" frameborder="0" allowfullscreen></iframe></div>
                    <?php elseif ($m['media_type'] === 'pdf'): ?>
                        <a href="<?= htmlspecialchars($m['url']) ?>" target="_blank" class="seg-pdf">📄 <?= htmlspecialchars($m['title'] ?? 'View PDF') ?></a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <?php if ($isLeader): ?>
                    <button class="edit-btn edit-btn--seg" onclick="startEdit(<?= $sid ?>)">✏️ Edit</button>
                <?php endif; ?>
            </div>

            <?php if (!$isDone): ?>
                <div class="seg-actions">
                    <?php if (($seg['segment_type'] ?? 'lesson') === 'passoff'): ?>
                        <?php
                        // Check passoff status (graceful if table doesn't exist)
                        $passoffStatus = null;
                        try {
                            $ps = $db->prepare("SELECT status FROM passoff_requests WHERE user_id=? AND segment_id=? ORDER BY id DESC LIMIT 1");
                            $ps->execute([$userId, $sid]);
                            $passoffStatus = $ps->fetch();
                        } catch (Exception $e) { /* table may not exist yet */ }
                        ?>
                        <?php if ($passoffStatus && $passoffStatus['status'] === 'pending'): ?>
                            <button class="btn-complete" disabled style="background:linear-gradient(135deg,var(--gold),var(--orange));opacity:.8">⏳ Waiting for Leader Approval</button>
                        <?php elseif ($passoffStatus && $passoffStatus['status'] === 'passed'): ?>
                            <button class="btn-complete" data-action="complete" data-seg-id="<?= $sid ?>">✓ Pass-off Approved — Mark Complete (+<?= $seg['xp_reward'] ?> XP)</button>
                        <?php else: ?>
                            <button class="btn-complete" data-action="passoff" data-seg-id="<?= $sid ?>" style="background:linear-gradient(135deg,var(--gold),var(--orange))">🎯 Ready? Request Leader Pass-off</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="btn-complete" data-action="complete" data-seg-id="<?= $sid ?>">✓ Mark Complete (+<?= $seg['xp_reward'] ?> XP)</button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="seg-done-badge">✅ Completed</div>
            <?php endif; ?>

            <?php else: ?>
                <p class="seg-locked-msg">Complete the previous segment to unlock</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- CONTINUE BUTTON -->
    <?php
    // Find the next uncompleted, unlocked segment or module
    $nextSeg = null;
    $allDone = ($done >= $total && $total > 0);
    foreach ($segs as $i => $seg) {
        if (!$seg['done']) {
            $prevDone = ($i === 0) ? true : $segs[$i-1]['done'];
            if ($prevDone) { $nextSeg = $seg; break; }
        }
    }
    $nextAction = $engine->resolveNextAction($userId);
    ?>
    <?php if ($allDone && $nextAction && $nextAction['type'] === 'segment'): ?>
        <a href="/become/module.php?id=<?= $nextAction['module_id'] ?>#seg-<?= $nextAction['segment_id'] ?>" class="btn-continue">
            Continue → <?= htmlspecialchars($nextAction['module_title'] ?? 'Next Module') ?>
        </a>
    <?php elseif ($allDone): ?>
        <a href="/become/" class="btn-continue">🎉 Module Complete — Back to Dashboard</a>
    <?php elseif ($nextSeg): ?>
        <a href="#seg-<?= $nextSeg['id'] ?>" class="btn-continue" onclick="document.getElementById('seg-<?= $nextSeg['id'] ?>').scrollIntoView({behavior:'smooth',block:'center'});return false;">
            Continue → <?= htmlspecialchars($nextSeg['title']) ?>
        </a>
    <?php endif; ?>

    <?php if ($mod['next_step_text']): ?>
        <div class="card next-step">👉 <?= htmlspecialchars($mod['next_step_text']) ?></div>
    <?php endif; ?>

</div>
<script>
const IS_LEADER = <?= $isLeader ? 'true' : 'false' ?>;
const MOD_ID = <?= $modId ?>;
</script>
<?php if ($isLeader): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<?php endif; ?>
<script data-cfasync="false" src="/become/portal.js"></script>
</body>
</html>
