<?php
// api.php - Secure API for Claude to read live content

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Generate a secure API key (you'll use this)
define('API_KEY', 'yeb_5622d6ee37e38f85c2ea52ca73eb43af');

// Rate limiting
$rate_limit_file = __DIR__ . '/.api_rate_limit';
$max_requests_per_minute = 10;

function checkRateLimit() {
    global $rate_limit_file, $max_requests_per_minute;
    
    $now = time();
    $requests = [];
    
    if (file_exists($rate_limit_file)) {
        $requests = json_decode(file_get_contents($rate_limit_file), true) ?: [];
    }
    
    // Remove requests older than 1 minute
    $requests = array_filter($requests, fn($t) => $t > $now - 60);
    
    if (count($requests) >= $max_requests_per_minute) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded']);
        exit;
    }
    
    $requests[] = $now;
    file_put_contents($rate_limit_file, json_encode($requests));
}

// Check API key
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Only GET requests allowed']);
    exit;
}

if (!isset($_GET['key']) || $_GET['key'] !== API_KEY) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

// Rate limiting
checkRateLimit();

// Return current content
$content_file = __DIR__ . '/content.json';

if (!file_exists($content_file)) {
    http_response_code(404);
    echo json_encode(['error' => 'Content file not found']);
    exit;
}

$content = file_get_contents($content_file);
$data = json_decode($content, true);

if ($data === null) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid JSON in content file']);
    exit;
}

// Add metadata
$response = [
    'success' => true,
    'timestamp' => date('c'),
    'content' => $data
];

echo json_encode($response, JSON_PRETTY_PRINT);
