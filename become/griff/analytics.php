<?php
/**
 * become/coach/analytics.php — Griff Admin Analytics
 * Location: public_html/become/griff/analytics.php
 * Shows: common questions, rep usage, trending topics, flagged issues
 */
require_once __DIR__ . '/../includes/auth.php';
$userRole = $_SESSION['portal_role'] ?? 'rep';
if (!in_array($userRole, ['admin', 'leader'])) {
    header('Location: /become/griff/');
    exit;
}
require_once __DIR__ . '/../includes/db.php';
$db = Database::getInstance();

// Get all conversations with user info
$s = $db->prepare("
    SELECT ac.*, tu.username, tu.first_name, tu.last_name
    FROM ai_conversations ac
    JOIN training_users tu ON ac.user_id = tu.id
    ORDER BY ac.updated_at DESC
    LIMIT 200
");
$s->execute();
$conversations = $s->fetchAll();

// Extract all user messages for analysis
$allQuestions = [];
$repUsage = [];
$topicCounts = [];

foreach ($conversations as $conv) {
    $msgs = json_decode($conv['messages'] ?? '[]', true);
    $repName = $conv['first_name'] ?: $conv['username'];
    if (!isset($repUsage[$repName])) $repUsage[$repName] = ['count' => 0, 'tokens' => 0, 'last' => $conv['updated_at']];
    $repUsage[$repName]['count']++;
    $repUsage[$repName]['tokens'] += (int)$conv['token_count'];

    foreach ($msgs as $m) {
        if ($m['role'] === 'user') {
            $allQuestions[] = [
                'text' => $m['content'],
                'rep' => $repName,
                'date' => $conv['updated_at'],
                'mode' => $conv['mode'],
                'conv_id' => $conv['id'],
            ];
            // Simple keyword extraction for trending topics
            $words = array_filter(
                preg_split('/\s+/', strtolower(preg_replace('/[^a-z0-9\s]/i', '', $m['content']))),
                fn($w) => strlen($w) > 4 && !in_array($w, ['about','would','should','could','their','there','where','which','these','those','being','after','before','other','think','really','going','something','anything'])
            );
            foreach ($words as $w) {
                $topicCounts[$w] = ($topicCounts[$w] ?? 0) + 1;
            }
        }
    }
}

arsort($topicCounts);
$topTopics = array_slice($topicCounts, 0, 20, true);
arsort($repUsage);

// Knowledge base stats
$s = $db->prepare("SELECT COUNT(*) c FROM knowledge_base");
$s->execute();
$kbCount = (int)$s->fetch()['c'];

$s = $db->prepare("SELECT COUNT(*) c FROM doctrine_rules WHERE is_active=1");
$s->execute();
$doctrineCount = (int)$s->fetch()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Griff Analytics</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#0a0a0f;--card:rgba(255,255,255,0.04);--bdr:rgba(255,255,255,0.08);--txt:#e8e8ef;--dim:#6b7280;--teal:#22A8B3;--gold:#FFB703;--green:#06D6A0;--purple:#a78bfa;--red:#EF476F}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--txt);padding:1.5rem;max-width:900px;margin:0 auto}
h1{font-size:1.3rem;margin-bottom:.25rem}
h2{font-size:1rem;margin-bottom:.75rem;color:var(--purple)}
.sub{color:var(--dim);font-size:.85rem;margin-bottom:1.5rem}
a{color:var(--teal);text-decoration:none}
a:hover{text-decoration:underline}

.nav{display:flex;gap:.75rem;margin-bottom:1.5rem;flex-wrap:wrap}
.nav a{color:var(--dim);font-size:.82rem;padding:.3rem .6rem;border:1px solid var(--bdr);border-radius:6px}
.nav a:hover{border-color:var(--teal);color:var(--teal);text-decoration:none}

.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.75rem;margin-bottom:2rem}
.stat-card{background:var(--card);border:1px solid var(--bdr);border-radius:10px;padding:1rem;text-align:center}
.stat-num{font-size:1.8rem;font-weight:700;color:var(--teal)}
.stat-label{font-size:.75rem;color:var(--dim);margin-top:.15rem}

.section{margin-bottom:2rem}

table{width:100%;border-collapse:collapse;font-size:.85rem}
th{text-align:left;padding:.5rem .6rem;border-bottom:1px solid var(--bdr);color:var(--dim);font-size:.72rem;text-transform:uppercase;letter-spacing:.04em}
td{padding:.45rem .6rem;border-bottom:1px solid rgba(255,255,255,.03)}
tr:hover td{background:rgba(255,255,255,.02)}

.topic-bar{display:flex;align-items:center;gap:.5rem;margin-bottom:.35rem}
.topic-word{font-size:.82rem;font-weight:600;width:100px;text-align:right}
.topic-fill{height:18px;border-radius:4px;background:var(--purple);transition:width .3s}
.topic-count{font-size:.72rem;color:var(--dim)}

.q-card{background:var(--card);border:1px solid var(--bdr);border-radius:8px;padding:.6rem .85rem;margin-bottom:.4rem}
.q-text{font-size:.85rem;margin-bottom:.2rem}
.q-meta{font-size:.7rem;color:var(--dim)}

.flag-btn{background:var(--red);color:#fff;border:none;padding:.2rem .5rem;border-radius:4px;font-size:.68rem;cursor:pointer;font-family:inherit}
</style>
</head>
<body>

<h1>🦅 Griff Analytics</h1>
<p class="sub">Track what reps are asking, common issues, and Griff's knowledge coverage.</p>

<div class="nav">
    <a href="/become/griff/">← Griff Chat</a>
    <a href="/become/manage.php">⚙️ Manage Portal</a>
    <a href="/become/">📊 Portal</a>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-num"><?= count($conversations) ?></div>
        <div class="stat-label">Total Chats</div>
    </div>
    <div class="stat-card">
        <div class="stat-num"><?= count($allQuestions) ?></div>
        <div class="stat-label">Questions Asked</div>
    </div>
    <div class="stat-card">
        <div class="stat-num"><?= count($repUsage) ?></div>
        <div class="stat-label">Active Reps</div>
    </div>
    <div class="stat-card">
        <div class="stat-num"><?= $kbCount ?></div>
        <div class="stat-label">Knowledge Chunks</div>
    </div>
    <div class="stat-card">
        <div class="stat-num"><?= $doctrineCount ?></div>
        <div class="stat-label">Doctrine Rules</div>
    </div>
</div>

<!-- Trending Topics -->
<div class="section">
    <h2>🔥 Trending Topics</h2>
    <p style="color:var(--dim);font-size:.78rem;margin-bottom:.75rem">Most common keywords in rep questions — shows what they're struggling with or asking about most.</p>
    <?php if ($topTopics): ?>
        <?php $maxCount = max($topTopics); ?>
        <?php foreach ($topTopics as $word => $count): ?>
        <div class="topic-bar">
            <span class="topic-word"><?= htmlspecialchars($word) ?></span>
            <div class="topic-fill" style="width:<?= round(($count/$maxCount)*200) ?>px"></div>
            <span class="topic-count"><?= $count ?>×</span>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color:var(--dim)">No questions asked yet. Reps need to start chatting with Griff!</p>
    <?php endif; ?>
</div>

<!-- Rep Usage -->
<div class="section">
    <h2>👥 Rep Usage</h2>
    <table>
        <tr><th>Rep</th><th>Chats</th><th>Tokens</th><th>Last Active</th></tr>
        <?php foreach ($repUsage as $name => $info): ?>
        <tr>
            <td style="font-weight:600"><?= htmlspecialchars($name) ?></td>
            <td><?= $info['count'] ?></td>
            <td><?= number_format($info['tokens']) ?></td>
            <td style="color:var(--dim)"><?= $info['last'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$repUsage): ?>
        <tr><td colspan="4" style="color:var(--dim);text-align:center;padding:1rem">No usage yet</td></tr>
        <?php endif; ?>
    </table>
</div>

<!-- Recent Questions -->
<div class="section">
    <h2>💬 Recent Questions</h2>
    <p style="color:var(--dim);font-size:.78rem;margin-bottom:.75rem">What reps are actually asking Griffin. Look for patterns and gaps in training.</p>
    <?php foreach (array_slice($allQuestions, 0, 30) as $q): ?>
    <div class="q-card">
        <div class="q-text"><?= htmlspecialchars(substr($q['text'], 0, 200)) ?><?= strlen($q['text']) > 200 ? '...' : '' ?></div>
        <div class="q-meta">
            <?= htmlspecialchars($q['rep']) ?> · <?= $q['mode'] === 'roleplay' ? '🎭' : '🎓' ?> · <?= $q['date'] ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (!$allQuestions): ?>
    <p style="color:var(--dim);text-align:center;padding:1rem">No questions yet.</p>
    <?php endif; ?>
</div>

</body>
</html>
