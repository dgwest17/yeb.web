<?php
// instagram.php — Server-side Instagram feed proxy with caching
// Fetches posts from Instagram Graph API, caches to avoid rate limits
// Returns JSON array of latest posts

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ─── CONFIG ───
// You need a long-lived Instagram User Access Token
// Get one at: https://developers.facebook.com/apps/ → Instagram Basic Display API
// Or use the Graph API: https://developers.facebook.com/docs/instagram-platform/instagram-graph-api
define('IG_ACCESS_TOKEN_FILE', __DIR__ . '/.instagram_token');
define('IG_CACHE_FILE', __DIR__ . '/ig_cache.json');
define('IG_CACHE_TTL', 3600); // 1 hour cache
define('IG_POST_COUNT', 8);

function getInstagramToken() {
    if (!file_exists(IG_ACCESS_TOKEN_FILE)) return null;
    return trim(file_get_contents(IG_ACCESS_TOKEN_FILE));
}

function getCachedFeed() {
    if (!file_exists(IG_CACHE_FILE)) return null;
    $cache = json_decode(file_get_contents(IG_CACHE_FILE), true);
    if (!$cache || !isset($cache['timestamp'])) return null;
    if (time() - $cache['timestamp'] > IG_CACHE_TTL) return null;
    return $cache['posts'];
}

function cacheFeed($posts) {
    file_put_contents(IG_CACHE_FILE, json_encode([
        'timestamp' => time(),
        'posts' => $posts
    ]));
}

// Check cache first
$cached = getCachedFeed();
if ($cached) {
    echo json_encode(['success' => true, 'posts' => $cached, 'cached' => true]);
    exit;
}

// Fetch from Instagram API
$token = getInstagramToken();
if (!$token) {
    // Return placeholder data when no token configured
    $placeholder = [];
    for ($i = 0; $i < IG_POST_COUNT; $i++) {
        $placeholder[] = [
            'id' => 'placeholder_' . $i,
            'media_type' => 'IMAGE',
            'media_url' => '',
            'permalink' => 'https://instagram.com/energy.best.ca',
            'caption' => 'Follow us on Instagram @energy.best.ca',
            'timestamp' => date('c'),
            'placeholder' => true
        ];
    }
    echo json_encode(['success' => true, 'posts' => $placeholder, 'setup_required' => true]);
    exit;
}

$url = 'https://graph.instagram.com/me/media'
     . '?fields=id,caption,media_type,media_url,thumbnail_url,permalink,timestamp'
     . '&limit=' . IG_POST_COUNT
     . '&access_token=' . $token;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT => 10,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 || $err) {
    // Try to serve stale cache if API fails
    if (file_exists(IG_CACHE_FILE)) {
        $stale = json_decode(file_get_contents(IG_CACHE_FILE), true);
        if ($stale && isset($stale['posts'])) {
            echo json_encode(['success' => true, 'posts' => $stale['posts'], 'stale' => true]);
            exit;
        }
    }
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'Instagram API unavailable']);
    exit;
}

$data = json_decode($response, true);
$posts = $data['data'] ?? [];

// Clean up posts for frontend
$cleanPosts = array_map(function($post) {
    return [
        'id' => $post['id'],
        'media_type' => $post['media_type'],
        'media_url' => $post['media_url'] ?? ($post['thumbnail_url'] ?? ''),
        'permalink' => $post['permalink'],
        'caption' => mb_strimwidth($post['caption'] ?? '', 0, 120, '...'),
        'timestamp' => $post['timestamp'],
    ];
}, $posts);

cacheFeed($cleanPosts);
echo json_encode(['success' => true, 'posts' => $cleanPosts]);
