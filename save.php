<?php
// save.php — Save content files with automatic backup + server-side login
// Handles: content.json, train-content.json, services-content.json

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Password loaded from config.php (gitignored — lives ONLY on the server)
$__cfg = file_exists(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];
define('ADMIN_PASSWORD', $__cfg['admin_save_password'] ?? '');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Handle login check
if (isset($data['action']) && $data['action'] === 'login') {
    if (ADMIN_PASSWORD !== '' && isset($data['password']) && $data['password'] === ADMIN_PASSWORD) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid password']);
    }
    exit;
}

// Determine which file to save
$target = $data['_save_target'] ?? 'content';
unset($data['_save_target']);

$fileMap = [
    'content'  => __DIR__ . '/content.json',
    'train'    => __DIR__ . '/train-content.json',
    'services' => __DIR__ . '/services-content.json',
    'train-users' => __DIR__ . '/train-users.json',
    'options'  => __DIR__ . '/options-content.json',
];

if (!isset($fileMap[$target])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid save target: ' . $target]);
    exit;
}

$content_file = $fileMap[$target];
$backup_dir = __DIR__ . '/backups';

if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Create backup before overwriting
if (file_exists($content_file)) {
    $backup_file = $backup_dir . '/' . $target . '-' . date('Y-m-d-His') . '.json';
    copy($content_file, $backup_file);
    
    $backups = glob($backup_dir . '/' . $target . '-*.json');
    if (count($backups) > 10) {
        usort($backups, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        for ($i = 0; $i < count($backups) - 10; $i++) {
            unlink($backups[$i]);
        }
    }
}

$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if (file_put_contents($content_file, $json) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to write file']);
    exit;
}

echo json_encode(['success' => true, 'target' => $target, 'backup_created' => true]);
