<?php
// become/griff/analytics.php — Griff Analytics
error_reporting(E_ALL); ini_set('display_errors', 0);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$role = isset($_SESSION['portal_role']) ? $_SESSION['portal_role'] : 'rep';
if ($role !== 'admin' && $role !== 'leader') { header('Location: /become/griff/'); exit; }
$db = Database::getInstance();

$convos = array(); $questions = array(); $repUsage = array(); $topics = array();
try {
    $s = $db->prepare("SELECT ac.*, tu.username, tu.first_name FROM ai_conversations ac JOIN training_users tu ON ac.user_id=tu.id ORDER BY ac.updated_at DESC LIMIT 200");
    $s->execute(); $convos = $s->fetchAll();
} catch (Exception $e) {}

foreach ($convos as $cv) {
    $msgs = json_decode($cv['messages'], true);
    if (!is_array($msgs)) continue;
    $rep = $cv['first_name'] ? $cv['first_name'] : $cv['username'];
    if (!isset($repUsage[$rep])) $repUsage[$rep] = array('count'=>0,'tokens'=>0,'last'=>$cv['updated_at']);
    $repUsage[$rep]['count']++;
    $repUsage[$rep]['tokens'] += intval($cv['token_count']);
    foreach ($msgs as $m) {
        if ($m['role'] === 'user') {
            $questions[] = array('text'=>$m['content'],'rep'=>$rep,'date'=>$cv['updated_at'],'mode'=>$cv['mode']);
            $ws = preg_split('/\s+/', strtolower(preg_replace('/[^a-z0-9\s]/i', '', $m['content'])));
            foreach ($ws as $w) { if (strlen($w)>4 && !in_array($w,array('about','would','should','could','their','there','where','which','these','those','think','really','going'))) { $topics[$w] = (isset($topics[$w])?$topics[$w]:0)+1; } }
        }
    }
}
arsort($topics); $topTopics = array_slice($topics, 0, 15, true);
$kbCount = 0; $docCount = 0;
try { $s = $db->prepare("SELECT COUNT(*) c FROM knowledge_base"); $s->execute(); $kbCount = intval($s->fetch()['c']); } catch (Exception $e) {}
try { $s = $db->prepare("SELECT COUNT(*) c FROM doctrine_rules WHERE is_active=1"); $s->execute(); $docCount = intval($s->fetch()['c']); } catch (Exception $e) {}
?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Griff Analytics</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:-apple-system,system-ui,sans-serif;background:#0b0b12;color:#e8e8ef;padding:1rem;max-width:800px;margin:0 auto}
h1{font-size:1.2rem;color:#a78bfa;margin-bottom:.25rem}h2{font-size:.95rem;color:#a78bfa;margin:1.25rem 0 .5rem}.sub{color:#6b7280;font-size:.82rem;margin-bottom:1rem}
a{color:#22A8B3;text-decoration:none;font-size:.82rem}.nav{display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap}.nav a{padding:.25rem .5rem;border:1px solid rgba(255,255,255,.08);border-radius:6px;color:#6b7280}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr));gap:.5rem;margin-bottom:1.5rem}.sc{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:.75rem;text-align:center}.sn{font-size:1.5rem;font-weight:700;color:#22A8B3}.sl{font-size:.68rem;color:#6b7280;margin-top:.1rem}
.bar{display:flex;align-items:center;gap:.4rem;margin-bottom:.25rem}.bw{font-size:.78rem;width:80px;text-align:right;font-weight:600}.bf{height:16px;border-radius:3px;background:#a78bfa}.bc{font-size:.68rem;color:#6b7280}
table{width:100%;border-collapse:collapse;font-size:.82rem}th{text-align:left;padding:.4rem;border-bottom:1px solid rgba(255,255,255,.08);color:#6b7280;font-size:.7rem;text-transform:uppercase}td{padding:.35rem .4rem;border-bottom:1px solid rgba(255,255,255,.03)}
.qc{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:6px;padding:.5rem .7rem;margin-bottom:.3rem}.qt{font-size:.82rem}.qm{font-size:.68rem;color:#6b7280;margin-top:.15rem}
</style></head><body>
<h1>🦅 Griff Analytics</h1>
<p class="sub">What reps are asking, common issues, knowledge coverage.</p>
<div class="nav"><a href="/become/griff/">← Griff</a><a href="/become/griff/doctrine.php">📜 Doctrine</a><a href="/become/manage.php">⚙️ Manage</a></div>

<div class="stats">
<div class="sc"><div class="sn"><?=count($convos)?></div><div class="sl">Chats</div></div>
<div class="sc"><div class="sn"><?=count($questions)?></div><div class="sl">Questions</div></div>
<div class="sc"><div class="sn"><?=count($repUsage)?></div><div class="sl">Reps</div></div>
<div class="sc"><div class="sn"><?=$kbCount?></div><div class="sl">KB Chunks</div></div>
<div class="sc"><div class="sn"><?=$docCount?></div><div class="sl">Rules</div></div>
</div>

<?php if($topTopics):?><h2>🔥 Trending Topics</h2>
<?php $mx=max($topTopics);foreach($topTopics as $w=>$c):?>
<div class="bar"><span class="bw"><?=htmlspecialchars($w)?></span><div class="bf" style="width:<?=round(($c/$mx)*180)?>px"></div><span class="bc"><?=$c?></span></div>
<?php endforeach;endif;?>

<?php if($repUsage):?><h2>👥 Rep Usage</h2>
<table><tr><th>Rep</th><th>Chats</th><th>Tokens</th><th>Last Active</th></tr>
<?php foreach($repUsage as $n=>$i):?><tr><td style="font-weight:600"><?=htmlspecialchars($n)?></td><td><?=$i['count']?></td><td><?=number_format($i['tokens'])?></td><td style="color:#6b7280;font-size:.75rem"><?=$i['last']?></td></tr><?php endforeach;?>
</table><?php endif;?>

<?php if($questions):?><h2>💬 Recent Questions</h2>
<?php foreach(array_slice($questions,0,25) as $q):?>
<div class="qc"><div class="qt"><?=htmlspecialchars(substr($q['text'],0,200))?></div><div class="qm"><?=htmlspecialchars($q['rep'])?> · <?=$q['mode']==='roleplay'?'🎭':'🎓'?> · <?=$q['date']?></div></div>
<?php endforeach;endif;?>

<?php if(empty($convos)):?><p style="color:#6b7280;text-align:center;padding:2rem">No conversations yet. Reps need to start chatting with Griff!</p><?php endif;?>
</body></html>
