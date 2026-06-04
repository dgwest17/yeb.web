<?php
/**
 * become/zoho_sync.php — Cron entry point for Recruits -> portal sync
 * Location: public_html/become/zoho_sync.php
 *
 * Run from Hostinger cron (recommended every 10-15 min):
 *   /usr/bin/php /home/USER/public_html/become/zoho_sync.php
 *
 * Or trigger over the web with the secret key from config.php:
 *   https://yourenergybest.com/become/zoho_sync.php?key=YOUR_ZOHO_SYNC_KEY
 *
 * Never runs for an anonymous web visitor without the key.
 */

require_once __DIR__ . '/includes/ZohoRecruitSync.php';

$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    header('Content-Type: application/json');
    $path = __DIR__ . '/../config.php';
    $cfg  = file_exists($path) ? (require $path) : [];
    $key  = $cfg['zoho_sync_key'] ?? '';
    if ($key === '' || ($_GET['key'] ?? '') !== $key) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Forbidden']);
        exit;
    }
}

try {
    $sync = new ZohoRecruitSync();
    $result = $sync->run();
} catch (Exception $e) {
    $result = ['ok' => false, 'error' => $e->getMessage()];
}

if ($isCli) {
    echo date('c') . "  zoho_sync  " . json_encode($result) . PHP_EOL;
} else {
    echo json_encode($result);
}
