<?php
/**
 * become/griff/doctrine.php — Griff Doctrine Builder
 * Location: public_html/become/griff/doctrine.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/auth.php';
if (($_SESSION['portal_role'] ?? '') !== 'admin') {
    header('Location: /become/griff/');
    exit;
}
require_once __DIR__ . '/../includes/db.php';

try {
    require_once __DIR__ . '/../includes/AICoach.php';
} catch (Exception $e) {
    // AICoach may fail if config is missing — continue without it
}

$db = Database::getInstance();

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'paste') {
        // Manual paste ingestion
        $title = trim($_POST['title'] ?? 'Manual Entry');
        $category = trim($_POST['category'] ?? 'doctrine');
        $text = trim($_POST['text'] ?? '');
        if ($text) {
            $chunks = chunkText($text, 1000);
            foreach ($chunks as $i => $chunk) {
                $db->prepare("INSERT INTO knowledge_base (source_type, source_title, chunk_text, chunk_order) VALUES ('doctrine', ?, ?, ?)")
                    ->execute([$title, $chunk, $i]);
            }
            $message = "Ingested " . count($chunks) . " chunks from '{$title}'";
            $messageType = 'success';
        }
    }

    if ($action === 'upload_pdf') {
        // PDF upload
        if (!empty($_FILES['pdf']['tmp_name'])) {
            $tmpFile = $_FILES['pdf']['tmp_name'];
            $fileName = $_FILES['pdf']['name'];

            // Try to extract text from PDF
            $text = extractPdfText($tmpFile);
            if ($text && strlen(trim($text)) > 50) {
                $chunks = chunkText($text, 1000);
                foreach ($chunks as $i => $chunk) {
                    $db->prepare("INSERT INTO knowledge_base (source_type, source_title, chunk_text, chunk_order) VALUES ('doctrine', ?, ?, ?)")
                        ->execute(["📄 " . $fileName, $chunk, $i]);
                }
                $message = "Extracted and indexed " . count($chunks) . " chunks from '{$fileName}'";
                $messageType = 'success';
            } else {
                $message = "Could not extract text from this PDF. Try the manual paste method instead — open the PDF, Select All, Copy, and paste below.";
                $messageType = 'error';
            }
        }
    }

    if ($action === 'add_rule') {
        $title = trim($_POST['rule_title'] ?? '');
        $text = trim($_POST['rule_text'] ?? '');
        $cat = trim($_POST['rule_category'] ?? 'general');
        $priority = (int)($_POST['rule_priority'] ?? 5);
        if ($title && $text) {
            $db->prepare("INSERT INTO doctrine_rules (category, rule_title, rule_text, priority) VALUES (?, ?, ?, ?)")
                ->execute([$cat, $title, $text, $priority]);
            $message = "Doctrine rule added: {$title}";
            $messageType = 'success';
        }
    }

    if ($action === 'delete_chunk') {
        $id = (int)($_POST['chunk_id'] ?? 0);
        if ($id) {
            $db->prepare("DELETE FROM knowledge_base WHERE id=?")->execute([$id]);
            $message = "Chunk deleted";
            $messageType = 'success';
        }
    }

    if ($action === 'delete_rule') {
        $id = (int)($_POST['rule_id'] ?? 0);
        if ($id) {
            $db->prepare("DELETE FROM doctrine_rules WHERE id=?")->execute([$id]);
            $message = "Rule deleted";
            $messageType = 'success';
        }
    }
}

// Get current knowledge base entries (doctrine type)
$doctrineChunks = [];
$grouped = [];
$rules = [];
$kbStats = [];

try {
    $s = $db->prepare("SELECT id, source_title, LEFT(chunk_text, 150) AS preview, chunk_order, created_at FROM knowledge_base WHERE source_type='doctrine' ORDER BY source_title, chunk_order");
    $s->execute();
    $doctrineChunks = $s->fetchAll();
    foreach ($doctrineChunks as $c) {
        $grouped[$c['source_title']][] = $c;
    }
} catch (Exception $e) {
    $message = 'Knowledge base table not found. Run ai-migration.sql in phpMyAdmin first.';
    $messageType = 'error';
}

try {
    $s = $db->prepare("SELECT * FROM doctrine_rules ORDER BY priority DESC");
    $s->execute();
    $rules = $s->fetchAll();
} catch (Exception $e) {
    if (!$message) { $message = 'Doctrine rules table not found. Run ai-migration.sql first.'; $messageType = 'error'; }
}

try {
    $s = $db->prepare("SELECT source_type, COUNT(*) c FROM knowledge_base GROUP BY source_type");
    $s->execute();
    foreach ($s->fetchAll() as $r) $kbStats[$r['source_type']] = (int)$r['c'];
} catch (Exception $e) {}

function chunkText($text, $maxLen = 1000) {
    $text = trim($text);
    if (strlen($text) <= $maxLen) return [$text];
    $paragraphs = preg_split('/\n{2,}/', $text);
    $chunks = [];
    $current = '';
    foreach ($paragraphs as $p) {
        $p = trim($p);
        if (!$p) continue;
        if (strlen($current) + strlen($p) > $maxLen && $current) {
            $chunks[] = trim($current);
            $current = '';
        }
        $current .= $p . "\n\n";
    }
    if (trim($current)) $chunks[] = trim($current);
    return $chunks;
}

function extractPdfText($filePath) {
    // Method 1: pdftotext (if available on server)
    $output = [];
    exec("pdftotext " . escapeshellarg($filePath) . " - 2>/dev/null", $output);
    $text = implode("\n", $output);
    if (strlen(trim($text)) > 50) return $text;

    // Method 2: Simple binary extraction (basic, works for text-based PDFs)
    $content = file_get_contents($filePath);
    // Extract text between stream objects
    preg_match_all('/stream\s*\n(.*?)\nendstream/s', $content, $matches);
    $text = '';
    foreach ($matches[1] as $stream) {
        // Try to decode if it's simple text
        $decoded = @gzuncompress($stream);
        if ($decoded) {
            preg_match_all('/\((.*?)\)/', $decoded, $textMatches);
            $text .= implode(' ', $textMatches[1]) . "\n";
        }
    }
    return strlen(trim($text)) > 50 ? $text : null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Griffin — Doctrine Builder</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#0a0a0f;--card:rgba(255,255,255,0.04);--bdr:rgba(255,255,255,0.08);--txt:#e8e8ef;--dim:#6b7280;--teal:#22A8B3;--gold:#FFB703;--green:#06D6A0;--purple:#a78bfa;--red:#EF476F}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--txt);padding:1.5rem;max-width:800px;margin:0 auto}
h1{font-size:1.3rem;margin-bottom:.25rem}
h2{font-size:1rem;margin:1.5rem 0 .5rem;color:var(--purple)}
.sub{color:var(--dim);font-size:.85rem;margin-bottom:1.5rem}
a{color:var(--teal);text-decoration:none;font-size:.85rem}

.nav{display:flex;gap:.75rem;margin-bottom:1.5rem;flex-wrap:wrap}
.nav a{color:var(--dim);padding:.3rem .6rem;border:1px solid var(--bdr);border-radius:6px}
.nav a:hover{border-color:var(--teal);color:var(--teal)}

.msg{padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.9rem}
.msg-success{background:rgba(6,214,160,.1);border:1px solid rgba(6,214,160,.2);color:var(--green)}
.msg-error{background:rgba(239,71,111,.1);border:1px solid rgba(239,71,111,.2);color:var(--red)}

.stats{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem}
.stat{background:var(--card);border:1px solid var(--bdr);border-radius:8px;padding:.5rem .85rem;font-size:.82rem}
.stat strong{color:var(--teal)}

.card{background:var(--card);border:1px solid var(--bdr);border-radius:10px;padding:1.25rem;margin-bottom:1rem}
label{display:block;font-size:.78rem;font-weight:600;color:var(--dim);margin-bottom:.3rem;text-transform:uppercase;letter-spacing:.03em}
input,textarea,select{width:100%;padding:.5rem .7rem;background:rgba(255,255,255,.05);border:1px solid var(--bdr);border-radius:8px;color:var(--txt);font-family:inherit;font-size:.9rem;margin-bottom:.75rem}
input:focus,textarea:focus{border-color:var(--teal);outline:none}
textarea{resize:vertical;min-height:120px}
.btn{display:inline-block;padding:.55rem 1.2rem;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-family:inherit;font-size:.88rem;transition:all .15s}
.btn-teal{background:var(--teal);color:#fff}
.btn-gold{background:var(--gold);color:#000}
.btn-red{background:var(--red);color:#fff;font-size:.75rem;padding:.3rem .6rem}
.btn:hover{transform:translateY(-1px)}

.chunk-group{margin-bottom:1rem}
.chunk-title{font-weight:700;font-size:.9rem;margin-bottom:.35rem;display:flex;justify-content:space-between;align-items:center}
.chunk-title span{color:var(--dim);font-size:.72rem;font-weight:400}
.chunk{background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.04);border-radius:6px;padding:.5rem .7rem;margin-bottom:.25rem;font-size:.82rem;color:var(--dim);display:flex;justify-content:space-between;align-items:start;gap:.5rem}
.chunk-text{flex:1}

.rule{background:var(--card);border:1px solid var(--bdr);border-radius:8px;padding:.65rem .85rem;margin-bottom:.4rem;display:flex;justify-content:space-between;align-items:start;gap:.5rem}
.rule-info{flex:1}
.rule-title{font-weight:600;font-size:.88rem}
.rule-text{font-size:.78rem;color:var(--dim);margin-top:.15rem}
.rule-meta{font-size:.68rem;color:var(--purple);margin-top:.15rem}
</style>
</head>
<body>

<h1>🦅 Griffin — Doctrine Builder</h1>
<p class="sub">Feed Griffin your sales doctrine, PDFs, and training philosophy. This becomes his core knowledge.</p>

<div class="nav">
    <a href="/become/griff/">← Griff Chat</a>
    <a href="/become/griff/analytics.php">📊 Analytics</a>
    <a href="/become/manage.php">⚙️ Manage</a>
</div>

<?php if ($message): ?>
<div class="msg msg-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="stats">
    <div class="stat">📚 Training: <strong><?= $kbStats['segment'] ?? 0 ?></strong> chunks</div>
    <div class="stat">📜 Doctrine: <strong><?= $kbStats['doctrine'] ?? 0 ?></strong> chunks</div>
    <div class="stat">📹 Videos: <strong><?= $kbStats['video'] ?? 0 ?></strong> chunks</div>
    <div class="stat">⚖️ Rules: <strong><?= count($rules) ?></strong> active</div>
</div>

<!-- Method 1: Paste Text -->
<h2>📝 Paste Doctrine Text</h2>
<p style="color:var(--dim);font-size:.82rem;margin-bottom:.75rem">Open your Griffin Hill PDF or notes → Select All → Copy → Paste below. This is the most reliable method.</p>
<div class="card">
    <form method="POST">
        <input type="hidden" name="action" value="paste">
        <label>Document Title</label>
        <input name="title" placeholder="e.g. Griffin Hill Sales System — Chapter 3" required>
        <label>Category</label>
        <select name="category">
            <option value="doctrine">Sales Doctrine</option>
            <option value="objections">Objection Handling</option>
            <option value="scripts">Scripts & Dialogues</option>
            <option value="process">Sales Process</option>
            <option value="mindset">Mindset & Philosophy</option>
            <option value="product">Product Knowledge</option>
        </select>
        <label>Content (paste everything)</label>
        <textarea name="text" rows="10" placeholder="Paste the full text content here. It will be automatically split into searchable chunks for Griffin..." required></textarea>
        <button type="submit" class="btn btn-teal">📥 Ingest into Griff</button>
    </form>
</div>

<!-- Method 2: PDF Upload -->
<h2>📄 Upload PDF</h2>
<p style="color:var(--dim);font-size:.82rem;margin-bottom:.75rem">Upload a PDF and Griffin will try to extract the text. Works best with text-based PDFs (not scanned images).</p>
<div class="card">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_pdf">
        <label>PDF File</label>
        <input type="file" name="pdf" accept=".pdf" required style="padding:.5rem">
        <button type="submit" class="btn btn-gold">📤 Upload & Extract</button>
    </form>
</div>

<!-- Method 3: Doctrine Rules -->
<h2>⚖️ Add Doctrine Rule</h2>
<p style="color:var(--dim);font-size:.82rem;margin-bottom:.75rem">Rules are direct instructions to Griffin. They override everything else. Use these for your core sales principles.</p>
<div class="card">
    <form method="POST">
        <input type="hidden" name="action" value="add_rule">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
            <div><label>Rule Title</label><input name="rule_title" placeholder="e.g. Always use assumptive close" required></div>
            <div><label>Category</label>
                <select name="rule_category">
                    <option value="methodology">Methodology</option>
                    <option value="objections">Objections</option>
                    <option value="tone">Tone & Style</option>
                    <option value="process">Process</option>
                    <option value="prohibited">Never Do This</option>
                </select>
            </div>
        </div>
        <label>Rule (be specific — tell Griffin exactly what to do/not do)</label>
        <textarea name="rule_text" rows="3" placeholder="e.g. When a rep asks about closing technique, always reference the Griffin Hill assumptive close: after presenting the solution, transition directly to scheduling with 'Let's get you set up for [date]...' Never ask 'Would you like to move forward?' — that gives them an out." required></textarea>
        <label>Priority (10 = highest)</label>
        <input type="number" name="rule_priority" value="5" min="1" max="10" style="max-width:100px">
        <button type="submit" class="btn btn-teal">➕ Add Rule</button>
    </form>
</div>

<!-- Current Doctrine Rules -->
<?php if ($rules): ?>
<h2>⚖️ Active Rules (<?= count($rules) ?>)</h2>
<?php foreach ($rules as $r): ?>
<div class="rule">
    <div class="rule-info">
        <div class="rule-title"><?= htmlspecialchars($r['rule_title']) ?></div>
        <div class="rule-text"><?= htmlspecialchars($r['rule_text']) ?></div>
        <div class="rule-meta">P<?= $r['priority'] ?> · <?= $r['category'] ?></div>
    </div>
    <form method="POST" style="flex-shrink:0">
        <input type="hidden" name="action" value="delete_rule">
        <input type="hidden" name="rule_id" value="<?= $r['id'] ?>">
        <button type="submit" class="btn btn-red" onclick="return confirm('Delete this rule?')">✕</button>
    </form>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- Knowledge Base: Doctrine Chunks -->
<?php if ($grouped): ?>
<h2>📜 Ingested Doctrine (<?= count($doctrineChunks) ?> chunks)</h2>
<?php foreach ($grouped as $title => $chunks): ?>
<div class="chunk-group">
    <div class="chunk-title"><?= htmlspecialchars($title) ?> <span><?= count($chunks) ?> chunks</span></div>
    <?php foreach ($chunks as $c): ?>
    <div class="chunk">
        <div class="chunk-text"><?= htmlspecialchars($c['preview']) ?>...</div>
        <form method="POST">
            <input type="hidden" name="action" value="delete_chunk">
            <input type="hidden" name="chunk_id" value="<?= $c['id'] ?>">
            <button type="submit" class="btn btn-red" style="font-size:.65rem;padding:.15rem .35rem">✕</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

</body>
</html>
