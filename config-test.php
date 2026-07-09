<?php
/**
 * config-test.php — TEMPORARY diagnostic. Upload to public_html/, visit once, DELETE after.
 * Checks: config.php parses, required keys exist, database connects.
 * Never prints secret values.
 */
header('Content-Type: text/plain');
echo "═══ YEB Config Diagnostic ═══\n\n";

// 1) Does config.php parse?
try {
    $cfg = require __DIR__ . '/config.php';
    echo "[1] config.php loads: YES\n";
    echo "[1b] returns array: " . (is_array($cfg) ? "YES" : "NO — must `return [...]`") . "\n";
} catch (Throwable $e) {
    echo "[1] config.php loads: NO — FATAL\n";
    echo "    → " . get_class($e) . ": " . $e->getMessage() . " (line " . $e->getLine() . ")\n";
    echo "\nFIX THIS FIRST. Every page that uses config.php (become login, zoho, stripe) is down until it parses.\n";
    exit;
}

// 2) Required keys (presence only)
$need = ['db_host','db_name','db_user','db_pass','zoho_client_id','zoho_client_secret'];
foreach ($need as $k) {
    echo "[2] key '$k': " . (isset($cfg[$k]) && $cfg[$k] !== '' ? "set" : "MISSING/EMPTY") . "\n";
}
echo "[2] STRIPE_SECRET_KEY defined: " . (defined('STRIPE_SECRET_KEY') && STRIPE_SECRET_KEY !== '' ? "YES" : "NO — stripe-checkout.php needs define('STRIPE_SECRET_KEY', 'sk_live_...'); in config.php") . "\n";
echo "[2] AUDIT_ACCESS_SECRET defined: " . (defined('AUDIT_ACCESS_SECRET') && AUDIT_ACCESS_SECRET !== '' ? "YES" : "NO") . "\n";

// 3) DB connection (what become login uses)
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $cfg['db_host'] ?? 'localhost', $cfg['db_port'] ?? 3306, $cfg['db_name'] ?? ''),
        $cfg['db_user'] ?? '', $cfg['db_pass'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "[3] database connects: YES\n";
    $n = $pdo->query("SELECT COUNT(*) FROM training_users WHERE is_active = 1")->fetchColumn();
    echo "[3b] active portal users: $n\n";
} catch (Throwable $e) {
    echo "[3] database connects: NO → " . $e->getMessage() . "\n";
}

echo "\nIf all checks pass but login still fails: clear OPcache (Hostinger → Advanced → PHP Configuration → toggle, or upload a file calling opcache_reset()).\n";
echo "\n*** DELETE THIS FILE NOW ***\n";
