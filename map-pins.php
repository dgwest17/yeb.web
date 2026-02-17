<?php
// ═══════════════════════════════════════════════════════════
// map-pins.php — Dynamic install map pins from Zoho Contacts
// Pulls contacts where Lifecycle_Stage = Client or Past Client
// Geocodes addresses, caches results, serves to Leaflet map
// ═══════════════════════════════════════════════════════════

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ─── CONFIG ───
define('CLIENT_ID',          '1000.E3XUN0DQA4EZ8XZDGLTVVN33YEPB4V');
define('CLIENT_SECRET',      'e6c650a1fdf0dcaa229d2f3569c24d8e7a927205c3');
define('REFRESH_TOKEN_FILE', __DIR__ . '/.zoho_refresh_token');
define('ZOHO_API_BASE',      'https://www.zohoapis.com/crm/v2');
define('CACHE_FILE',         __DIR__ . '/map-pins-cache.json');
define('CACHE_TTL',          3600 * 6); // 6 hour cache

// ─── Hardcoded base pins (your original 170 installs) ───
// These are always shown even if Zoho is down
define('BASE_PINS_FILE', __DIR__ . '/map-pins-base.json');

// ═══════════════════════════════════════════════════════════
// CHECK CACHE FIRST
// ═══════════════════════════════════════════════════════════
if (file_exists(CACHE_FILE)) {
    $cache = json_decode(file_get_contents(CACHE_FILE), true);
    if ($cache && isset($cache['timestamp']) && (time() - $cache['timestamp']) < CACHE_TTL) {
        echo json_encode(['success' => true, 'pins' => $cache['pins'], 'count' => count($cache['pins']), 'cached' => true]);
        exit;
    }
}

// ═══════════════════════════════════════════════════════════
// LOAD BASE PINS
// ═══════════════════════════════════════════════════════════
$basePins = [];
if (file_exists(BASE_PINS_FILE)) {
    $basePins = json_decode(file_get_contents(BASE_PINS_FILE), true) ?: [];
}

// ═══════════════════════════════════════════════════════════
// ZOHO AUTH
// ═══════════════════════════════════════════════════════════
function getAccessToken() {
    if (!file_exists(REFRESH_TOKEN_FILE)) return null;
    $refreshToken = trim(file_get_contents(REFRESH_TOKEN_FILE));
    if (empty($refreshToken)) return null;
    
    $ch = curl_init('https://accounts.zoho.com/oauth/v2/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'refresh_token' => $refreshToken,
            'client_id'     => CLIENT_ID,
            'client_secret' => CLIENT_SECRET,
            'grant_type'    => 'refresh_token'
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return $result['access_token'] ?? null;
}

// ═══════════════════════════════════════════════════════════
// FETCH ZOHO CONTACTS (Client + Past Client)
// ═══════════════════════════════════════════════════════════
function fetchZohoClients($token) {
    $allRecords = [];
    $page = 1;
    $hasMore = true;
    
    while ($hasMore && $page <= 10) { // Max 10 pages (2000 records)
        // COQL query for Client/Past Client with address
        $query = [
            'select_query' => "SELECT Mailing_Street, Mailing_City, Mailing_State, Mailing_Zip FROM Contacts WHERE Lifecycle_Stage in ('Client', 'Past Client') AND Mailing_City is not null LIMIT " . (($page - 1) * 200) . ", 200"
        ];
        
        $ch = curl_init(ZOHO_API_BASE . '/coql');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($query),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Zoho-oauthtoken ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) break;
        
        $data = json_decode($response, true);
        $records = $data['data'] ?? [];
        $allRecords = array_merge($allRecords, $records);
        
        $hasMore = !empty($data['info']['more_records']);
        $page++;
    }
    
    return $allRecords;
}

// ═══════════════════════════════════════════════════════════
// GEOCODE ADDRESSES → COORDINATES
// ═══════════════════════════════════════════════════════════
// Known city coordinates for fast lookup (no external API needed)
$CITY_COORDS = [
    'encinitas' => [33.037, -117.292], 'escondido' => [33.119, -117.086],
    'vista' => [33.200, -117.243], 'carlsbad' => [33.158, -117.351],
    'san marcos' => [33.143, -117.166], 'poway' => [32.963, -117.036],
    'oceanside' => [33.196, -117.380], 'san diego' => [32.716, -117.161],
    'chula vista' => [32.640, -117.084], 'la mesa' => [32.768, -117.023],
    'el cajon' => [32.795, -116.963], 'santee' => [32.838, -116.974],
    'imperial beach' => [32.584, -117.113], 'national city' => [32.678, -117.099],
    'spring valley' => [32.745, -116.999], 'la jolla' => [32.842, -117.276],
    'del mar' => [32.959, -117.265], 'solana beach' => [32.991, -117.271],
    'cardiff' => [33.020, -117.278], 'rancho santa fe' => [33.020, -117.203],
    'bonita' => [32.658, -117.030], 'coronado' => [32.686, -117.183],
    'ramona' => [33.042, -116.868], 'lakeside' => [32.857, -116.922],
    'alpine' => [32.835, -116.766], 'fallbrook' => [33.376, -117.251],
    'valley center' => [33.216, -117.034], 'bonsall' => [33.289, -117.225],
    'temecula' => [33.494, -117.148], 'murrieta' => [33.554, -117.213],
    'rancho bernardo' => [33.017, -117.075], 'scripps ranch' => [32.900, -117.100],
    'mira mesa' => [32.915, -117.143], 'clairemont' => [32.840, -117.200],
    'kearny mesa' => [32.830, -117.142], 'mission valley' => [32.770, -117.159],
    'pacific beach' => [32.799, -117.236], 'ocean beach' => [32.749, -117.249],
    'point loma' => [32.720, -117.243], 'hillcrest' => [32.748, -117.163],
    'north park' => [32.741, -117.129], 'university city' => [32.868, -117.224],
    'carmel valley' => [32.935, -117.230], 'sabre springs' => [32.960, -117.095],
    'rancho penasquitos' => [32.950, -117.113], 'san ysidro' => [32.556, -117.044],
    'otay ranch' => [32.623, -116.970], 'eastlake' => [32.632, -116.976],
    'lemon grove' => [32.743, -117.032], 'la presa' => [32.717, -117.003],
    'san carlos' => [32.775, -117.073], 'tierrasanta' => [32.827, -117.101],
    'long beach' => [33.770, -118.194], 'torrance' => [33.836, -118.341],
    'fullerton' => [33.870, -117.924], 'cerritos' => [33.858, -118.065],
    'whittier' => [33.979, -118.033], 'santa ana' => [33.746, -117.868],
    'manhattan beach' => [33.885, -118.411], 'la habra' => [33.932, -117.946],
    'beaumont' => [33.929, -116.977], 'palm desert' => [33.722, -116.374],
    'yorba linda' => [33.889, -117.813], 'lakewood' => [33.854, -118.134],
    'san clemente' => [33.427, -117.612],
];

function geocodeCity($city) {
    global $CITY_COORDS;
    $key = strtolower(trim($city));
    if (isset($CITY_COORDS[$key])) {
        $coords = $CITY_COORDS[$key];
        // Add small random offset so pins don't stack
        return [
            $coords[0] + (mt_rand(-600, 600) / 100000),
            $coords[1] + (mt_rand(-600, 600) / 100000)
        ];
    }
    return null;
}

// ═══════════════════════════════════════════════════════════
// MAIN LOGIC
// ═══════════════════════════════════════════════════════════
$token = getAccessToken();

$zohoPins = [];
if ($token) {
    $clients = fetchZohoClients($token);
    
    // Track addresses for dedup
    $seenAddresses = [];
    
    // Mark base pins as seen (by rough coordinate match)
    foreach ($basePins as $bp) {
        $key = round($bp[0], 3) . ',' . round($bp[1], 3);
        $seenAddresses[$key] = true;
    }
    
    foreach ($clients as $contact) {
        $street = trim($contact['Mailing_Street'] ?? '');
        $city   = trim($contact['Mailing_City'] ?? '');
        $zip    = trim($contact['Mailing_Zip'] ?? '');
        
        if (empty($city)) continue;
        
        // Dedup key: street + zip (or just street + city if no zip)
        $dedupKey = strtolower($street . '|' . ($zip ?: $city));
        if (isset($seenAddresses[$dedupKey])) continue;
        $seenAddresses[$dedupKey] = true;
        
        $coords = geocodeCity($city);
        if ($coords) {
            $zohoPins[] = $coords;
        }
    }
}

// Merge base + zoho pins
$allPins = array_merge($basePins, $zohoPins);

// Cache result
$cacheData = [
    'timestamp' => time(),
    'pins' => $allPins,
    'zoho_new' => count($zohoPins),
    'base' => count($basePins),
];
file_put_contents(CACHE_FILE, json_encode($cacheData));

echo json_encode([
    'success' => true,
    'pins' => $allPins,
    'count' => count($allPins),
    'new_from_zoho' => count($zohoPins),
]);
