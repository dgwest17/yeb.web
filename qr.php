<?php
/**
 * qr.php — Admin → QR Codes. Admin session required; changes also need the CSRF token.
 *
 *   GET                                   → { codes: [...] } with scan totals and the last 30 days
 *   POST {action:'save', slug, name, dest} → create, or update the name/destination of an existing code
 *   POST {action:'archive'|'restore', slug}
 *
 * A code's slug is what's printed (/go/<slug>), so it never changes once created, and
 * codes are archived instead of deleted (an archived code sends scans to the homepage).
 * The QR images themselves are drawn in the browser (admin/index.html).
 */
require_once __DIR__ . '/security-config.php';
header('Content-Type: application/json');
require_admin_auth();

const QR_CODES = __DIR__ . '/.qr_codes.json';
const QR_SCANS = __DIR__ . '/.qr_scans.json';

function out($a) { echo json_encode($a, JSON_UNESCAPED_SLASHES); exit; }
function load_codes() { return json_decode(@file_get_contents(QR_CODES) ?: '{}', true)['codes'] ?? []; }
function save_codes(array $codes) {
  $ok = file_put_contents(QR_CODES . '.tmp', json_encode(['codes' => $codes], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false
     && rename(QR_CODES . '.tmp', QR_CODES);
  if ($ok) @chmod(QR_CODES, 0600);
  return $ok;
}
/** https:// or http:// link, or a path on this site. Nothing else gets printed. */
function clean_dest($d) {
  $d = trim((string)$d);
  if ($d !== '' && $d[0] === '/' && (strlen($d) === 1 || $d[1] !== '/')) return $d;
  if (preg_match('#^https?://[^\s/$.?\#][^\s]*$#i', $d) && filter_var($d, FILTER_VALIDATE_URL)) return $d;
  return null;
}

$codes = load_codes();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $scans = json_decode(@file_get_contents(QR_SCANS) ?: '{}', true) ?: [];
  $tz = new DateTimeZone('America/Los_Angeles');
  $days = [];
  for ($i = 29; $i >= 0; $i--) $days[] = (new DateTime("-$i days", $tz))->format('Y-m-d');
  $list = [];
  foreach ($codes as $slug => $c) {
    $s = $scans[$slug] ?? [];
    $daily = array_map(fn($d) => $s[$d] ?? 0, $days);
    $list[] = $c + ['slug' => $slug, 'total' => array_sum($s), 'last30' => array_sum($daily),
      'last7' => array_sum(array_slice($daily, -7)), 'daily' => $daily, 'last_scan' => $s ? max(array_keys($s)) : null];
  }
  usort($list, fn($a, $b) => [!empty($a['archived']), $b['created'] ?? ''] <=> [!empty($b['archived']), $a['created'] ?? '']);
  out(['codes' => $list, 'base' => 'https://yourenergybest.com/go/']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); out(['error' => 'GET or POST']); }
validate_csrf();
$in = json_decode(file_get_contents('php://input'), true) ?: [];
$slug = strtolower(trim((string)($in['slug'] ?? '')));
if (!preg_match('/^[a-z0-9](?:[a-z0-9-]{0,38}[a-z0-9])?$/', $slug)) out(['ok' => false, 'error' => 'Use 1–40 lowercase letters, numbers and dashes, like yard-sign or flyer-dec.']);
$now = date('c');

switch ($in['action'] ?? '') {
  case 'save':
    $name = trim(mb_substr((string)($in['name'] ?? ''), 0, 80));
    $dest = clean_dest($in['dest'] ?? '');
    if ($name === '') out(['ok' => false, 'error' => 'Give it a name you\'ll recognize, like "Yard sign".']);
    if ($dest === null) out(['ok' => false, 'error' => 'The destination must start with https:// or be a page on this site like /audit/.']);
    $isNew = !isset($codes[$slug]);
    // The form sends create_only for a new code, so it can't overwrite one that's already printed
    if (!$isNew && !empty($in['create_only'])) out(['ok' => false, 'error' => "/go/$slug is already taken. Pick another name."]);
    $codes[$slug] = $codes[$slug] ?? ['created' => $now];
    $codes[$slug]['name'] = $name;
    $codes[$slug]['dest'] = $dest;
    $codes[$slug]['updated'] = $now;
    if (!save_codes($codes)) out(['ok' => false, 'error' => 'Could not save on the server.']);
    security_log('qr_saved', ['slug' => $slug, 'new' => $isNew]);
    out(['ok' => true, 'created' => $isNew]);

  case 'archive':
  case 'restore':
    if (!isset($codes[$slug])) out(['ok' => false, 'error' => 'No such code.']);
    $codes[$slug]['archived'] = $in['action'] === 'archive';
    $codes[$slug]['updated'] = $now;
    if (!save_codes($codes)) out(['ok' => false, 'error' => 'Could not save on the server.']);
    security_log('qr_' . $in['action'], ['slug' => $slug]);
    out(['ok' => true]);

  default:
    http_response_code(400);
    out(['error' => 'Unknown action']);
}
