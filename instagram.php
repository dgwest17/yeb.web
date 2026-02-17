<?php
// ═══════════════════════════════════════════════════════════
// instagram.php — Instagram feed proxy
// Uses: Instagram API with Instagram Login (new method, 2025+)
// Endpoint: graph.instagram.com/me/media
// Auto-refreshes long-lived token every 50 days
// ═══════════════════════════════════════════════════════════

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ─── CONFIG ───
define('IG_TOKEN_FILE',       __DIR__ . '/.instagram_token');
define('IG_CACHE_FILE',       __DIR__ . '/ig_cache.json');
define('IG_CACHE_TTL',        3600);     // 1 hour cache
define('IG_TOKEN_REFRESH_DAYS', 50);     // Refresh before 60-day expiry
define('IG_POST_COUNT',       12);

// ═══════════════════════════════════════════════════════════
// TOKEN MANAGEMENT
// ═══════════════════════════════════════════════════════════
function getTokenData() {
    if (!file_exists(IG_TOKEN_FILE)) return null;
    $raw = trim(file_get_contents(IG_TOKEN_FILE));
    if (empty($raw)) return null;
    
    // Support JSON format (with saved_at) or plain token string
    $data = json_decode($raw, true);
    if ($data && isset($data['access_token'])) return $data;
    
    // Plain token string
    return ['access_token' => $raw, 'saved_at' => filemtime(IG_TOKEN_FILE)];
}

function saveTokenData($token, $expiresIn = null) {
    file_put_contents(IG_TOKEN_FILE, json_encode([
        'access_token' => $token,
        'saved_at' => time(),
        'expires_in' => $expiresIn,
    ]));
}

function refreshTokenIfNeeded($tokenData) {
    $savedAt = $tokenData['saved_at'] ?? 0;
    $daysSinceSave = (time() - $savedAt) / 86400;
    
    if ($daysSinceSave < IG_TOKEN_REFRESH_DAYS) {
        return $tokenData['access_token'];
    }
    
    // Refresh long-lived token (works with new Instagram Login method)
    $url = 'https://graph.instagram.com/refresh_access_token'
         . '?grant_type=ig_refresh_token'
         . '&access_token=' . urlencode($tokenData['access_token']);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if (isset($result['access_token'])) {
            saveTokenData($result['access_token'], $result['expires_in'] ?? null);
            return $result['access_token'];
        }
    }
    
    return $tokenData['access_token']; // Use existing if refresh fails
}

// ═══════════════════════════════════════════════════════════
// CACHE
// ═══════════════════════════════════════════════════════════
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
        'posts' => $posts,
    ]));
}

// ═══════════════════════════════════════════════════════════
// MAIN
// ═══════════════════════════════════════════════════════════

// Check cache
$cached = getCachedFeed();
if ($cached) {
    echo json_encode(['success' => true, 'posts' => $cached, 'cached' => true]);
    exit;
}

// Get token
$tokenData = getTokenData();
if (!$tokenData || empty($tokenData['access_token'])) {
    $placeholder = [];
    for ($i = 0; $i < IG_POST_COUNT; $i++) {
        $placeholder[] = [
            'id' => 'placeholder_' . $i,
            'media_type' => 'IMAGE',
            'media_url' => '',
            'permalink' => 'https://instagram.com/energy.best.ca',
            'caption' => 'Follow us on Instagram @energy.best.ca',
            'timestamp' => date('c'),
            'placeholder' => true,
        ];
    }
    echo json_encode(['success' => true, 'posts' => $placeholder, 'setup_required' => true]);
    exit;
}

// Auto-refresh if needed
$token = refreshTokenIfNeeded($tokenData);

// Fetch from Instagram Graph API
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
    // Serve stale cache if available
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
