<?php
/**
 * indexnow.php — tells Bing (and Yandex, Seznam, Naver) about every page in the sitemap, the
 * IndexNow way: no account needed; the key file at the site root proves we own the site.
 * Bing's index is what ChatGPT search and Copilot read, so new pages reach them in minutes.
 *
 * Run it after publishing new pages: Admin can call it, or open it while signed in to admin.
 * Admin session required (so strangers can't spam the endpoint).
 */
require_once __DIR__ . '/security-config.php';
header('Content-Type: application/json');
require_admin_auth();

$keyFiles = array_values(array_filter(glob(__DIR__ . '/*.txt') ?: [], fn() => preg_match('/^[0-9a-f]{32}.txt$/', basename())));
$key = $keyFiles ? basename(reset($keyFiles), '.txt') : '';
if ($key === '') { http_response_code(500); echo json_encode(['ok' => false, 'error' => 'No IndexNow key file at the site root']); exit; }

$ch = curl_init('https://yourenergybest.com/sitemap.xml');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
$xml = curl_exec($ch); curl_close($ch);
preg_match_all('#<loc>([^<]+)</loc>#', (string)$xml, $m);
$urls = array_values(array_unique(array_map('html_entity_decode', $m[1])));
if (!$urls) { http_response_code(502); echo json_encode(['ok' => false, 'error' => 'Could not read the sitemap']); exit; }

$ch = curl_init('https://api.indexnow.org/indexnow');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 20,
  CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
  CURLOPT_POSTFIELDS => json_encode(['host' => 'yourenergybest.com', 'key' => $key,
    'keyLocation' => 'https://yourenergybest.com/' . $key . '.txt', 'urlList' => array_slice($urls, 0, 10000)])]);
$resp = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
echo json_encode(['ok' => $code >= 200 && $code < 300, 'status' => $code, 'submitted' => count($urls)]);
