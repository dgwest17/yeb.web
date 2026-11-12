<?php
/**
 * prep.php — the "before we meet" answers (prep.js), into Zoho.
 *
 * Shown after the quote form (new solar, or existing solar if they picked that),
 * after an audit booking, and on /prep/ — a link David can text before a meeting.
 *
 *   POST multipart: data (JSON), bill (optional image/PDF), website (honeypot)
 *   data = { path: solar|audit, ref?: "<leadId>.<sig>" (from zoho.php), contact?: {first,last,phone,email},
 *            answers: {...}, summary: [[question, answer], ...] }
 *
 * Finds the Lead (signed ref → that Lead; else by email, then phone; else creates one),
 * fills Avg_Monthly_Bill / Opportunity_Type, adds a readable note (with the raw answers
 * for the rep app to read once it shows Leads), attaches the bill, emails David, and —
 * once Twilio texting is on (after 10DLC) — texts the homeowner a booking
 * link and David a heads-up.
 */
require_once __DIR__ . '/security-config.php';
require_once __DIR__ . '/newsletter-lib.php';   // nl_zoho_token(): the site's Zoho connection
header('Content-Type: application/json');

const PREP_CALENDLY = 'https://calendly.com/yourenergybest/energy';
const PREP_LUMARIA  = 'https://www.lumaria.energy/';
const PREP_DAVID    = '+13039419131';           // David's own phone (heads-up texts, once Twilio texting is on)
const PREP_NOTIFY   = 'dwest@yourenergybest.com';

function out($a, $code = 200) { http_response_code($code); echo json_encode($a); exit; }
function crm($method, $path, $body = null, $multipart = false) {
  $ch = curl_init('https://www.zohoapis.com/crm/v2' . $path);
  $h = ['Authorization: Zoho-oauthtoken ' . nl_zoho_token()];
  $o = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_CUSTOMREQUEST => $method];
  if ($body !== null) {
    if ($multipart) { $o[CURLOPT_POSTFIELDS] = $body; }
    else { $h[] = 'Content-Type: application/json'; $o[CURLOPT_POSTFIELDS] = json_encode($body); }
  }
  $o[CURLOPT_HTTPHEADER] = $h;
  curl_setopt_array($ch, $o);
  $raw = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  return [$code, json_decode($raw ?: '', true) ?: []];
}
function e164($p) { $d = preg_replace('/\D/', '', (string)$p); if (strlen($d) === 10) return '+1' . $d; if (strlen($d) === 11 && $d[0] === '1') return '+' . $d; return null; }
// Texts: Quo is gone (cancelled 2026-11-12). They come back through Twilio (858) 786-2204 once
// its texting registration (10DLC) is approved; until then David gets the email only.
function send_text($to, $msg) { return false; }

/** The signed Lead reference zoho.php hands the browser after the quote/audit form: "<leadId>.<sig>". */
function lead_ref_id($ref) {
  if (!preg_match('/^(\d{6,25})\.([a-f0-9]{24})$/', (string)$ref, $m) || !defined('AUDIT_ACCESS_SECRET')) return null;
  return hash_equals(substr(hash_hmac('sha256', 'prep:' . $m[1], AUDIT_ACCESS_SECRET), 0, 24), $m[2]) ? $m[1] : null;
}

// A "before we meet" link sent from app.lumaria.energy carries a token the rep app signed;
// only the rep app can say whether it's real and who it's for.
function lumaria_link($t) {
  if (!preg_match('/^[A-Za-z0-9_-]{10,600}\.[A-Za-z0-9_-]{20,64}$/', (string)$t)) return null;
  $ch = curl_init('https://app.lumaria.energy/api/prep-link?t=' . rawurlencode($t));
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
  $raw = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  $j = json_decode($raw ?: '', true);
  if ($code !== 200 || empty($j['ok']) || !preg_match('/^\d{6,25}$/', (string)($j['id'] ?? ''))
      || !in_array($j['module'] ?? '', ['Contacts', 'Leads'], true) || !in_array($j['kind'] ?? '', ['solar', 'audit'], true)) return null;
  return $j;
}

// GET ?t=… — the page asks who the link is for, to greet them by name
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['t'])) {
  $ipg = 'prepget_' . get_client_ip();
  if (is_rate_limited($ipg, 60, 3600)) out(['ok' => false], 429);
  record_rate_limit($ipg);
  $l = lumaria_link($_GET['t']);
  out($l ? ['ok' => true, 'first' => $l['first'] ?? '', 'kind' => $l['kind']] : ['ok' => false], $l ? 200 : 404);
}

// GET ?check=<ref> — the rep platform asks whether a website lead reference is real (it never
// sees our secret), before it makes that lead a lumaria.energy before-we-meet link.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check'])) {
  $id = lead_ref_id($_GET['check']);
  if (!$id) out(['ok' => false], 404);
  [$code, $j] = crm('GET', "/Leads/$id?fields=First_Name");
  out(['ok' => true, 'module' => 'Leads', 'id' => $id, 'first' => (string)($j['data'][0]['First_Name'] ?? '')]);
}

// GET ?link=<ref>&kind=solar|audit — after booking on the website: the lumaria.energy form for
// this new lead (Luma asks; the same form the rep app sends). The page falls back to prep.js if not.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['link'])) {
  $ipl = 'preplink_' . get_client_ip();
  if (is_rate_limited($ipl, 30, 3600)) out(['ok' => false], 429);
  record_rate_limit($ipl);
  $kind = ($_GET['kind'] ?? '') === 'audit' ? 'audit' : 'solar';
  if (!lead_ref_id($_GET['link'])) out(['ok' => false], 404);
  $ch = curl_init('https://app.lumaria.energy/api/prep-link?kind=' . $kind . '&ref=' . rawurlencode($_GET['link']));
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12]);
  $raw = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  $j = json_decode($raw ?: '', true);
  $url = (string)($j['url'] ?? '');
  if ($code !== 200 || strpos($url, 'https://www.lumaria.energy/prep/') !== 0) out(['ok' => false], 502);
  out(['ok' => true, 'url' => $url]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['ok' => false, 'error' => 'POST only'], 405);
if (!empty($_POST['website'])) out(['ok' => true]);                       // honeypot: bots get a quiet success
$ip = 'prep_' . get_client_ip();
if (is_rate_limited($ip, 12, 3600)) out(['ok' => false, 'error' => 'That\'s a lot of submissions — please call or text (858) 786-2204.'], 429);
record_rate_limit($ip);

$d = json_decode($_POST['data'] ?? '', true);
if (!is_array($d) || !in_array($d['path'] ?? '', ['solar', 'audit'], true)) out(['ok' => false, 'error' => 'Something went wrong — please try again.'], 400);
$path = $d['path'];
$c = array_map(fn($v) => trim(substr((string)$v, 0, 120)), (array)($d['contact'] ?? []));
$summary = array_slice(array_filter((array)($d['summary'] ?? []), 'is_array'), 0, 40);
$answers = (array)($d['answers'] ?? []);

try {
  // ── which record ── a link from app.lumaria.energy names the client outright (no form contact
  // details needed); otherwise the signed Lead ref, or a Lead found or made from what they typed.
  $mod = 'Leads';
  $leadId = null;
  $fromApp = false;
  if (!empty($d['t'])) {
    $l = lumaria_link($d['t']);
    if (!$l) out(['ok' => false, 'error' => 'This link isn\'t working — please call or text (858) 786-2204.'], 400);
    $mod = $l['module']; $leadId = $l['id']; $path = $l['kind']; $fromApp = true;
    [$code, $j] = crm('GET', "/$mod/$leadId?fields=First_Name,Last_Name,Phone,Mobile,Email");
    $r = $j['data'][0] ?? [];
    $c = ['first' => $r['First_Name'] ?? ($l['first'] ?? ''), 'last' => $r['Last_Name'] ?? '', 'phone' => ($r['Mobile'] ?? '') ?: ($r['Phone'] ?? ''), 'email' => $r['Email'] ?? ''];
  }
  if (!$leadId && preg_match('/^(\d{6,25})\.([a-f0-9]{24})$/', (string)($d['ref'] ?? ''), $m)
      && defined('AUDIT_ACCESS_SECRET') && hash_equals(substr(hash_hmac('sha256', 'prep:' . $m[1], AUDIT_ACCESS_SECRET), 0, 24), $m[2])) $leadId = $m[1];
  if (!$leadId) {
    $email = filter_var($c['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '';
    $phone = preg_replace('/\D/', '', $c['phone'] ?? '');
    if (($c['first'] ?? '') === '' || ($email === '' && strlen($phone) < 10)) out(['ok' => false, 'error' => 'Please add your name and a phone number or email.'], 400);
    foreach (array_filter(['email' => $email, 'phone' => $phone]) as $by => $v) {
      [$code, $j] = crm('GET', '/Leads/search?' . $by . '=' . urlencode($v));
      if ($code === 200 && !empty($j['data'][0]['id'])) { $leadId = $j['data'][0]['id']; break; }
    }
    if (!$leadId) {
      [$code, $j] = crm('POST', '/Leads', ['data' => [[
        'First_Name' => $c['first'], 'Last_Name' => ($c['last'] ?? '') ?: 'N/A', 'Email' => $email, 'Phone' => $c['phone'] ?? '',
        'Lead_Source' => 'Website', 'Lead_Status' => 'New', 'Description' => 'Source: Before-we-meet form (' . $path . ')']]]);
      $leadId = $j['data'][0]['details']['id'] ?? null;
      if (!$leadId) throw new RuntimeException('Could not save to Zoho');
    }
  }

  // ── fields the rep app and proposals use ── (Leads only: a client the rep app sent the link
  // to is already set up there, so its fields are the rep's to change, not this form's)
  if (!$fromApp) {
  $upd = [];
  if ($path === 'solar' && is_numeric($answers['bill'] ?? null) && $answers['bill'] > 0) $upd['Avg_Monthly_Bill'] = (float)$answers['bill'];
  $upd['Opportunity_Type'] = $path === 'audit'
    ? (in_array('A battery', (array)($answers['interested'] ?? []), true) ? 'Solar Owner – Add Battery' : 'Solar Owner – Audit / Review')
    : 'No Solar Yet';
  crm('PUT', '/Leads/' . $leadId, ['data' => [$upd]]);
  }

  // ── the answers, readable, plus the raw data for the rep app ──
  $lines = [];
  foreach ($summary as $row) { $q = trim((string)($row[0] ?? '')); $a = trim((string)($row[1] ?? '')); if ($q !== '' && $a !== '') $lines[] = "• $q\n  $a"; }
  $title = $path === 'audit' ? 'Before we meet — existing solar / audit' : 'Before we meet — new solar';
  $content = implode("\n", $lines) . "\n\n--- prep-data v1 ---\n" . json_encode(['path' => $path, 'answers' => $answers, 'at' => date('c')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  crm('POST', "/$mod/$leadId/Notes", ['data' => [['Note_Title' => $title, 'Note_Content' => mb_substr($content, 0, 30000)]]]);

  // ── the bill, if they added one ──
  $billSaved = false;
  if (!empty($_FILES['bill']['tmp_name']) && is_uploaded_file($_FILES['bill']['tmp_name'])) {
    $f = $_FILES['bill'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if ($f['size'] <= 15 * 1024 * 1024 && in_array($mime, ['image/jpeg', 'image/png', 'image/heic', 'image/heif', 'image/webp', 'application/pdf'], true)) {
      $name = 'bill-' . date('Ymd') . '-' . preg_replace('/[^A-Za-z0-9._-]/', '', basename($f['name']));
      [$code] = crm('POST', "/$mod/$leadId/Attachments", ['file' => new CURLFile($f['tmp_name'], $mime, $name)], true);
      $billSaved = $code >= 200 && $code < 300;
    }
  }

  // ── tell David; text them once Twilio texting is on ──
  $who = trim(($c['first'] ?? '') . ' ' . ($c['last'] ?? '')) ?: 'A lead';
  @mail(PREP_NOTIFY, "Before-we-meet form: $who (" . ($path === 'audit' ? 'existing solar' : 'new solar') . ')',
    implode("\n", $lines) . "\n\n" . ($billSaved ? "Bill attached in Zoho.\n" : '') . "Zoho: https://crm.zoho.com/crm/tab/$mod/$leadId",
    "From: Lumaria Energy <noreply@yourenergybest.com>\r\n");
  $texted = false;
  $to = e164($c['phone'] ?? '');
  if ($to) {
    $first = $c['first'] ?? '';
    $texted = send_text($to, "Hi" . ($first ? " $first" : '') . ", it's David with Lumaria Energy — thanks, I've got your answers! "
      . "Grab a time that works: " . PREP_CALENDLY . " — or just reply here with a good time. Reply STOP to opt out.");
  }
  send_text(PREP_DAVID, "Prep form in: $who (" . ($path === 'audit' ? 'audit' : 'new solar') . "). https://crm.zoho.com/crm/tab/$mod/$leadId");

  out(['ok' => true, 'calendly' => PREP_CALENDLY, 'lumaria' => $path === 'audit' ? PREP_LUMARIA : null, 'texted' => $texted, 'bill' => $billSaved]);
} catch (Throwable $e) {
  // never lose the answers: email them even if Zoho is down
  @mail(PREP_NOTIFY, 'Before-we-meet form (Zoho failed — saved here)', print_r(['contact' => $c, 'path' => $path, 'summary' => $summary, 'error' => $e->getMessage()], true),
    "From: Lumaria Energy <noreply@yourenergybest.com>\r\n");
  out(['ok' => true, 'calendly' => PREP_CALENDLY, 'lumaria' => $path === 'audit' ? PREP_LUMARIA : null, 'texted' => false, 'note' => 'emailed']);
}
