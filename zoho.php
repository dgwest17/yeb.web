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
        fputcsv($fp, ['timestamp', 'type', 'name', 'email', 'phone', 'zip', 'monthly_bill', 'option', 'source']);
    }
    fputcsv($fp, [
        date('Y-m-d H:i:s'),
        $type,
        $data['name'] ?? '',
        $data['email'] ?? '',
        $data['phone'] ?? '',
        $data['zip'] ?? '',
        $data['monthly_bill'] ?? '',
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
    // ─── NEWSLETTER → Zoho Lead with "Newsletter" tag ───
    if (!$accessToken) {
        logDebug('No token, but email saved locally and notification sent');
        echo json_encode(['success' => true, 'note' => 'Saved locally, Zoho sync pending']);
        exit;
    }
    
    // Build UTM description
    $utmParts = [];
    foreach (['utm_source','utm_medium','utm_campaign','utm_content','utm_term','referrer'] as $k) {
        if (!empty($input[$k])) $utmParts[] = "$k: {$input[$k]}";
    }
    $utmDesc = $utmParts ? implode(' | ', $utmParts) : '';
    
    $leadData = [
        'First_Name'  => $input['first_name'] ?? 'Newsletter',
        'Last_Name'   => $input['last_name'] ?? ($input['first_name'] ?? 'Subscriber'),
        'Email'       => $input['email'] ?? '',
        'Lead_Source'  => 'Website',
        'Tag'         => [['name' => 'Newsletter']],
        'Description' => 'Newsletter signup from website' . ($utmDesc ? " | $utmDesc" : '')
    ];
    
    // Dedup check — search for existing lead with same email
    $searchUrl = 'https://www.zohoapis.com/crm/v2/Leads/search?email=' . urlencode($input['email'] ?? '');
    $sch = curl_init($searchUrl);
    curl_setopt_array($sch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Authorization: Zoho-oauthtoken ' . $accessToken,
        ],
    ]);
    $searchResp = curl_exec($sch);
    $searchCode = curl_getinfo($sch, CURLINFO_HTTP_CODE);
    curl_close($sch);
    
    if ($searchCode === 200) {
        $existing = json_decode($searchResp, true);
        if (!empty($existing['data'])) {
            logDebug('Duplicate found for email: ' . ($input['email'] ?? '') . ' — skipping create, updating tag');
            // Update existing record to add Newsletter tag
            $existingId = $existing['data'][0]['id'];
            $updateData = ['Tag' => [['name' => 'Newsletter']]];
            $updatePayload = json_encode(['data' => [$updateData]]);
            $uch = curl_init("https://www.zohoapis.com/crm/v2/Leads/{$existingId}");
            curl_setopt_array($uch, [
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_POSTFIELDS => $updatePayload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Zoho-oauthtoken ' . $accessToken,
                    'Content-Type: application/json'
                ],
            ]);
            curl_exec($uch);
            curl_close($uch);
            echo json_encode(['success' => true]);
            exit;
        }
    }
    
    $result = createZohoRecord('Leads', $leadData, $accessToken);
    
    if ($result['code'] === 200 || $result['code'] === 201) {
        echo json_encode(['success' => true]);
    } else {
        logDebug('Zoho lead creation failed for newsletter, but saved locally');
        echo json_encode(['success' => true, 'note' => 'Saved locally, Zoho sync pending']);
    }
    
} else {
    // ─── LEAD (quote form) → Zoho Lead with dedup + UTM ───
    if (!$accessToken) {
        logDebug('No token, but lead saved locally and notification sent');
        echo json_encode(['success' => true, 'note' => 'Saved locally, Zoho sync pending']);
        exit;
    }
    
    $nameParts = explode(' ', trim($input['name'] ?? 'Unknown'), 2);
    $firstName = $nameParts[0];
    $lastName  = $nameParts[1] ?? $nameParts[0];
    
    // Build UTM description
    $utmParts = [];
    foreach (['utm_source','utm_medium','utm_campaign','utm_content','utm_term','referrer'] as $k) {
        if (!empty($input[$k])) $utmParts[] = "$k: {$input[$k]}";
    }
    $utmDesc = $utmParts ? implode(' | ', $utmParts) : '';
    
    $leadData = [
        'First_Name'  => $firstName,
        'Last_Name'   => $lastName,
        'Email'       => $input['email'] ?? '',
        'Phone'       => $input['phone'] ?? '',
        'Zip_Code'    => $input['zip'] ?? '',
        'Lead_Source'  => 'Website',
        'Tag'         => [['name' => 'Quote Request']],
        'Description' => 'Customer Option: ' . ($input['customer_option'] ?? 'Not specified')
                       . ' | Monthly Bill: ' . ($input['monthly_bill'] ?? 'Not specified')
                       . ($utmDesc ? " | $utmDesc" : '')
    ];
    
    // Dedup check
    $email = $input['email'] ?? '';
    if ($email) {
        $searchUrl = 'https://www.zohoapis.com/crm/v2/Leads/search?email=' . urlencode($email);
        $sch = curl_init($searchUrl);
        curl_setopt_array($sch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Authorization: Zoho-oauthtoken ' . $accessToken],
        ]);
        $searchResp = curl_exec($sch);
        $searchCode = curl_getinfo($sch, CURLINFO_HTTP_CODE);
        curl_close($sch);
        
        if ($searchCode === 200) {
            $existing = json_decode($searchResp, true);
            if (!empty($existing['data'])) {
                logDebug('Duplicate lead found for: ' . $email . ' — updating instead of creating');
                $existingId = $existing['data'][0]['id'];
                $leadData['Description'] = '[Returning Lead] ' . $leadData['Description'];
                $updatePayload = json_encode(['data' => [$leadData]]);
                $uch = curl_init("https://www.zohoapis.com/crm/v2/Leads/{$existingId}");
                curl_setopt_array($uch, [
                    CURLOPT_CUSTOMREQUEST => 'PUT',
                    CURLOPT_POSTFIELDS => $updatePayload,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Zoho-oauthtoken ' . $accessToken,
                        'Content-Type: application/json'
                    ],
                ]);
                $dupResp = curl_exec($uch);
                curl_close($uch);
                logDebug('Dedup update response: ' . $dupResp);
                echo json_encode(['success' => true]);
                exit;
            }
        }
    }
    
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
        
        logDebug('Zoho lead creation failed, but saved locally and notified');
        echo json_encode(['success' => true, 'note' => 'Saved locally, Zoho sync pending']);
    }
}
