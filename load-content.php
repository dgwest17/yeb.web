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
    'knowledge'   => 'knowledge.json',
];

if (!isset($targets[$target])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid target']);
    exit;
}

$file = __DIR__ . '/' . $targets[$target];

// Knowledge starts from the answers that were written into the old page
if ($target === 'knowledge' && !file_exists($file) && file_exists(__DIR__ . '/knowledge-seed.json')) {
    echo file_get_contents(__DIR__ . '/knowledge-seed.json');
    exit;
}

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
