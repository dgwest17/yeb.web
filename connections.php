<?php
/**
 * connections.php — Admin → Connections. Status of every outside service the
 * site talks to, and a safe place to paste a new token. Admin session required;
 * changes also need the CSRF token. Secrets are written to dotfiles (blocked by
 * .htaccess, ignored by git) and are NEVER sent back to the browser.
 *
 *   GET                                  → status of each connection
 *   POST {action:'instagram', token}      → checks the token with Instagram, saves .instagram_token
 *   POST {action:'zoho', code}            → trades a Zoho Self Client grant code for a refresh token
 *   POST {action:'stripe_webhook', secret}→ saves the webhook signing secret (whsec_...)
 *   POST {action:'google', key, place_id} → saves the Google Places key + place id (reviews)
 *   POST {action:'cleanup'}               → deletes leftovers git can't remove (see $STRAY)
 */
require_once __DIR__ . '/security-config.php';
header('Content-Type: application/json');
require_admin_auth();

$cfg = file_exists(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];
$F = [
  'instagram' => __DIR__ . '/.instagram_token',
  'zoho'      => __DIR__ . '/.zoho_refresh_token',
  'webhook'   => __DIR__ . '/.stripe_webhook_secret',
  'gkey'      => __DIR__ . '/.google_api_key',
  'gplace'    => __DIR__ . '/.google_place_id',
];
// Leftovers git can't remove (never committed): a stray token copy, and what Become
// and Kinobi wrote on the server — uploads, test scripts, logs, rep account files.
// Become's lesson files were copied into Lumaria's Quiver before it was retired.
$STRAY = [__DIR__ . '/instagram_token.txt', __DIR__ . '/become', __DIR__ . '/kinobi',
          __DIR__ . '/train-content.json', __DIR__ . '/train-users.json'];

function remove_path($p) {
  if (is_link($p) || is_file($p)) return @unlink($p);
  if (!is_dir($p)) return false;
  foreach (array_diff(scandir($p), ['.', '..']) as $e) remove_path($p . '/' . $e);
  return @rmdir($p);
}

function http($method, $url, $headers = [], $body = null) {
  $ch = curl_init($url);
  $o = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => $headers];
  if ($method === 'POST') { $o[CURLOPT_POST] = true; $o[CURLOPT_POSTFIELDS] = $body; }
  curl_setopt_array($ch, $o);
  $resp = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return [$code, json_decode($resp ?: '', true) ?: []];
}
function save_secret($file, $value) {
  if (@file_put_contents($file, $value, LOCK_EX) === false) return false;
  @chmod($file, 0600);
  return true;
}
function ig_token($file) {
  if (!file_exists($file)) return null;
  $raw = trim(file_get_contents($file));
  $j = json_decode($raw, true);
  return $j['access_token'] ?? ($raw ?: null);
}
function zoho_token($cfg, $refresh) {
  [$code, $j] = http('POST', 'https://accounts.zoho.com/oauth/v2/token', [], http_build_query([
    'refresh_token' => $refresh, 'client_id' => $cfg['zoho_client_id'] ?? '',
    'client_secret' => $cfg['zoho_client_secret'] ?? '', 'grant_type' => 'refresh_token']));
  return $j['access_token'] ?? null;
}
function out($a) { echo json_encode($a); exit; }

// ─── Status ───
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $s = [];

  $tok = ig_token($F['instagram']);
  $s['instagram'] = ['configured' => (bool)$tok];
  if ($tok) {
    [$c, $j] = http('GET', 'https://graph.instagram.com/me?fields=username&access_token=' . urlencode($tok));
    $s['instagram'] += ['ok' => $c === 200, 'account' => $j['username'] ?? null,
      'saved_days_ago' => (int)floor((time() - filemtime($F['instagram'])) / 86400),
      'error' => $c === 200 ? null : ($j['error']['message'] ?? "HTTP $c")];
  }

  $ref = file_exists($F['zoho']) ? trim(file_get_contents($F['zoho'])) : '';
  $s['zoho'] = ['configured' => $ref !== '', 'client_configured' => !empty($cfg['zoho_client_id']) && !empty($cfg['zoho_client_secret'])];
  if ($ref !== '') $s['zoho']['ok'] = (bool)zoho_token($cfg, $ref);

  $s['stripe'] = ['secret_key' => defined('STRIPE_SECRET_KEY') && STRIPE_SECRET_KEY !== '',
    'live' => defined('STRIPE_SECRET_KEY') && strpos(STRIPE_SECRET_KEY, 'sk_live_') === 0,
    'webhook_secret' => (defined('STRIPE_WEBHOOK_SECRET') && STRIPE_WEBHOOK_SECRET !== '') || file_exists($F['webhook'])];
  if ($s['stripe']['secret_key']) {
    [$c, $j] = http('GET', 'https://api.stripe.com/v1/account', ['Authorization: Bearer ' . STRIPE_SECRET_KEY]);
    $s['stripe']['ok'] = $c === 200;
    $s['stripe']['account'] = $j['settings']['dashboard']['display_name'] ?? ($j['business_profile']['name'] ?? null);
  }
  $s['stripe']['webhook_url'] = 'https://yourenergybest.com/stripe-webhook.php';

  $s['google'] = ['configured' => file_exists($F['gkey']) && file_exists($F['gplace'])];

  $s['stray_files'] = array_values(array_map('basename', array_filter($STRAY, 'file_exists')));
  out($s);
}

// ─── Changes ───
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); out(['error' => 'GET or POST']); }
validate_csrf();
$in = json_decode(file_get_contents('php://input'), true) ?: [];

switch ($in['action'] ?? '') {
  case 'instagram':
    $tok = trim($in['token'] ?? '');
    if (strlen($tok) < 40) out(['ok' => false, 'error' => "That doesn't look like an Instagram token."]);
    [$c, $j] = http('GET', 'https://graph.instagram.com/me?fields=username&access_token=' . urlencode($tok));
    if ($c !== 200) out(['ok' => false, 'error' => 'Instagram rejected it: ' . ($j['error']['message'] ?? "HTTP $c")]);
    if (!save_secret($F['instagram'], json_encode(['access_token' => $tok, 'saved_at' => time()]))) out(['ok' => false, 'error' => 'Could not write the token file.']);
    @unlink(__DIR__ . '/ig_cache.json');   // show the new account's posts right away
    security_log('connection_updated', ['service' => 'instagram']);
    out(['ok' => true, 'account' => $j['username'] ?? null]);

  case 'zoho':
    $grant = trim($in['code'] ?? '');
    if ($grant === '') out(['ok' => false, 'error' => 'Paste the code from the Zoho API Console.']);
    [$c, $j] = http('POST', 'https://accounts.zoho.com/oauth/v2/token', [], http_build_query([
      'grant_type' => 'authorization_code', 'code' => $grant,
      'client_id' => $cfg['zoho_client_id'] ?? '', 'client_secret' => $cfg['zoho_client_secret'] ?? '']));
    if (empty($j['refresh_token'])) out(['ok' => false, 'error' => 'Zoho said: ' . ($j['error'] ?? "HTTP $c") . '. Codes expire in minutes and work once — generate a fresh one.']);
    if (!save_secret($F['zoho'], $j['refresh_token'])) out(['ok' => false, 'error' => 'Could not write the token file.']);
    @unlink(__DIR__ . '/map-pins-cache.json');
    security_log('connection_updated', ['service' => 'zoho']);
    out(['ok' => true]);

  case 'stripe_webhook':
    $sec = trim($in['secret'] ?? '');
    if (strpos($sec, 'whsec_') !== 0) out(['ok' => false, 'error' => 'The signing secret starts with whsec_.']);
    if (!save_secret($F['webhook'], $sec)) out(['ok' => false, 'error' => 'Could not write the secret file.']);
    security_log('connection_updated', ['service' => 'stripe_webhook']);
    out(['ok' => true]);

  case 'google':
    $key = trim($in['key'] ?? ''); $place = trim($in['place_id'] ?? '');
    if ($key === '' || $place === '') out(['ok' => false, 'error' => 'Both the API key and the Place ID are needed.']);
    [$c, $j] = http('GET', 'https://places.googleapis.com/v1/places/' . rawurlencode($place),
      ['X-Goog-Api-Key: ' . $key, 'X-Goog-FieldMask: displayName,rating,userRatingCount']);
    if ($c !== 200) out(['ok' => false, 'error' => 'Google said: ' . ($j['error']['message'] ?? "HTTP $c")]);
    save_secret($F['gkey'], $key); save_secret($F['gplace'], $place);
    @unlink(__DIR__ . '/reviews_cache.json');
    security_log('connection_updated', ['service' => 'google']);
    out(['ok' => true, 'account' => $j['displayName']['text'] ?? null, 'rating' => $j['rating'] ?? null, 'reviews' => $j['userRatingCount'] ?? null]);

  case 'cleanup':
    $gone = [];
    foreach ($STRAY as $f) if (file_exists($f) && remove_path($f)) $gone[] = basename($f);
    out(['ok' => true, 'deleted' => $gone]);

  default:
    http_response_code(400);
    out(['error' => 'Unknown action']);
}
