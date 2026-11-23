<?php
/**
 * go.php — /go/<code> (see .htaccess). The address our own QR codes print, so the
 * destination can change any time without reprinting, and scans are counted here
 * instead of by a monthly-fee QR service (Flowcode was cancelled 2026-11-23).
 *
 * Codes live in .qr_codes.json (Admin → QR Codes, via qr.php); daily scan counts in
 * .qr_scans.json. Both are dotfiles, which .htaccess blocks from the web.
 *
 * - Unknown or archived code → the homepage, so a printed code never dead-ends.
 * - Our own pages get utm_source=qr&utm_medium=print&utm_campaign=<code> (unless the
 *   destination already has a utm_source), so nav.js carries the source into Zoho.
 * - Link previews and crawlers are sent on but not counted.
 */
const QR_CODES = __DIR__ . '/.qr_codes.json';
const QR_SCANS = __DIR__ . '/.qr_scans.json';
const SITE = 'https://yourenergybest.com';

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$slug = strtolower(preg_replace('/[^a-z0-9-]/i', '', $_GET['c'] ?? ''));
$codes = json_decode(@file_get_contents(QR_CODES) ?: '{}', true)['codes'] ?? [];
$code = $codes[$slug] ?? null;

if (!$code || !empty($code['archived']) || empty($code['dest'])) {
  header('Location: ' . SITE . '/', true, 302);
  exit;
}

$dest = $code['dest'];
if ($dest[0] === '/') $dest = SITE . $dest;
$host = strtolower(parse_url($dest, PHP_URL_HOST) ?: '');
if (in_array($host, ['yourenergybest.com', 'www.yourenergybest.com'], true) && stripos($dest, 'utm_source=') === false) {
  [$base, $frag] = array_pad(explode('#', $dest, 2), 2, null);
  $base .= (strpos($base, '?') === false ? '?' : '&') . http_build_query(['utm_source' => 'qr', 'utm_medium' => 'print', 'utm_campaign' => $slug]);
  $dest = $base . ($frag !== null ? '#' . $frag : '');
}

// Count people, not previews: skip HEAD requests and anything that calls itself a bot.
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isBot = $ua === '' || preg_match('/bot|crawl|spider|preview|slurp|facebookexternalhit|whatsapp|curl|wget|python|headless/i', $ua);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !$isBot) {
  $fh = @fopen(QR_SCANS, 'c+');
  if ($fh && flock($fh, LOCK_EX)) {
    $scans = json_decode(stream_get_contents($fh) ?: '{}', true) ?: [];
    $day = (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->format('Y-m-d');
    $scans[$slug][$day] = ($scans[$slug][$day] ?? 0) + 1;
    ftruncate($fh, 0); rewind($fh);
    fwrite($fh, json_encode($scans));
    fflush($fh); flock($fh, LOCK_UN);
  }
  if ($fh) fclose($fh);
}

header('Location: ' . $dest, true, 302);
