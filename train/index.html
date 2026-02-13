<?php
// ═══════════════════════════════════════════════════════════
// zoho.php — Zoho CRM Lead Upsert (Phase 4)
// Handles: Quote Wizard, Newsletter Popup, PayPal Intake
// Features: Upsert by email, local CSV backup, email notify,
//           structured logging, test mode (?test=true)
// ═══════════════════════════════════════════════════════════

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

error_reporting(E_ALL);
ini_set('display_errors', 0);

// ─── CONFIG ───
define('CLIENT_ID',          '1000.E3XUN0DQA4EZ8XZDGLTVVN33YEPB4V');
define('CLIENT_SECRET',      'e6c650a1fdf0dcaa229d2f3569c24d8e7a927205c3');
define('REFRESH_TOKEN_FILE', __DIR__ . '/.zoho_refresh_token');
define('LOG_FILE',           __DIR__ . '/zoho_debug.log');
define('LEADS_BACKUP_FILE',  __DIR__ . '/leads_backup.csv');
define('NOTIFY_EMAIL',       'info@yourenergybest.com');
define('ZOHO_API_BASE',      'https://www.zohoapis.com/crm/v2');

// ─── Opportunity Type Mapping ───
$OPPORTUNITY_MAP = [
    'New to Solar'        => 'No Solar Yet',
    'new'                 => 'No Solar Yet',
    '$0 Down Financing'   => 'No Solar Yet',
    'financing'           => 'No Solar Yet',
    'Ready for Proposal'  => 'No Solar - Bid Searching',
    'proposal'            => 'No Solar - Bid Searching',
    'Already Have Solar'  => 'Solar Owner – Audit / Review',
    'existing'            => 'Solar Owner – Audit / Review',
    // Pay/Service page options (pass through directly)
    'No Solar Yet'                      => 'No Solar Yet',
    'No Solar - Bid Searching'          => 'No Solar - Bid Searching',
    'Solar Owner – Audit / Review'      => 'Solar Owner – Audit / Review',
    'Solar Owner – Service / Repair'    => 'Solar Owner – Service / Repair',
    'Solar Owner – Under Service Plan'  => 'Solar Owner – Under Service Plan',
];

// ═══════════════════════════════════════════════════════════
// UTILITY FUNCTIONS
// ═══════════════════════════════════════════════════════════

function logDebug($msg) {
    file_put_contents(LOG_FILE, date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
}

function logStructured($source, $status, $data = [], $zohoId = null, $error = null) {
    $entry = [
        'timestamp' => date('c'),
        'source'    => $source,
        'status'    => $status,
        'email'     => $data['email'] ?? '',
        'zoho_id'   => $zohoId,
        'error'     => $error,
    ];
    logDebug('STRUCTURED: ' . json_encode($entry));
}

function splitName($fullName) {
    $name = trim($fullName ?? '');
    if (empty($name)) return ['First' => 'Unknown', 'Last' => 'N/A'];
    
    $parts = explode(' ', $name, 2);
    $first = $parts[0];
    $last  = isset($parts[1]) && trim($parts[1]) !== '' ? trim($parts[1]) : 'N/A';
    return ['First' => $first, 'Last' => $last];
}

function resolveOpportunityType($input) {
    global $OPPORTUNITY_MAP;
    $raw = $input['opportunity_type'] ?? $input['customer_option'] ?? '';
    if (empty($raw)) return null;
    // Direct match first
    if (isset($OPPORTUNITY_MAP[$raw])) return $OPPORTUNITY_MAP[$raw];
    // Case-insensitive search
    foreach ($OPPORTUNITY_MAP as $key => $val) {
        if (strcasecmp($key, $raw) === 0) return $val;
    }
    // If it's already a valid Zoho value, pass through
    if (in_array($raw, array_values($OPPORTUNITY_MAP))) return $raw;
    return $raw;
}

// ─── TOKEN ───
function getAccessToken() {
    if (!file_exists(REFRESH_TOKEN_FILE)) {
        logDebug('ERROR: No refresh token file');
        return null;
    }
    $refreshToken = trim(file_get_contents(REFRESH_TOKEN_FILE));
    if (empty($refreshToken)) {
        logDebug('ERROR: Refresh token file is empty');
        return null;
    }
    
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
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) { logDebug('CURL error refreshing token: ' . $err); return null; }
    
    $result = json_decode($response, true);
    if (isset($result['access_token'])) return $result['access_token'];
    
    logDebug('Token refresh failed: ' . $response);
    return null;
}

// ─── LOCAL BACKUP ───
function saveLocalBackup($data, $source) {
    $isNew = !file_exists(LEADS_BACKUP_FILE);
    $fp = fopen(LEADS_BACKUP_FILE, 'a');
    if ($isNew) {
        fputcsv($fp, ['timestamp','source','name','email','phone','zip','monthly_bill','opportunity_type','lead_source']);
    }
    fputcsv($fp, [
        date('Y-m-d H:i:s'),
        $source,
        $data['name'] ?? $data['first_name'] ?? '',
        $data['email'] ?? '',
        $data['phone'] ?? '',
        $data['zip'] ?? '',
        $data['monthly_bill'] ?? $data['avg_monthly_bill'] ?? '',
        $data['opportunity_type'] ?? $data['customer_option'] ?? '',
        $data['lead_source'] ?? 'Website'
    ]);
    fclose($fp);
}

// ─── EMAIL NOTIFICATION ───
function sendNotification($data, $source) {
    $subject = "New Lead [{$source}]: " . ($data['name'] ?? $data['first_name'] ?? $data['email'] ?? 'Unknown');
    $body = "New lead from yourenergybest.com\nSource: {$source}\n\n";
    foreach ($data as $key => $val) {
        if (!empty($val) && $key !== 'action' && $key !== 'source_form') {
            $body .= ucfirst(str_replace('_', ' ', $key)) . ": {$val}\n";
        }
    }
    $body .= "\nTime: " . date('Y-m-d H:i:s');
    @mail(NOTIFY_EMAIL, $subject, $body, "From: noreply@yourenergybest.com\r\nReply-To: " . ($data['email'] ?? NOTIFY_EMAIL));
}

// ─── ZOHO API CALL ───
function zohoRequest($method, $url, $accessToken, $body = null) {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Zoho-oauthtoken ' . $accessToken,
            'Content-Type: application/json'
        ],
    ];
    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        if ($body) $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    } elseif ($method === 'PUT') {
        $opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
        if ($body) $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    }
    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) logDebug("CURL error [{$method}]: {$err}");
    logDebug("Zoho [{$method}] [{$httpCode}]: " . substr($response, 0, 500));
    
    return ['code' => $httpCode, 'body' => json_decode($response, true), 'raw' => $response];
}

// ─── UPSERT: Search by email, update if found, create if not ───
function upsertLead($leadData, $accessToken, $email) {
    // Search for existing lead by email
    if (!empty($email)) {
        $searchUrl = ZOHO_API_BASE . '/Leads/search?email=' . urlencode($email);
        $search = zohoRequest('GET', $searchUrl, $accessToken);
        
        if ($search['code'] === 200 && !empty($search['body']['data'])) {
            $existingId = $search['body']['data'][0]['id'];
            logDebug("Found existing lead {$existingId} for {$email} — updating");
            
            // Don't overwrite Lead_Status on update
            unset($leadData['Lead_Status']);
            
            $result = zohoRequest('PUT', ZOHO_API_BASE . "/Leads/{$existingId}", $accessToken, ['data' => [$leadData]]);
            
            if ($result['code'] === 200) {
                return ['success' => true, 'id' => $existingId, 'action' => 'updated'];
            }
            logDebug("Update failed [{$result['code']}], trying create instead");
        }
    }
    
    // CREATE new lead
    $result = zohoRequest('POST', ZOHO_API_BASE . '/Leads', $accessToken, ['data' => [$leadData]]);
    
    if ($result['code'] === 200 || $result['code'] === 201) {
        $newId = $result['body']['data'][0]['details']['id'] ?? null;
        return ['success' => true, 'id' => $newId, 'action' => 'created'];
    }
    
    // Retry with minimal fields if full create failed
    logDebug("Full create failed, trying minimal fields...");
    $minimal = [
        'First_Name'            => $leadData['First_Name'],
        'Last_Name'             => $leadData['Last_Name'],
        'Email'                 => $leadData['Email'] ?? '',
        'Phone'                 => $leadData['Phone'] ?? '',
        'Lead_Source'            => $leadData['Lead_Source'] ?? 'Website',
        'Lead_Status'            => 'New',
        'Newsletter_Subscriber' => true,
        'Description'           => $leadData['Description'] ?? '',
    ];
    $retry = zohoRequest('POST', ZOHO_API_BASE . '/Leads', $accessToken, ['data' => [$minimal]]);
    
    if ($retry['code'] === 200 || $retry['code'] === 201) {
        $newId = $retry['body']['data'][0]['details']['id'] ?? null;
        return ['success' => true, 'id' => $newId, 'action' => 'created_minimal'];
    }
    
    return ['success' => false, 'error' => $retry['raw']];
}

// ═══════════════════════════════════════════════════════════
// HANDLE REQUEST
// ═══════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$action = $input['action'] ?? 'lead';
$sourceForm = $input['source_form'] ?? ($action === 'newsletter' ? 'NewsletterPopup' : 'QuoteWizard');

// Test mode
$isTest = !empty($input['test']) || (isset($_GET['test']) && $_GET['test'] === 'true');
if ($isTest) {
    logDebug("=== TEST MODE ===");
    $sourceForm .= '_TEST';
}

logDebug("━━━ Received [{$sourceForm}]: " . json_encode($input));

// Always save locally + email
saveLocalBackup($input, $sourceForm);
sendNotification($input, $sourceForm);

// Trace ID
$traceId = 'WebLead:' . date('Ymd-His') . '-' . substr(md5(uniqid()), 0, 6);

// Access token
$accessToken = getAccessToken();
if (!$accessToken) {
    logStructured($sourceForm, 'token_fail', $input);
    echo json_encode(['success' => true, 'note' => 'Saved locally, Zoho sync pending', 'trace' => $traceId]);
    exit;
}

// ─── BUILD LEAD DATA ───
$email = trim($input['email'] ?? '');

// ═══ RECRUIT HANDLER (Training Access Requests) ═══
if ($action === 'recruit') {
    // Name handling
    if (!empty($input['name'])) {
        $nameParts = splitName($input['name']);
    } else {
        $nameParts = [
            'First' => trim($input['first_name'] ?? '') ?: 'Unknown',
            'Last'  => trim($input['last_name'] ?? '') ?: 'N/A'
        ];
    }
    
    $recruitData = [
        'Name'              => $nameParts['First'],
        'Last_Name'         => $nameParts['Last'],
        'Email'             => $email,
        'Phone_1'           => $input['phone'] ?? '',
        'How_you_found_us'  => 'Website - Training Portal',
        'Status'            => 'New',
        'Tag'               => 'TRAINING_ACCESS_REQUEST',
        'Rep_Notes'         => "Training access requested | Trace: {$traceId}",
    ];
    
    if ($isTest) {
        $recruitData['Tag'] = 'TEST_RECRUIT,TRAINING_ACCESS_REQUEST';
    }
    
    logDebug("Recruit payload: " . json_encode($recruitData));
    
    // Search for existing recruit by email
    $existingId = null;
    if (!empty($email)) {
        $searchUrl = ZOHO_API_BASE . '/Recruits/search?email=' . urlencode($email);
        $search = zohoRequest('GET', $searchUrl, $accessToken);
        if ($search['code'] === 200 && !empty($search['body']['data'])) {
            $existingId = $search['body']['data'][0]['id'];
            logDebug("Found existing recruit {$existingId} for {$email}");
        }
    }
    
    if ($existingId) {
        // Update existing
        unset($recruitData['Status']);
        $result = zohoRequest('PUT', ZOHO_API_BASE . "/Recruits/{$existingId}", $accessToken, ['data' => [$recruitData]]);
        if ($result['code'] === 200) {
            logStructured($sourceForm, 'success', $input, $existingId);
            echo json_encode(['success' => true, 'zoho_id' => $existingId, 'action' => 'updated', 'trace' => $traceId]);
            exit;
        }
    }
    
    // Create new
    $result = zohoRequest('POST', ZOHO_API_BASE . '/Recruits', $accessToken, ['data' => [$recruitData]]);
    if ($result['code'] === 200 || $result['code'] === 201) {
        $newId = $result['body']['data'][0]['details']['id'] ?? null;
        logStructured($sourceForm, 'success', $input, $newId);
        echo json_encode(['success' => true, 'zoho_id' => $newId, 'action' => 'created', 'trace' => $traceId]);
    } else {
        logStructured($sourceForm, 'fail', $input, null, $result['raw']);
        echo json_encode(['success' => true, 'note' => 'Saved locally, Zoho sync pending', 'trace' => $traceId]);
    }
    exit;
}

// ═══ LEAD HANDLER (Quote, Newsletter, PayPal) ═══
$opportunityType = resolveOpportunityType($input);

$leadSource = $input['lead_source'] ?? 'Website';

// Name splitting
if (!empty($input['name'])) {
    $nameParts = splitName($input['name']);
} else {
    $first = trim($input['first_name'] ?? '');
    $last  = trim($input['last_name'] ?? '');
    $nameParts = [
        'First' => $first ?: 'Unknown',
        'Last'  => $last ?: 'N/A'
    ];
}

// Description trace
$descParts = ["Source: {$sourceForm}", "Trace: {$traceId}"];
if (!empty($input['customer_option'])) $descParts[] = "Selection: {$input['customer_option']}";
if (!empty($input['payment_note']))    $descParts[] = "Payment: {$input['payment_note']}";
foreach (['utm_source','utm_medium','utm_campaign','utm_content','utm_term','referrer'] as $k) {
    if (!empty($input[$k])) $descParts[] = "{$k}: {$input[$k]}";
}
if ($isTest) $descParts[] = "TEST_LEAD";

// Assemble lead record
$leadData = [
    'First_Name'            => $nameParts['First'],
    'Last_Name'             => $nameParts['Last'],
    'Email'                 => $email,
    'Phone'                 => $input['phone'] ?? '',
    'Zip_Code'              => $input['zip'] ?? '',
    'Lead_Source'            => $leadSource,
    'Lead_Status'            => 'New',
    'Newsletter_Subscriber' => true,
    'Description'           => implode(' | ', $descParts),
];

if ($opportunityType) {
    $leadData['Opportunity_Type'] = $opportunityType;
}

// Avg Monthly Bill as number
$bill = $input['monthly_bill'] ?? $input['avg_monthly_bill'] ?? null;
if ($bill !== null && $bill !== '') {
    $numBill = floatval(preg_replace('/[^0-9.]/', '', strval($bill)));
    if ($numBill > 0) {
        $leadData['Avg_Monthly_Bill'] = $numBill;
    }
}

if ($isTest) {
    $leadData['Tag'] = [['name' => 'TEST_LEAD']];
}

logDebug("Lead payload: " . json_encode($leadData));

// ─── UPSERT ───
$result = upsertLead($leadData, $accessToken, $email);

if ($result['success']) {
    logStructured($sourceForm, 'success', $input, $result['id']);
    echo json_encode([
        'success'  => true,
        'zoho_id'  => $result['id'],
        'action'   => $result['action'],
        'trace'    => $traceId,
    ]);
} else {
    logStructured($sourceForm, 'fail', $input, null, $result['error'] ?? 'Unknown');
    echo json_encode([
        'success' => true,
        'note'    => 'Saved locally, Zoho sync pending',
        'trace'   => $traceId,
    ]);
}
