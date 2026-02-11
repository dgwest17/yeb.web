<?php
// zoho.php — Zoho CRM integration for Leads + Contacts (Newsletter)
// Also saves every submission locally as backup so no lead is ever lost

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

error_reporting(E_ALL);
ini_set('display_errors', 0);

// ─── CONFIG ───
define('CLIENT_ID',          '1000.W1ZOGCIIX44GUMUK0827B9V9ZHC12L');
define('CLIENT_SECRET',      'b7fc12526163429eda966f0f289096708eceb82983');
define('REFRESH_TOKEN_FILE', __DIR__ . '/.zoho_refresh_token');
define('LOG_FILE',           __DIR__ . '/zoho_debug.log');
define('LEADS_BACKUP_FILE',  __DIR__ . '/leads_backup.csv');
define('NOTIFY_EMAIL',       'info@yourenergybest.com');

function logDebug($msg) {
    file_put_contents(LOG_FILE, date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
}

// ─── TOKEN MANAGEMENT ───
function getAccessToken() {
    if (!file_exists(REFRESH_TOKEN_FILE)) {
        logDebug('ERROR: No refresh token file. You need to generate one. See setup instructions.');
        return null;
    }
    
    $refreshToken = trim(file_get_contents(REFRESH_TOKEN_FILE));
    if (empty($refreshToken)) {
        logDebug('ERROR: Refresh token file is empty');
        return null;
    }
    
    $postData = [
        'refresh_token' => $refreshToken,
        'client_id'     => CLIENT_ID,
        'client_secret' => CLIENT_SECRET,
        'grant_type'    => 'refresh_token'
    ];
    
    $ch = curl_init('https://accounts.zoho.com/oauth/v2/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($postData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) { logDebug('CURL error refreshing token: ' . $err); return null; }
    
    $result = json_decode($response, true);
    if (isset($result['access_token'])) {
        logDebug('Access token obtained');
        return $result['access_token'];
    }
    
    logDebug('Token refresh failed: ' . $response);
    return null;
}

// ─── SAVE LEAD LOCALLY (never lose a lead) ───
function saveLocalBackup($data, $type = 'lead') {
    $isNew = !file_exists(LEADS_BACKUP_FILE);
    $fp = fopen(LEADS_BACKUP_FILE, 'a');
    if ($isNew) {
        fputcsv($fp, ['timestamp', 'type', 'name', 'email', 'phone', 'zip', 'option', 'source']);
    }
    fputcsv($fp, [
        date('Y-m-d H:i:s'),
        $type,
        $data['name'] ?? '',
        $data['email'] ?? '',
        $data['phone'] ?? '',
        $data['zip'] ?? '',
        $data['customer_option'] ?? '',
        $data['source'] ?? 'website'
    ]);
    fclose($fp);
}

// ─── SEND EMAIL NOTIFICATION ───
function sendNotification($data, $type = 'lead') {
    $subject = ($type === 'lead') 
        ? 'New Solar Lead: ' . ($data['name'] ?? 'Unknown')
        : 'New Newsletter Signup: ' . ($data['email'] ?? 'Unknown');
    
    $body = "New {$type} from yourenergybest.com\n\n";
    foreach ($data as $key => $val) {
        if (!empty($val)) $body .= ucfirst(str_replace('_', ' ', $key)) . ": {$val}\n";
    }
    $body .= "\nTime: " . date('Y-m-d H:i:s');
    
    @mail(NOTIFY_EMAIL, $subject, $body, "From: noreply@yourenergybest.com\r\nReply-To: " . ($data['email'] ?? NOTIFY_EMAIL));
}

// ─── CREATE ZOHO CRM RECORD ───
function createZohoRecord($module, $recordData, $accessToken) {
    $payload = json_encode(['data' => [$recordData]]);
    
    $ch = curl_init("https://www.zohoapis.com/crm/v2/{$module}");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Zoho-oauthtoken ' . $accessToken,
            'Content-Type: application/json'
        ],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    logDebug("Zoho {$module} response [{$httpCode}]: {$response}");
    if ($err) logDebug("CURL error: {$err}");
    
    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

// ─── HANDLE REQUESTS ───
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
logDebug("Received {$action} submission: " . json_encode($input));

// Always save locally first — never lose a lead
saveLocalBackup($input, $action);

// Always send email notification
sendNotification($input, $action);

// Try Zoho CRM
$accessToken = getAccessToken();

if ($action === 'newsletter') {
    // ─── NEWSLETTER → Zoho Contact ───
    if (!$accessToken) {
        logDebug('No token, but email saved locally and notification sent');
        echo json_encode(['success' => true, 'note' => 'Saved locally, Zoho sync pending']);
        exit;
    }
    
    $contactData = [
        'Email'       => $input['email'] ?? '',
        'First_Name'  => $input['first_name'] ?? '',
        'Last_Name'   => $input['last_name'] ?? ($input['first_name'] ?? 'Newsletter Subscriber'),
        'Lead_Source'  => 'Newsletter Popup',
        'Description' => 'Newsletter signup from website'
    ];
    
    $result = createZohoRecord('Contacts', $contactData, $accessToken);
    
    if ($result['code'] === 200 || $result['code'] === 201) {
        echo json_encode(['success' => true]);
    } else {
        // Still return success to user since we saved locally
        logDebug('Zoho contact creation failed, but saved locally');
        echo json_encode(['success' => true, 'note' => 'Saved locally, Zoho sync pending']);
    }
    
} else {
    // ─── LEAD (quote form) → Zoho Lead ───
    if (!$accessToken) {
        logDebug('No token, but lead saved locally and notification sent');
        // Return success to user — we have their info, just Zoho sync failed
        echo json_encode(['success' => true, 'note' => 'Saved locally, Zoho sync pending']);
        exit;
    }
    
    $nameParts = explode(' ', trim($input['name'] ?? 'Unknown'), 2);
    $firstName = $nameParts[0];
    $lastName  = $nameParts[1] ?? $nameParts[0];
    
    $leadData = [
        'First_Name'  => $firstName,
        'Last_Name'   => $lastName,
        'Email'       => $input['email'] ?? '',
        'Phone'       => $input['phone'] ?? '',
        'Zip_Code'    => $input['zip'] ?? '',
        'Lead_Source'  => 'Website Quote Form',
        'Description' => 'Customer Option: ' . ($input['customer_option'] ?? 'Not specified')
    ];
    
    $result = createZohoRecord('Leads', $leadData, $accessToken);
    
    if ($result['code'] === 200 || $result['code'] === 201) {
        echo json_encode(['success' => true]);
    } else {
        // If Zip_Code field doesn't exist, try without it
        if ($result['code'] === 400 || $result['code'] === 202) {
            logDebug('Retrying without Zip_Code field...');
            unset($leadData['Zip_Code']);
            $leadData['Description'] .= ' | Zip: ' . ($input['zip'] ?? 'N/A');
            $retry = createZohoRecord('Leads', $leadData, $accessToken);
            
            if ($retry['code'] === 200 || $retry['code'] === 201) {
                echo json_encode(['success' => true]);
                exit;
            }
        }
        
        // Still return success — we have the lead saved locally + email sent
        logDebug('Zoho lead creation failed, but saved locally and notified');
        echo json_encode(['success' => true, 'note' => 'Saved locally, Zoho sync pending']);
    }
}
