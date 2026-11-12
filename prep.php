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
 * once Quo's API key is saved in Admin → Connections — texts the homeowner a booking
 * link and David a heads-up.
 */
require_once __DIR__ . '/security-config.php';
require_once __DIR__ . '/newsletter-lib.php';   // nl_zoho_token(): the site's Zoho connection
header('Content-Type: application/json');

const PREP_CALENDLY = 'https://calendly.com/yourenergybest/energy';
const PREP_LUMARIA  = 'https://www.lumaria.energy/';
const PREP_FROM     = '+17604547868';           // Quo business line
const PREP_DAVID    = '+13039419131';           // David's own phone (heads-up texts)
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
function quo_text($to, $msg) {
  $key = trim(@file_get_contents(__DIR__ . '/.quo_api_key') ?: '');
  if ($key === '' || !$to) return false;
  $ch = curl_init('https://api.openphone.com/v1/messages');
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Authorization: ' . $key, 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['content' => $msg, 'from' => PREP_FROM, 'to' => [$to]])]);
  curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  return $code >= 200 && $code < 300;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['ok' => false, 'error' => 'POST only'], 405);
if (!empty($_POST['website'])) out(['ok' => true]);                       // honeypot: bots get a quiet success
$ip = 'prep_' . get_client_ip();
if (is_rate_limited($ip, 12, 3600)) out(['ok' => false, 'error' => 'That\'s a lot of submissions — please call or text (760) 454-7868.'], 429);
record_rate_limit($ip);

$d = json_decode($_POST['data'] ?? '', true);
if (!is_array($d) || !in_array($d['path'] ?? '', ['solar', 'audit'], true)) out(['ok' => false, 'error' => 'Something went wrong — please try again.'], 400);
$path = $d['path'];
$c = array_map(fn($v) => trim(substr((string)$v, 0, 120)), (array)($d['contact'] ?? []));
$summary = array_slice(array_filter((array)($d['summary'] ?? []), 'is_array'), 0, 40);
$answers = (array)($d['answers'] ?? []);

try {
  // ── which Lead ──
  $leadId = null;
  if (preg_match('/^(\d{6,25})\.([a-f0-9]{24})$/', (string)($d['ref'] ?? ''), $m)
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

  // ── fields the rep app and proposals use ──
  $upd = [];
  if ($path === 'solar' && is_numeric($answers['bill'] ?? null) && $answers['bill'] > 0) $upd['Avg_Monthly_Bill'] = (float)$answers['bill'];
  $upd['Opportunity_Type'] = $path === 'audit'
    ? (in_array('A battery', (array)($answers['interested'] ?? []), true) ? 'Solar Owner – Add Battery' : 'Solar Owner – Audit / Review')
    : 'No Solar Yet';
  crm('PUT', '/Leads/' . $leadId, ['data' => [$upd]]);

  // ── the answers, readable, plus the raw data for the rep app ──
  $lines = [];
  foreach ($summary as $row) { $q = trim((string)($row[0] ?? '')); $a = trim((string)($row[1] ?? '')); if ($q !== '' && $a !== '') $lines[] = "• $q\n  $a"; }
  $title = $path === 'audit' ? 'Before we meet — existing solar / audit' : 'Before we meet — new solar';
  $content = implode("\n", $lines) . "\n\n--- prep-data v1 ---\n" . json_encode(['path' => $path, 'answers' => $answers, 'at' => date('c')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  crm('POST', '/Leads/' . $leadId . '/Notes', ['data' => [['Note_Title' => $title, 'Note_Content' => mb_substr($content, 0, 30000)]]]);

  // ── the bill, if they added one ──
  $billSaved = false;
  if (!empty($_FILES['bill']['tmp_name']) && is_uploaded_file($_FILES['bill']['tmp_name'])) {
    $f = $_FILES['bill'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if ($f['size'] <= 15 * 1024 * 1024 && in_array($mime, ['image/jpeg', 'image/png', 'image/heic', 'image/heif', 'image/webp', 'application/pdf'], true)) {
      $name = 'bill-' . date('Ymd') . '-' . preg_replace('/[^A-Za-z0-9._-]/', '', basename($f['name']));
      [$code] = crm('POST', '/Leads/' . $leadId . '/Attachments', ['file' => new CURLFile($f['tmp_name'], $mime, $name)], true);
      $billSaved = $code >= 200 && $code < 300;
    }
  }

  // ── tell David; text them if Quo is connected ──
  $who = trim(($c['first'] ?? '') . ' ' . ($c['last'] ?? '')) ?: 'A lead';
  @mail(PREP_NOTIFY, "Before-we-meet form: $who (" . ($path === 'audit' ? 'existing solar' : 'new solar') . ')',
    implode("\n", $lines) . "\n\n" . ($billSaved ? "Bill attached in Zoho.\n" : '') . "Zoho lead: https://crm.zoho.com/crm/tab/Leads/$leadId",
    "From: Lumaria Energy <noreply@yourenergybest.com>\r\n");
  $texted = false;
  $to = e164($c['phone'] ?? '');
  if ($to) {
    $first = $c['first'] ?? '';
    $texted = quo_text($to, "Hi" . ($first ? " $first" : '') . ", it's David with Lumaria Energy — thanks, I've got your answers! "
      . "Grab a time that works: " . PREP_CALENDLY . " — or just reply here with a good time. Reply STOP to opt out.");
  }
  quo_text(PREP_DAVID, "Prep form in: $who (" . ($path === 'audit' ? 'audit' : 'new solar') . "). https://crm.zoho.com/crm/tab/Leads/$leadId");

  out(['ok' => true, 'calendly' => PREP_CALENDLY, 'lumaria' => $path === 'audit' ? PREP_LUMARIA : null, 'texted' => $texted, 'bill' => $billSaved]);
} catch (Throwable $e) {
  // never lose the answers: email them even if Zoho is down
  @mail(PREP_NOTIFY, 'Before-we-meet form (Zoho failed — saved here)', print_r(['contact' => $c, 'path' => $path, 'summary' => $summary, 'error' => $e->getMessage()], true),
    "From: Lumaria Energy <noreply@yourenergybest.com>\r\n");
  out(['ok' => true, 'calendly' => PREP_CALENDLY, 'lumaria' => $path === 'audit' ? PREP_LUMARIA : null, 'texted' => false, 'note' => 'emailed']);
}
