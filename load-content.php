<?php
/**
 * load-content.php — JSON Content Loader
 * 
 * Serves JSON data files. Needed because .htaccess may block direct .json file access.
 * 
 * Usage:
 *   GET ?target=content         → content.json
 *   GET ?target=services        → services-content.json
 *   GET ?target=options         → options-content.json
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$target = $_GET['target'] ?? '';

$targets = [
    'content'     => 'content.json',
    'services'    => 'services-content.json',
    'options'     => 'options-content.json',
];

if (!isset($targets[$target])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid target']);
    exit;
}

$file = __DIR__ . '/' . $targets[$target];

if (!file_exists($file)) {
    // Return empty defaults
    $defaults = [
        'content' => '{}',
        'services' => '{"hero":{"title":"","subtitle":""},"services":[]}',
        'options' => '{}',
    ];
    echo $defaults[$target] ?? '{}';
    exit;
}

echo file_get_contents($file);
