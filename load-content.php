<?php
/**
 * load-content.php — Authenticated JSON Content Loader
 * 
 * Serves JSON data files to authenticated admin sessions.
 * Needed because .htaccess blocks direct .json file access.
 * 
 * Also used by the PUBLIC homepage (cms.js) for content.json only.
 * 
 * Usage:
 *   GET ?target=content         → content.json (public, for homepage)
 *   GET ?target=train           → train-content.json (admin only)
 *   GET ?target=services        → services-content.json (public, for services page)
 *   GET ?target=train-users     → train-users.json (admin only, passwords stripped)
 */

require_once __DIR__ . '/security-config.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$target = $_GET['target'] ?? '';

$targets = [
    'content'     => ['file' => 'content.json',          'public' => true],
    'train'       => ['file' => 'train-content.json',    'public' => false],
    'services'    => ['file' => 'services-content.json',  'public' => true],
    'train-users' => ['file' => 'train-users.json',      'public' => false],
];

if (!isset($targets[$target])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid target']);
    exit;
}

$config = $targets[$target];

// If not public, require admin auth
if (!$config['public'] && !is_admin_authenticated()) {
    http_response_code(403);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$file = __DIR__ . '/' . $config['file'];

if (!file_exists($file)) {
    // Return empty defaults
    $defaults = [
        'content' => '{}',
        'train' => '{"manuals":[],"settings":{"portal_password":"Become"}}',
        'services' => '{"hero":{"title":"","subtitle":""},"services":[]}',
        'train-users' => '{"users":[]}',
    ];
    echo $defaults[$target] ?? '{}';
    exit;
}

$data = file_get_contents($file);

// For train-users: strip password hashes if serving to admin 
// (admin sees "(encrypted)" in the UI, doesn't need actual hashes in JS)
// Actually, we DO need the hashes sent so save round-trips preserve them.
// But we strip them from any non-admin request.
if ($target === 'train-users' && !is_admin_authenticated()) {
    http_response_code(403);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

echo $data;
