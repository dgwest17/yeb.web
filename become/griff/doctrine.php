<?php
// become/griff/doctrine.php — Griff Doctrine Builder
error_reporting(E_ALL); ini_set('display_errors', 0);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
if (!isset($_SESSION['portal_role']) || $_SESSION['portal_role'] !== 'admin') { header('Location: /become/griff/'); exit; }
$db = Database::getInstance();
$msg = ''; $msgOk = false;

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = isset($_POST['act']) ? $_POST['act'] : '';
    try {
        if ($act === 'paste') {
            $title = trim(isset($_POST['title']) ? $_POST['title'] : 'Untitled');
            $text = trim(isset($_POST['text']) ? $_POST['text'] : '');
            if ($text) {
                $chunks = str_split($text, 1000); // Simple chunking
                $i = 0;
                foreach ($chunks as $c) {
                    $c = trim($c);
                    if (strlen($c) > 10) {
                        $s = $db->prepare("INSERT INTO knowledge_base (source_type, source_title, chunk_text, chunk_order) VALUES ('doctrine', ?, ?, ?)");
                        $s->execute(array($title, $c, $i++));
                    }
                }
                $msg = "Ingested {$i} chunks from '{$title}'"; $msgOk = true;
            }
        }
        if ($act === 'rule') {
            $rt = trim(isset($_POST['rt']) ? $_POST['rt'] : '');
            $rx = trim(isset($_POST['rx']) ? $_POST['rx'] : '');
            $rc = trim(isset($_POST['rc']) ? $_POST['rc'] : 'general');
            $rp = intval(isset($_POST['rp']) ? $_POST['rp'] : 5);
            if ($rt && $rx) {
                $s = $db->prepare("INSERT INTO doctrine_rules (category, rule_title, rule_text, priority) VALUES (?, ?, ?, ?)");
                $s->execute(array($rc, $rt, $rx, $rp));
                $msg = "Rule added: {$rt}"; $msgOk = true;
            }
        }
        if ($act === 'delrule') {
            $db->prepare("DELETE FROM doctrine_rules WHERE id=?")->execute(array(intval($_POST['did'])));
            $msg = "Rule deleted"; $msgOk = true;
        }
        if ($act === 'delchunk') {
            $db->prepare("DELETE FROM knowledge_base WHERE id=?")->execute(array(intval($_POST['did'])));
            $msg = "Chunk deleted"; $msgOk = true;
        }
    } catch (Exception $e) { $msg = 'Error: ' . $e->getMessage(); }
}

// Load data
$rules = array(); $chunks = array(); $stats = array();
try { $s = $db->prepare("SELECT * FROM doctrine_rules ORDER BY priority DESC"); $s->execute(); $rules = $s->fetchAll(); } catch (Exception $e) {}
try { $s = $db->prepare("SELECT id, source_title, LEFT(chunk_text,120) AS preview, chunk_order FROM knowledge_base WHERE source_type='doctrine' ORDER BY source_title, chunk_order"); $s->execute(); $chunks = $s->fetchAll(); } catch (Exception $e) {}
try { $s = $db->prepare("SELECT source_type, COUNT(*) c FROM knowledge_base GROUP BY source_type"); $s->execute(); foreach ($s->fetchAll() as $r) $stats[$r['source_type']] = $r['c']; } catch (Exception $e) {}

$grouped = array();
foreach ($chunks as $c) { $grouped[$c['source_title']][] = $c; }
?><!DOCTYPE html>
<html><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Griff Doctrine</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,system-ui,sans-serif;background:#0b0b12;color:#e8e8ef;padding:1rem;max-width:700px;margin:0 auto}
h1{font-size:1.2rem;color:#a78bfa;margin-bottom:.25rem}
h2{font-size:.95rem;color:#a78bfa;margin:1.5rem 0 .5rem}
.sub{color:#6b7280;font-size:.82rem;margin-bottom:1rem}
a{color:#22A8B3;text-decoration:none;font-size:.82rem}
.nav{display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap}
.nav a{padding:.25rem .5rem;border:1px solid rgba(255,255,255,.08);border-radius:6px;color:#6b7280}
.alert{padding:.6rem .8rem;border-radius:8px;margin-bottom:.75rem;font-size:.85rem}
.alert-ok{background:rgba(6,214,160,.1);border:1px solid rgba(6,214,160,.2);color:#06D6A0}
.alert-err{background:rgba(239,71,111,.1);border:1px solid rgba(239,71,111,.2);color:#EF476F}
.card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:1rem;margin-bottom:.75rem}
label{display:block;font-size:.72rem;font-weight:600;color:#6b7280;margin-bottom:.2rem;text-transform:uppercase}
input,textarea,select{width:100%;padding:.45rem .6rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:6px;color:#e8e8ef;font-family:inherit;font-size:.88rem;margin-bottom:.5rem}
textarea{resize:vertical;min-height:80px}
.btn{display:inline-block;padding:.45rem 1rem;border:none;border-radius:6px;font-weight:600;cursor:pointer;font-family:inherit;font-size:.85rem}
.btn-t{background:#22A8B3;color:#fff}
.btn-r{background:#EF476F;color:#fff;font-size:.7rem;padding:.25rem .5rem}
.rule{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:8px;padding:.5rem .7rem;margin-bottom:.35rem;display:flex;gap:.5rem;align-items:start}
.rule-info{flex:1}
.rule-t{font-weight:600;font-size:.85rem}
.rule-x{font-size:.75rem;color:#6b7280;margin-top:.1rem}
.rule-m{font-size:.65rem;color:#a78bfa;margin-top:.1rem}
.stat{display:inline-block;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:6px;padding:.3rem .6rem;font-size:.78rem;margin-right:.35rem;margin-bottom:.35rem}
.stat b{color:#22A8B3}
.chunk{font-size:.78rem;color:#6b7280;padding:.3rem .5rem;border-bottom:1px solid rgba(255,255,255,.03);display:flex;gap:.3rem;align-items:start}
.chunk-t{flex:1;overflow:hidden;text-overflow:ellipsis}
.grp{margin-bottom:.75rem}
.grp-h{font-weight:600;font-size:.85rem;margin-bottom:.25rem}
</style></head><body>
<h1>🦅 Griff — Doctrine Builder</h1>
<p class="sub">Feed Griff your sales doctrine, training philosophy, and rules.</p>
<div class="nav"><a href="/become/griff/">← Griff Chat</a><a href="/become/griff/analytics.php">📊 Analytics</a><a href="/become/manage.php">⚙️ Manage</a></div>

<?php if($msg):?><div class="alert <?=$msgOk?'alert-ok':'alert-err'?>"><?=htmlspecialchars($msg)?></div><?php endif;?>

<div style="margin-bottom:1rem">
<span class="stat">📚 Training: <b><?=isset($stats['segment'])?$stats['segment']:0?></b></span>
<span class="stat">📜 Doctrine: <b><?=isset($stats['doctrine'])?$stats['doctrine']:0?></b></span>
<span class="stat">⚖️ Rules: <b><?=count($rules)?></b></span>
</div>

<h2>📝 Paste Doctrine Text</h2>
<div class="card">
<form method="POST"><input type="hidden" name="act" value="paste">
<label>Title</label><input name="title" placeholder="e.g. Griffin Hill - Case Open" required>
<label>Content (paste everything)</label><textarea name="text" rows="6" placeholder="Paste text here..." required></textarea>
<button class="btn btn-t" type="submit">Ingest into Griff</button>
</form></div>

<h2>⚖️ Add Doctrine Rule</h2>
<div class="card">
<form method="POST"><input type="hidden" name="act" value="rule">
<label>Title</label><input name="rt" placeholder="e.g. Never use pressure tactics" required>
<label>Category</label><select name="rc"><option value="methodology">Methodology</option><option value="objections">Objections</option><option value="tone">Tone</option><option value="process">Process</option><option value="prohibited">Never Do This</option></select>
<label>Rule text</label><textarea name="rx" rows="3" placeholder="Be specific about what Griff should do or not do..." required></textarea>
<label>Priority (1-10, higher = more important)</label><input name="rp" type="number" value="5" min="1" max="10" style="max-width:80px">
<button class="btn btn-t" type="submit">Add Rule</button>
</form></div>

<?php if($rules):?>
<h2>⚖️ Active Rules (<?=count($rules)?>)</h2>
<?php foreach($rules as $r):?>
<div class="rule">
<div class="rule-info"><div class="rule-t"><?=htmlspecialchars($r['rule_title'])?></div><div class="rule-x"><?=htmlspecialchars($r['rule_text'])?></div><div class="rule-m">P<?=$r['priority']?> · <?=$r['category']?></div></div>
<form method="POST"><input type="hidden" name="act" value="delrule"><input type="hidden" name="did" value="<?=$r['id']?>"><button class="btn btn-r" onclick="return confirm('Delete?')">✕</button></form>
</div>
<?php endforeach;endif;?>

<?php if($grouped):?>
<h2>📜 Doctrine Chunks (<?=count($chunks)?>)</h2>
<?php foreach($grouped as $title=>$cks):?>
<div class="grp"><div class="grp-h"><?=htmlspecialchars($title)?> <span style="color:#6b7280;font-weight:400;font-size:.72rem">(<?=count($cks)?>)</span></div>
<?php foreach($cks as $c):?>
<div class="chunk"><span class="chunk-t"><?=htmlspecialchars($c['preview'])?>...</span>
<form method="POST" style="flex-shrink:0"><input type="hidden" name="act" value="delchunk"><input type="hidden" name="did" value="<?=$c['id']?>"><button class="btn btn-r" style="font-size:.6rem;padding:.15rem .3rem" onclick="return confirm('Delete?')">✕</button></form>
</div>
<?php endforeach;?></div>
<?php endforeach;endif;?>

</body></html>
