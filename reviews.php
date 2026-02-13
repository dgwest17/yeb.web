<?php
// reviews.php — Server-side Google Reviews proxy with caching
// Fetches reviews from Google Places API (New), caches to avoid billing
// Returns JSON with rating, review count, and individual reviews

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ─── CONFIG ───
define('GOOGLE_API_KEY_FILE', __DIR__ . '/.google_api_key');
define('GOOGLE_PLACE_ID_FILE', __DIR__ . '/.google_place_id');
define('REVIEWS_CACHE_FILE', __DIR__ . '/reviews_cache.json');
define('REVIEWS_CACHE_TTL', 21600); // 6 hours — Google reviews don't change that fast

function getCachedReviews() {
    if (!file_exists(REVIEWS_CACHE_FILE)) return null;
    $cache = json_decode(file_get_contents(REVIEWS_CACHE_FILE), true);
    if (!$cache || !isset($cache['timestamp'])) return null;
    if (time() - $cache['timestamp'] > REVIEWS_CACHE_TTL) return null;
    return $cache['data'];
}

function cacheReviews($data) {
    file_put_contents(REVIEWS_CACHE_FILE, json_encode([
        'timestamp' => time(),
        'data' => $data
    ]));
}

// Check cache first
$cached = getCachedReviews();
if ($cached) {
    echo json_encode(array_merge($cached, ['cached' => true]));
    exit;
}

// Load credentials
$apiKey = file_exists(GOOGLE_API_KEY_FILE) ? trim(file_get_contents(GOOGLE_API_KEY_FILE)) : null;
$placeId = file_exists(GOOGLE_PLACE_ID_FILE) ? trim(file_get_contents(GOOGLE_PLACE_ID_FILE)) : null;

if (!$apiKey || !$placeId) {
    // Return your stored/fallback data when API not configured
    $fallback = [
        'success' => true,
        'rating' => 5.0,
        'total_reviews' => 194,
        'reviews' => [],
        'setup_required' => true
    ];
    echo json_encode($fallback);
    exit;
}

// Fetch from Google Places API (New)
$url = 'https://places.googleapis.com/v1/places/' . $placeId;
$fields = 'rating,userRatingCount,reviews';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Goog-Api-Key: ' . $apiKey,
        'X-Goog-FieldMask: ' . $fields,
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 || $err) {
    // Serve stale cache if API fails
    if (file_exists(REVIEWS_CACHE_FILE)) {
        $stale = json_decode(file_get_contents(REVIEWS_CACHE_FILE), true);
        if ($stale && isset($stale['data'])) {
            echo json_encode(array_merge($stale['data'], ['stale' => true]));
            exit;
        }
    }
    // Ultimate fallback
    echo json_encode(['success' => true, 'rating' => 5.0, 'total_reviews' => 194, 'reviews' => []]);
    exit;
}

$place = json_decode($response, true);

$reviews = [];
if (isset($place['reviews'])) {
    foreach (array_slice($place['reviews'], 0, 10) as $review) {
        $reviews[] = [
            'author' => $review['authorAttribution']['displayName'] ?? 'Customer',
            'rating' => $review['rating'] ?? 5,
            'text' => mb_strimwidth($review['text']['text'] ?? '', 0, 300, '...'),
            'time' => $review['relativePublishTimeDescription'] ?? '',
            'photo' => $review['authorAttribution']['photoUri'] ?? '',
        ];
    }
}

$result = [
    'success' => true,
    'rating' => $place['rating'] ?? 5.0,
    'total_reviews' => $place['userRatingCount'] ?? 194,
    'reviews' => $reviews,
];

cacheReviews($result);
echo json_encode($result);
