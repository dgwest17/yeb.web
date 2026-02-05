<?php
// save.php - Save content.json with automatic backup

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$content_file = __DIR__ . '/content.json';
$backup_dir = __DIR__ . '/backups';

// Create backups directory if it doesn't exist
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Create backup of existing content.json before overwriting
if (file_exists($content_file)) {
    $backup_file = $backup_dir . '/content-' . date('Y-m-d-His') . '.json';
    copy($content_file, $backup_file);
    
    // Keep only last 10 backups (cleanup old ones)
    $backups = glob($backup_dir . '/content-*.json');
    if (count($backups) > 10) {
        usort($backups, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        for ($i = 0; $i < count($backups) - 10; $i++) {
            unlink($backups[$i]);
        }
    }
}

// Write new content
$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if (file_put_contents($content_file, $json) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to write file']);
    exit;
}

echo json_encode(['success' => true, 'backup_created' => true]);
