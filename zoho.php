<?php
// ═══════════════════════════════════════════════════════════
// zoho.php — Zoho CRM Lead Upsert (Phase 4 + Spam Filters)
// Features: Upsert by email, CSV backup, email notify,
//           SPAM PROTECTION: honeypot, timing, gibberish, email
// ═══════════════════════════════════════════════════════════

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

error_reporting(E_ALL);
ini_set('display_errors', 0);

$__cfg = file_exists(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];
define('CLIENT_ID',          $__cfg['zoho_client_id']     ?? '');
define('CLIENT_SECRET',      $__cfg['zoho_client_secret'] ?? '');
define('REFRESH_TOKEN_FILE', __DIR__ . '/.zoho_refresh_token');
define('LOG_FILE',           __DIR__ . '/zoho_debug.log');
define('LEADS_BACKUP_FILE',  __DIR__ . '/leads_backup.csv');
define('SPAM_LOG_FILE',      __DIR__ . '/spam_blocked.log');
define('NOTIFY_EMAIL',       'info@yourenergybest.com');
define('ZOHO_API_BASE',      'https://www.zohoapis.com/crm/v2');
require_once __DIR__ . '/lead-alerts.php';

// ═══════════════════════════════════════════════════════════
// SPAM PROTECTION — Runs BEFORE anything else
// Returns success either way, so bots think it worked
// A filled honeypot is dropped. Anything else suspicious is HELD: backed up and
// emailed to David, never discarded (it used to lose real people silently).
// ═══════════════════════════════════════════════════════════

function isGibberish($str) {
    $str = trim($str ?? '');
    if (strlen($str) < 2) return false;
    // Alternating caps pattern: UhRgOzhkFFEkFhac
    if (preg_match('/[A-Z][a-z][A-Z][a-z][A-Z]/', $str)) return true;
    // 7+ consonants in a row, or a long word with no vowel at all (y counts as a vowel).
    // It was 5+ and "under 15% vowels", which threw away Schmidt, Schwartz, Lynch,
    // Kirschner, Lindstrom and other real surnames.
    if (preg_match('/[^aeiouy\s]{7,}/i', preg_replace('/[^a-zA-Z\s]/', '', $str))) return true;
    $letters = preg_replace('/[^a-zA-Z]/', '', $str);
    if (strlen($letters) > 5 && !preg_match('/[aeiouy]/i', $letters)) return true;
    // (An all-caps rule used to sit here. Real people type "JANE DOE" on phones,
    //  and blocked leads get a fake success, so it silently lost real homeowners.)
    return false;
}

function blockSpam($reason, $input) {
    $entry = date('[Y-m-d H:i:s]') . " BLOCKED [{$reason}]" .
             " IP:" . ($_SERVER['REMOTE_ADDR'] ?? '?') .
             " Email:" . ($input['email'] ?? 'none') .
             " Name:" . ($input['name'] ?? $input['first_name'] ?? 'none') . "\n";
    @file_put_contents(SPAM_LOG_FILE, $entry, FILE_APPEND);
    if ($reason !== 'honeypot') {
        saveLocalBackup($input, 'HELD');
        // At most 25 of these emails an hour, so a bot flood can't bury the inbox
        $seenFile = __DIR__ . '/.held_alerts.json';
        $seen = array_filter((array)json_decode(@file_get_contents($seenFile) ?: '[]', true), fn($t) => $t > time() - 3600);
        if (count($seen) < 25) {
            $seen[] = time();
            @file_put_contents($seenFile, json_encode(array_values($seen)), LOCK_EX);
            yeb_lead_alert('Website lead held as possible spam', $reason, $input);
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

// Server-to-server calls (stripe-lib.php) sign the body; they skip the spam layers
// so a paying customer is never silently dropped as "gibberish".
$__raw = file_get_contents('php://input');
$__isInternal = defined('AUDIT_ACCESS_SECRET') && AUDIT_ACCESS_SECRET !== ''
    && hash_equals(hash_hmac('sha256', $__raw, AUDIT_ACCESS_SECRET), $_SERVER['HTTP_X_YEB_INTERNAL'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$__isInternal) {
    $spamCheck = json_decode($__raw, true);
    if ($spamCheck) {
        // Layer 1: Honeypot — hidden fields bots fill
        if (!empty($spamCheck['website']) || !empty($spamCheck['company_url']))
            blockSpam('honeypot', $spamCheck);

        // Layer 2: Timing — form must take > 3 seconds
        // (a phone whose clock runs ahead gives a negative number: that isn't a bot)
        $took = !empty($spamCheck['_ts']) ? time() - intval($spamCheck['_ts']) : null;
        if ($took !== null && $took >= 0 && $took < 3)
            blockSpam('too_fast:' . $took . 's', $spamCheck);

        // Layer 3: Gibberish names
        $fn = $spamCheck['name'] ?? $spamCheck['first_name'] ?? '';
        $ln = $spamCheck['last_name'] ?? '';
        if (isGibberish($fn) || isGibberish($ln))
            blockSpam('gibberish:' . $fn . ' ' . $ln, $spamCheck);

        // Layer 5: Cloudflare human check (nav.js sends it; held, never dropped)
        require_once __DIR__ . '/human-check.php';
        [$__human, $__why] = human_check();
        if (!$__human) blockSpam($__why, $spamCheck);

        // Layer 4: Bad email
        $em = trim($spamCheck['email'] ?? '');
        if (!empty($em)) {
            if (preg_match('/^N\/A/i', $em) || !filter_var($em, FILTER_VALIDATE_EMAIL) ||
                strlen($em) < 6 || preg_match('/\.(ru|cn|tk|ml|ga|cf|gq|xyz|top|buzz|click)$/i', $em))
                blockSpam('bad_email:' . $em, $spamCheck);
        }
    }
}

// ═══════════════════════════════════════════════════════════
// UTILITY FUNCTIONS (unchanged)
// ═══════════════════════════════════════════════════════════

$OPPORTUNITY_MAP = [
    'New to Solar'        => 'No Solar Yet',
    'new'                 => 'No Solar Yet',
    '$0 Down Financing'   => 'No Solar Yet',
    'financing'           => 'No Solar Yet',
    'Ready for Proposal'  => 'No Solar - Bid Searching',
    'proposal'            => 'No Solar - Bid Searching',
    'Already Have Solar'  => 'Solar Owner – Audit / Review',
    'existing'            => 'Solar Owner – Audit / Review',
    'No Solar Yet'                      => 'No Solar Yet',
    'No Solar - Bid Searching'          => 'No Solar - Bid Searching',
    'Solar Owner – Audit / Review'      => 'Solar Owner – Audit / Review',
    'Solar Owner – Service / Repair'    => 'Solar Owner – Service / Repair',
    'Solar Owner – Under Service Plan'  => 'Solar Owner – Under Service Plan',
];

function logDebug($msg) {
    file_put_contents(LOG_FILE, date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
}

function logStructured($source, $status, $data = [], $zohoId = null, $error = null) {
    logDebug('STRUCTURED: ' . json_encode(['timestamp'=>date('c'),'source'=>$source,'status'=>$status,'email'=>$data['email']??'','zoho_id'=>$zohoId,'error'=>$error]));
}

function splitName($fullName) {
    $name = trim($fullName ?? '');
    if (empty($name)) return ['First' => 'Unknown', 'Last' => 'N/A'];
    $parts = explode(' ', $name, 2);
    return ['First' => $parts[0], 'Last' => isset($parts[1]) && trim($parts[1]) !== '' ? trim($parts[1]) : 'N/A'];
}

function resolveOpportunityType($input) {
    global $OPPORTUNITY_MAP;
    $raw = $input['opportunity_type'] ?? $input['customer_option'] ?? '';
    if (empty($raw)) return null;
    if (isset($OPPORTUNITY_MAP[$raw])) return $OPPORTUNITY_MAP[$raw];
    foreach ($OPPORTUNITY_MAP as $key => $val) { if (strcasecmp($key, $raw) === 0) return $val; }
    if (in_array($raw, array_values($OPPORTUNITY_MAP))) return $raw;
    return $raw;
}

function getAccessToken() {
    if (!file_exists(REFRESH_TOKEN_FILE)) { logDebug('ERROR: No refresh token file'); return null; }
    $refreshToken = trim(file_get_contents(REFRESH_TOKEN_FILE));
    if (empty($refreshToken)) { logDebug('ERROR: Refresh token file is empty'); return null; }
    $ch = curl_init('https://accounts.zoho.com/oauth/v2/token');
    curl_setopt_array($ch, [CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query(['refresh_token'=>$refreshToken,'client_id'=>CLIENT_ID,'client_secret'=>CLIENT_SECRET,'grant_type'=>'refresh_token']),CURLOPT_RETURNTRANSFER=>true,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_TIMEOUT=>15]);
    $response = curl_exec($ch); $err = curl_error($ch); curl_close($ch);
    if ($err) { logDebug('CURL error refreshing token: ' . $err); return null; }
    $result = json_decode($response, true);
    if (isset($result['access_token'])) return $result['access_token'];
    logDebug('Token refresh failed: ' . $response); return null;
}

function saveLocalBackup($data, $source) {
    $isNew = !file_exists(LEADS_BACKUP_FILE);
    $fp = fopen(LEADS_BACKUP_FILE, 'a');
    if ($isNew) fputcsv($fp, ['timestamp','source','name','email','phone','zip','monthly_bill','opportunity_type','lead_source']);
    fputcsv($fp, [date('Y-m-d H:i:s'),$source,$data['name']??$data['first_name']??'',$data['email']??'',$data['phone']??'',$data['zip']??'',$data['monthly_bill']??$data['avg_monthly_bill']??'',$data['opportunity_type']??$data['customer_option']??'',$data['lead_source']??'Website']);
    fclose($fp);
}

function sendNotification($data, $source) {
    $subject = "New Lead [{$source}]: " . ($data['name'] ?? $data['first_name'] ?? $data['email'] ?? 'Unknown');
    $body = "New lead from yourenergybest.com\nSource: {$source}\n\n";
    foreach ($data as $key => $val) {
        if (!empty($val) && !in_array($key, ['action','source_form','_ts','website','company_url']))
            $body .= ucfirst(str_replace('_', ' ', $key)) . ": {$val}\n";
    }
    $body .= "\nTime: " . date('Y-m-d H:i:s');
    $replyTo = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: NOTIFY_EMAIL;
    @mail(NOTIFY_EMAIL, str_replace(["\r", "\n"], ' ', $subject), $body, "From: noreply@yourenergybest.com\r\nReply-To: " . $replyTo);
}

function zohoRequest($method, $url, $accessToken, $body = null) {
    $ch = curl_init($url);
    $opts = [CURLOPT_RETURNTRANSFER=>true,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>['Authorization: Zoho-oauthtoken '.$accessToken,'Content-Type: application/json']];
    if ($method === 'POST') { $opts[CURLOPT_POST] = true; if ($body) $opts[CURLOPT_POSTFIELDS] = json_encode($body); }
    elseif ($method === 'PUT') { $opts[CURLOPT_CUSTOMREQUEST] = 'PUT'; if ($body) $opts[CURLOPT_POSTFIELDS] = json_encode($body); }
    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch); $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    if ($err) logDebug("CURL error [{$method}]: {$err}");
    logDebug("Zoho [{$method}] [{$httpCode}]: " . substr($response, 0, 500));
    return ['code' => $httpCode, 'body' => json_decode($response, true), 'raw' => $response];
}

function upsertLead($leadData, $accessToken, $email) {
    if (!empty($email)) {
        $search = zohoRequest('GET', ZOHO_API_BASE . '/Leads/search?email=' . urlencode($email), $accessToken);
        if ($search['code'] === 200 && !empty($search['body']['data'])) {
            $existingId = $search['body']['data'][0]['id'];
            logDebug("Found existing lead {$existingId} for {$email} — updating");
            unset($leadData['Lead_Status']);
            $result = zohoRequest('PUT', ZOHO_API_BASE . "/Leads/{$existingId}", $accessToken, ['data' => [$leadData]]);
            if ($result['code'] === 200) return ['success' => true, 'id' => $existingId, 'action' => 'updated'];
            logDebug("Update failed [{$result['code']}], trying create instead");
        }
    }
    $result = zohoRequest('POST', ZOHO_API_BASE . '/Leads', $accessToken, ['data' => [$leadData]]);
    if ($result['code'] === 200 || $result['code'] === 201) {
        $newId = $result['body']['data'][0]['details']['id'] ?? null;
        return ['success' => true, 'id' => $newId, 'action' => 'created'];
    }
    logDebug("Full create failed, trying minimal...");
    $minimal = ['First_Name'=>$leadData['First_Name'],'Last_Name'=>$leadData['Last_Name'],'Email'=>$leadData['Email']??'','Phone'=>$leadData['Phone']??'','Lead_Source'=>$leadData['Lead_Source']??'Website','Lead_Status'=>'New','Newsletter_Subscriber'=>true,'Description'=>$leadData['Description']??''];
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

$input = json_decode($__raw, true);
if (!$input) { http_response_code(400); echo json_encode(['error' => 'Invalid JSON']); exit; }

// Timeline notes and the 'note' action are server-to-server only
$zohoNote = $__isInternal && is_array($input['zoho_note'] ?? null) ? $input['zoho_note'] : null;
if (($input['action'] ?? '') === 'note' && !$__isInternal) { http_response_code(400); echo json_encode(['error' => 'Invalid action']); exit; }

// Strip spam-filter fields before processing
// Text opt-in from the website form (human.js adds the box): noted on the Lead, never assumed
$textsOk = !empty($input['texts_ok']);
unset($input['website'], $input['company_url'], $input['_ts'], $input['zoho_note'], $input['texts_ok']);

$action = $input['action'] ?? 'lead';
$sourceForm = $input['source_form'] ?? ($action === 'newsletter' ? 'NewsletterPopup' : 'QuoteWizard');

$isTest = !empty($input['test']) || (isset($_GET['test']) && $_GET['test'] === 'true');
if ($isTest) { logDebug("=== TEST MODE ==="); $sourceForm .= '_TEST'; }

logDebug("━━━ Received [{$sourceForm}]: " . json_encode($input));

saveLocalBackup($input, $sourceForm);
sendNotification($zohoNote ? $input + ['event' => $zohoNote['title'] . ' — ' . $zohoNote['content']] : $input, $sourceForm);

$traceId = 'WebLead:' . date('Ymd-His') . '-' . substr(md5(uniqid()), 0, 6);
$accessToken = getAccessToken();
if (!$accessToken) {
    logStructured($sourceForm, 'token_fail', $input);
    yeb_lead_alert('Website lead NOT saved to Zoho', 'The site could not sign in to Zoho (reconnect it in Admin, Connections).', $input);
    echo json_encode(['success' => true, 'note' => 'Saved locally, Zoho sync pending', 'trace' => $traceId]);
    exit;
}

$email = trim($input['email'] ?? '');

function addLeadNote($leadId, $note, $accessToken) {
    if (!$leadId || !$note) return;
    $r = zohoRequest('POST', ZOHO_API_BASE . "/Leads/{$leadId}/Notes", $accessToken, ['data' => [[
        'Note_Title' => substr($note['title'] ?? 'Note', 0, 120),
        'Note_Content' => $note['content'] ?? '',
    ]]]);
    if ($r['code'] !== 200 && $r['code'] !== 201) logDebug("Note on lead {$leadId} failed [{$r['code']}]");
}

// ═══ NOTE HANDLER (server-to-server: Stripe renewals, failed cards, cancellations) ═══
// Adds to the existing lead's timeline without overwriting its fields.
// No matching lead yet → falls through and creates one, then the note lands on it.
if ($action === 'note') {
    if ($email === '') {
        logStructured($sourceForm, 'note_no_email', $input);
        echo json_encode(['success' => true, 'note' => 'No email — emailed only', 'trace' => $traceId]); exit;
    }
    $search = zohoRequest('GET', ZOHO_API_BASE . '/Leads/search?email=' . urlencode($email), $accessToken);
    if ($search['code'] === 200 && !empty($search['body']['data'])) {
        $leadId = $search['body']['data'][0]['id'];
        addLeadNote($leadId, $zohoNote, $accessToken);
        logStructured($sourceForm, 'note', $input, $leadId);
        echo json_encode(['success' => true, 'zoho_id' => $leadId, 'action' => 'noted', 'trace' => $traceId]); exit;
    }
    $action = 'lead';
}

// ═══ RECRUIT HANDLER ═══
if ($action === 'recruit') {
    if (!empty($input['name'])) { $nameParts = splitName($input['name']); }
    else { $nameParts = ['First' => trim($input['first_name'] ?? '') ?: 'Unknown', 'Last' => trim($input['last_name'] ?? '') ?: 'N/A']; }

    $repNotesParts = [];
    if (!empty($input['d2d_experience']))   $repNotesParts[] = "D2D Experience: {$input['d2d_experience']}";
    if (!empty($input['sales_experience'])) $repNotesParts[] = "Sales Experience: {$input['sales_experience']}";
    if (!empty($input['blitz_available']))  $repNotesParts[] = "Blitz Available: {$input['blitz_available']}";
    if (!empty($input['why_good_fit']))     $repNotesParts[] = "Why Good Fit:\n{$input['why_good_fit']}";
    $repNotesParts[] = "Source: {$sourceForm} | Trace: {$traceId}";

    $recruitData = [
        'Name'=>$nameParts['First'],'Last_Name'=>$nameParts['Last'],'Email'=>$email,
        'Phone_1'=>$input['phone']??'','How_you_found_us'=>$input['lead_source']??'Website - Build Page',
        'D2D_Experience'=>$input['d2d_experience']??'','Sales_Experience'=>$input['sales_experience']??'',
        'Blitz_Availability'=>$input['blitz_available']??'','Rep_Notes'=>implode("\n\n",$repNotesParts),
        'Status'=>'New','Tag'=>$isTest?'TEST_RECRUIT,BUILD_PAGE_WAITLIST':'BUILD_PAGE_WAITLIST',
    ];

    logDebug("Recruit payload: " . json_encode($recruitData));
    $existingId = null;
    if (!empty($email)) {
        $search = zohoRequest('GET', ZOHO_API_BASE . '/Recruits/search?email=' . urlencode($email), $accessToken);
        if ($search['code'] === 200 && !empty($search['body']['data'])) $existingId = $search['body']['data'][0]['id'];
    }
    if ($existingId) {
        unset($recruitData['Status']);
        $result = zohoRequest('PUT', ZOHO_API_BASE . "/Recruits/{$existingId}", $accessToken, ['data' => [$recruitData]]);
        if ($result['code'] === 200) {
            logStructured($sourceForm, 'success', $input, $existingId);
            echo json_encode(['success'=>true,'zoho_id'=>$existingId,'action'=>'updated','trace'=>$traceId]); exit;
        }
    }
    $result = zohoRequest('POST', ZOHO_API_BASE . '/Recruits', $accessToken, ['data' => [$recruitData]]);
    if ($result['code'] === 200 || $result['code'] === 201) {
        $newId = $result['body']['data'][0]['details']['id'] ?? null;
        logStructured($sourceForm, 'success', $input, $newId);
        echo json_encode(['success'=>true,'zoho_id'=>$newId,'action'=>'created','trace'=>$traceId]);
    } else {
        logStructured($sourceForm, 'fail', $input, null, $result['raw']);
        yeb_lead_alert('Build With Us signup NOT saved to Zoho', 'Zoho answered: ' . substr((string)$result['raw'], 0, 300), $input);
        echo json_encode(['success'=>true,'note'=>'Saved locally, Zoho sync pending','trace'=>$traceId]);
    }
    exit;
}

// ═══ LEAD HANDLER ═══
$opportunityType = resolveOpportunityType($input);
$leadSource = $input['lead_source'] ?? 'Website';
if (!empty($input['name'])) { $nameParts = splitName($input['name']); }
else { $nameParts = ['First' => trim($input['first_name'] ?? '') ?: 'Unknown', 'Last' => trim($input['last_name'] ?? '') ?: 'N/A']; }

$descParts = ["Source: {$sourceForm}", "Trace: {$traceId}"];
if (!empty($input['customer_option'])) $descParts[] = "Selection: {$input['customer_option']}";
if (!empty($input['payment_note']))    $descParts[] = "Payment: {$input['payment_note']}";
if (!empty($input['reason']))          $descParts[] = "Reason: " . (is_array($input['reason']) ? implode(', ', $input['reason']) : $input['reason']);
if (!empty($input['notes']))           $descParts[] = "Notes: {$input['notes']}";
if (!empty($input['fee_note']))        $descParts[] = $input['fee_note'];
foreach (['utm_source','utm_medium','utm_campaign','utm_content','utm_term','referrer'] as $k) {
    if (!empty($input[$k])) $descParts[] = "{$k}: {$input[$k]}";
}
if ($isTest) $descParts[] = "TEST_LEAD";

$leadData = [
    'First_Name'=>$nameParts['First'],'Last_Name'=>$nameParts['Last'],'Email'=>$email,
    'Phone'=>$input['phone']??'','Zip_Code'=>$input['zip']??'',
    'Lead_Source'=>$leadSource,'Lead_Status'=>'New','Newsletter_Subscriber'=>true,
    'Description'=>implode(' | ',$descParts),
];
// Home address (the audit booking pages send street / city / zip)
foreach (['street' => 'Street', 'city' => 'City', 'state' => 'State'] as $in => $zf) {
    if (!empty($input[$in])) $leadData[$zf] = substr(trim($input[$in]), 0, $zf === 'Street' ? 250 : 100);
}
if ($opportunityType) $leadData['Opportunity_Type'] = $opportunityType;
$bill = $input['monthly_bill'] ?? $input['avg_monthly_bill'] ?? null;
if ($bill !== null && $bill !== '') { $numBill = floatval(preg_replace('/[^0-9.]/', '', strval($bill))); if ($numBill > 0) $leadData['Avg_Monthly_Bill'] = $numBill; }
if ($isTest) $leadData['Tag'] = [['name' => 'TEST_LEAD']];

logDebug("Lead payload: " . json_encode($leadData));
$result = upsertLead($leadData, $accessToken, $email);

if ($result['success']) {
    addLeadNote($result['id'], $zohoNote, $accessToken);
    logStructured($sourceForm, 'success', $input, $result['id']);
    // A signed reference to this Lead, so the follow-up questions (prep.php) attach to it
    $prep = ($result['id'] && defined('AUDIT_ACCESS_SECRET')) ? $result['id'] . '.' . substr(hash_hmac('sha256', 'prep:' . $result['id'], AUDIT_ACCESS_SECRET), 0, 24) : null;
    if ($textsOk) addLeadNote($result['id'], ['title' => 'Texts OK (website opt-in)',
        'content' => 'Ticked "OK to text me about my request" on the ' . $sourceForm . ' form, ' . date('M j, Y g:i a T') . ', from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown IP') . '. Shown: rates may apply, frequency varies, reply STOP to opt out, HELP for help.'], $accessToken);
    echo json_encode(['success'=>true,'zoho_id'=>$result['id'],'action'=>$result['action'],'trace'=>$traceId,'prep'=>$prep]);
    // LEAD SPEED: text David now (lumaria /api/lead-alert checks the signed ref with prep.php first).
    // After the response is sent, so the visitor never waits on it.
    if ($prep && !$__isInternal && !$isTest) {
        if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
        $ch = curl_init('https://www.lumaria.energy/api/lead-alert');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['ref' => $prep, 'form' => $sourceForm, 'opp' => $input['opportunity_type'] ?? '', 'texts_ok' => $textsOk])]);
        $alert = curl_exec($ch); $alertCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if ($alertCode !== 200) logDebug("Lead text alert failed [$alertCode]: " . substr((string)$alert, 0, 200));
    }
} else {
    logStructured($sourceForm, 'fail', $input, null, $result['error'] ?? 'Unknown');
    yeb_lead_alert('Website lead NOT saved to Zoho', 'Zoho answered: ' . substr((string)($result['error'] ?? 'Unknown'), 0, 300), $input);
    echo json_encode(['success'=>true,'note'=>'Saved locally, Zoho sync pending','trace'=>$traceId]);
}
