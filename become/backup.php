<?php
/**
 * become/backup.php — Full Database & Content Backup
 * Location: public_html/become/backup.php
 * 
 * Creates a complete JSON export of all training data.
 * Can be run manually or via cron for automated backups.
 * 
 * Usage:
 *   Browser: /become/backup.php (must be admin)
 *   Cron:    php /path/to/public_html/become/backup.php --cron
 *   Download: /become/backup.php?download=1
 */

// Allow CLI execution for cron
$isCron = php_sapi_name() === 'cli' || in_array('--cron', $argv ?? []);

if (!$isCron) {
    session_start();
    if (empty($_SESSION['portal_user_id']) || ($_SESSION['portal_role'] ?? '') !== 'admin') {
        http_response_code(403);
        die('Admin access required');
    }
}

require_once __DIR__ . '/includes/db.php';
$db = Database::getInstance();

// ─── Export all tables ───
$tables = [
    'folders'            => 'SELECT * FROM folders ORDER BY folder_order',
    'modules'            => 'SELECT * FROM modules ORDER BY module_order',
    'segments'           => 'SELECT * FROM segments ORDER BY segment_order',
    'segment_media'      => 'SELECT * FROM segment_media ORDER BY id',
    'level_thresholds'   => 'SELECT * FROM level_thresholds ORDER BY level',
    'training_users'     => 'SELECT id, username, first_name, last_name, email, role, created_at FROM training_users',
    'user_progress'      => 'SELECT * FROM user_progress',
    'completed_segments' => 'SELECT * FROM completed_segments',
    'completed_modules'  => 'SELECT * FROM completed_modules',
    'completed_folders'  => 'SELECT * FROM completed_folders',
    'manual_unlocks'     => 'SELECT * FROM manual_unlocks',
    'activity_log'       => 'SELECT * FROM activity_log ORDER BY id DESC LIMIT 1000',
];

// Gracefully handle missing tables
$backup = [
    'meta' => [
        'version'    => '1.0',
        'created_at' => date('Y-m-d H:i:s'),
        'site'       => 'yourenergybest.com',
        'portal'     => 'Become Training Portal',
    ],
    'tables' => [],
    'stats'  => [],
];

$totalRows = 0;
foreach ($tables as $name => $query) {
    try {
        $s = $db->prepare($query);
        $s->execute();
        $rows = $s->fetchAll();
        $backup['tables'][$name] = $rows;
        $backup['stats'][$name] = count($rows);
        $totalRows += count($rows);
    } catch (Exception $e) {
        $backup['tables'][$name] = [];
        $backup['stats'][$name] = 'ERROR: ' . $e->getMessage();
    }
}

// Also try passoff_requests
try {
    $s = $db->prepare("SELECT * FROM passoff_requests ORDER BY id");
    $s->execute();
    $backup['tables']['passoff_requests'] = $s->fetchAll();
    $backup['stats']['passoff_requests'] = count($backup['tables']['passoff_requests']);
} catch (Exception $e) {
    // Table may not exist yet
}

$backup['stats']['total_rows'] = $totalRows;

$json = json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// ─── Save to backup directory ───
$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

// Protect backup directory
$htaccess = $backupDir . '/.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Deny from all\n");
}

$filename = 'become-backup-' . date('Y-m-d-His') . '.json';
$filepath = $backupDir . '/' . $filename;
file_put_contents($filepath, $json);

// Keep only last 30 backups
$files = glob($backupDir . '/become-backup-*.json');
if (count($files) > 30) {
    usort($files, function($a, $b) { return filemtime($a) - filemtime($b); });
    $toDelete = array_slice($files, 0, count($files) - 30);
    foreach ($toDelete as $f) unlink($f);
}

// ─── Output ───
if ($isCron) {
    echo "Backup saved: {$filename} ({$totalRows} rows, " . strlen($json) . " bytes)\n";
    exit;
}

// Download mode
if (isset($_GET['download'])) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($json));
    echo $json;
    exit;
}

// Browser mode — show status page
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Backup — Become</title>
<style>
body{background:#111;color:#fff;font-family:sans-serif;padding:2rem;max-width:600px;margin:0 auto}
h2{color:#22A8B3;margin-bottom:1rem}
.stat{display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid rgba(255,255,255,.05);font-size:.9rem}
.stat-name{color:#9CA3AF}
.stat-val{color:#06D6A0;font-weight:600}
.btn{display:inline-block;padding:.75rem 1.5rem;border-radius:10px;font-weight:700;text-decoration:none;margin-top:1rem;font-size:.95rem}
.btn-teal{background:#22A8B3;color:#fff}
.btn-gold{background:#FFB703;color:#000;margin-left:.5rem}
.success{background:rgba(6,214,160,.1);border:1px solid rgba(6,214,160,.2);border-radius:10px;padding:1rem;margin-bottom:1rem;color:#06D6A0}
.meta{font-size:.8rem;color:#6B7280;margin-top:1.5rem}
</style>
</head><body>
<h2>✅ Backup Complete</h2>
<div class="success">
    <strong><?= $filename ?></strong><br>
    <?= number_format($totalRows) ?> rows · <?= number_format(strlen($json)) ?> bytes
</div>

<h3 style="margin:1.5rem 0 .5rem;font-size:1rem">Tables backed up:</h3>
<?php foreach ($backup['stats'] as $name => $count): ?>
<?php if ($name === 'total_rows') continue; ?>
<div class="stat">
    <span class="stat-name"><?= $name ?></span>
    <span class="stat-val"><?= is_int($count) ? number_format($count) . ' rows' : $count ?></span>
</div>
<?php endforeach; ?>

<div style="margin-top:1.5rem">
    <a class="btn btn-teal" href="/become/backup.php?download=1">⬇ Download Backup</a>
    <a class="btn btn-gold" href="/become/manage.php">← Back to Manage</a>
</div>

<div class="meta">
    <p>Backups are saved to <code>/become/backups/</code> (last 30 kept).</p>
    <p>To automate, add this cron job:<br>
    <code>0 3 * * * php <?= realpath(__DIR__) ?>/backup.php --cron</code></p>
    <p>This runs a backup every day at 3 AM.</p>
</div>

<?php
// List existing backups
$existing = glob($backupDir . '/become-backup-*.json');
rsort($existing);
if ($existing):
?>
<h3 style="margin:1.5rem 0 .5rem;font-size:1rem">Recent backups:</h3>
<?php foreach (array_slice($existing, 0, 10) as $f): ?>
<div class="stat">
    <span class="stat-name"><?= basename($f) ?></span>
    <span class="stat-val"><?= number_format(filesize($f)) ?> bytes</span>
</div>
<?php endforeach; ?>
<?php endif; ?>

</body></html>
