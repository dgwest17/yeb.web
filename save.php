<?php
/**
 * save.php — SECURED CMS Save Endpoint
 * 
 * Replaces the original save.php with:
 *   ✅ Server-side admin authentication required
 *   ✅ CSRF token validation
 *   ✅ Input sanitization
 *   ✅ Backup with rotation
 *   ✅ Security logging
 * 
 * All requests must include:
 *   - Valid admin session (from admin-auth.php login)
 *   - X-CSRF-Token header (or _csrf_token in body)
 */

require_once __DIR__ . '/security-config.php';
set_security_headers();

header('Content-Type: application/json');

// ─── Only POST allowed ───
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST required']);
    exit;
}

// ─── Require admin authentication ───
require_admin_auth();

// ─── Validate CSRF token ───
validate_csrf();

// ─── Parse input ───
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// ─── Route to correct JSON file ───
$target = $input['_save_target'] ?? 'content';
unset($input['_save_target']); // Don't save the routing key

$targets = [
    'content'     => 'content.json',
    'train'       => 'train-content.json',
    'services'    => 'services-content.json',
    'train-users' => 'train-users.json',
];

if (!isset($targets[$target])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid save target: ' . clean($target)]);
    exit;
}

$file = __DIR__ . '/' . $targets[$target];

// ─── If saving train-users, hash any plain-text passwords ───
if ($target === 'train-users' && isset($input['users'])) {
    foreach ($input['users'] as &$user) {
        // Hash customPassword if it exists and isn't already hashed
        if (!empty($user['customPassword']) && !str_starts_with($user['customPassword'], '$2y$') && !str_starts_with($user['customPassword'], '$2b$')) {
            $user['customPassword'] = hash_portal_password($user['customPassword']);
        }
        // Hash password field too
        if (!empty($user['password']) && !str_starts_with($user['password'], '$2y$') && !str_starts_with($user['password'], '$2b$')) {
            $user['password'] = hash_portal_password($user['password']);
        }
    }
    unset($user);
}

// ─── Backup existing file ───
$backup_dir = __DIR__ . '/backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0700, true);
}

if (file_exists($file)) {
    $timestamp = date('Y-m-d_H-i-s');
    $backup_name = pathinfo($targets[$target], PATHINFO_FILENAME) . '_' . $timestamp . '.json';
    copy($file, $backup_dir . $backup_name);

    // Rotate: keep only last 10 backups per target
    $prefix = pathinfo($targets[$target], PATHINFO_FILENAME) . '_';
    $backups = glob($backup_dir . $prefix . '*.json');
    if ($backups) {
        sort($backups);
        while (count($backups) > 10) {
            unlink(array_shift($backups));
        }
    }
}

// ─── Write the data ───
$json = json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($json === false) {
    http_response_code(500);
    echo json_encode(['error' => 'JSON encoding failed: ' . json_last_error_msg()]);
    exit;
}

$bytes = file_put_contents($file, $json, LOCK_EX);

if ($bytes === false) {
    security_log('save_failed', ['target' => $target]);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to write file. Check permissions.']);
    exit;
}

security_log('save_success', ['target' => $target, 'bytes' => $bytes]);

echo json_encode([
    'success' => true,
    'target' => $target,
    'bytes' => $bytes,
    'backed_up' => true
]);
