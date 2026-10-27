<?php
/**
 * backups.php — Admin → History. save.php keeps the last 10 versions of each
 * content file in /backups (blocked from the web by .htaccess). This lists them
 * and puts one back.
 *
 *   GET                                   → { content: [{ file, at, bytes }], services: [...], options: [...] }
 *   POST { action:'restore', file }       → current version is backed up first, then replaced
 *   GET  ?file=<name>                     → that backup's JSON (to preview or download)
 */
require_once __DIR__ . '/security-config.php';
header('Content-Type: application/json');
require_admin_auth();

$DIR = __DIR__ . '/backups';
$TARGETS = ['content' => 'content.json', 'services' => 'services-content.json', 'options' => 'options-content.json'];

/** "content-2026-10-27-142233.json" → ['content', unix time], or null if it isn't one of ours. */
function parse_backup($name) {
  global $TARGETS;
  if (!preg_match('/^(content|services|options)-(\d{4})-(\d{2})-(\d{2})-(\d{2})(\d{2})(\d{2})\.json$/', $name, $m)) return null;
  if (!isset($TARGETS[$m[1]])) return null;
  return [$m[1], mktime((int)$m[5], (int)$m[6], (int)$m[7], (int)$m[3], (int)$m[4], (int)$m[2])];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  if (isset($_GET['file'])) {
    $name = basename($_GET['file']);
    if (!parse_backup($name) || !is_file("$DIR/$name")) { http_response_code(404); echo json_encode(['error' => 'No such backup']); exit; }
    readfile("$DIR/$name");
    exit;
  }
  $out = ['content' => [], 'services' => [], 'options' => []];
  foreach (is_dir($DIR) ? scandir($DIR) : [] as $name) {
    $p = parse_backup($name);
    if ($p) $out[$p[0]][] = ['file' => $name, 'at' => date('c', $p[1]), 'bytes' => filesize("$DIR/$name")];
  }
  foreach ($out as &$list) usort($list, fn($a, $b) => strcmp($b['at'], $a['at']));
  echo json_encode($out);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'GET or POST']); exit; }
validate_csrf();
$in = json_decode(file_get_contents('php://input'), true) ?: [];
if (($in['action'] ?? '') !== 'restore') { http_response_code(400); echo json_encode(['error' => 'Unknown action']); exit; }

$name = basename($in['file'] ?? '');
$p = parse_backup($name);
if (!$p || !is_file("$DIR/$name")) { http_response_code(404); echo json_encode(['error' => 'No such backup']); exit; }
$json = file_get_contents("$DIR/$name");
if (json_decode($json) === null) { http_response_code(422); echo json_encode(['error' => 'That backup is not valid JSON']); exit; }

$live = __DIR__ . '/' . $TARGETS[$p[0]];
// Keep what's live now, so a restore can itself be undone
if (is_file($live)) copy($live, "$DIR/{$p[0]}-" . date('Y-m-d-His') . '.json');
if (file_put_contents($live, $json, LOCK_EX) === false) { http_response_code(500); echo json_encode(['error' => 'Could not write the file']); exit; }
security_log('content_restored', ['file' => $name]);
echo json_encode(['ok' => true, 'restored' => $p[0], 'from' => date('c', $p[1])]);
