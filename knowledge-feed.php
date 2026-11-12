<?php
/**
 * knowledge-feed.php — everything Luma (the lumaria assistant) should know from
 * the website, live, as JSON. Lumaria's api/_faro reads it (cached ~10 min), so
 * an answer edited in Admin → Knowledge, a blog post or newsletter, or a price
 * change in plans.php reaches Luma without a redeploy.
 *
 *   knowledge  every live answer with "Teach Luma" on (plain text)
 *   posts      blog posts / newsletters, newest first (plain text)
 *   offers     what customers can do next, with current prices
 *   business   name, phone, email, area served
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
header('Access-Control-Allow-Origin: *');
require __DIR__ . '/knowledge/_lib.php';

$entries = array_values(array_filter(kc_live(), fn($e) => ($e['luma'] ?? true) !== false));
$knowledge = array_map(fn($e) => [
  'topic'    => preg_replace('/:.*$/', '', kc_cat($e['cat'])['title']),
  'question' => $e['question'],
  'answer'   => kc_text($e['answer']),
  'for'      => $e['audience'] ?? 'all',
  'url'      => KC_BASE . kc_url($e),
  'updated'  => $e['updated'] ?? null,
], $entries);

$content = json_decode(@file_get_contents(__DIR__ . '/content.json') ?: '{}', true) ?: [];
$posts = [];
foreach ($content['blog_posts'] ?? [] as $p) {
  $title = kc_text($p['title'] ?? '');
  if ($title === '') continue;
  $posts[] = [
    'title' => $title, 'kind' => $p['kind'] ?? 'post', 'date' => $p['date'] ?? '', 'tag' => kc_text($p['tag'] ?? ''),
    'text'  => kc_text($p['body'] ?? ''),
    'url'   => KC_BASE . '/blog.html?p=' . rawurlencode(preg_replace('/[^a-z0-9-]/i', '', $p['id'] ?? '')),
  ];
}
usort($posts, fn($a, $b) => strcmp($b['date'], $a['date']));

$plans = require __DIR__ . '/plans.php';
$price = fn($k) => isset($plans[$k]) && ($plans[$k]['launched'] ?? true) !== false ? $plans[$k]['amount'] / 100 : null;
$offers = array_values(array_filter([
  ['name' => 'Free solar and battery quote', 'price' => 0, 'url' => KC_BASE . '/quote/', 'for' => 'homeowners without solar, or adding a battery'],
  ['name' => 'Solar Savings Audit (20–45 min with David)', 'price' => $price('savings-audit'), 'url' => KC_BASE . '/audit/', 'for' => 'homeowners who already have solar'],
  ['name' => 'Premium Audit & Inspection (on-site)', 'price' => $price('audit-premium'), 'url' => KC_BASE . '/services/audit/', 'for' => 'a full physical inspection'],
  ['name' => 'Self-Audit', 'price' => 0, 'url' => 'https://www.lumaria.energy/', 'for' => 'checking your own system in the lumaria app'],
], fn($o) => $o['price'] !== null));

echo json_encode([
  'updated'  => date('c', max(@filemtime(__DIR__ . '/knowledge.json') ?: 0, @filemtime(__DIR__ . '/knowledge-seed.json') ?: 0, @filemtime(__DIR__ . '/content.json') ?: 0, @filemtime(__DIR__ . '/plans.php') ?: 0)),
  'business' => ['name' => 'Your Energy Best', 'phone' => '(858) 786-2204', 'email' => 'dwest@yourenergybest.com',
                 'area' => 'San Diego County, Orange County and Long Beach (SDG&E reaches San Clemente, Dana Point and San Juan Capistrano; the rest of OC and Long Beach are Edison)', 'site' => KC_BASE . '/'],
  'offers'    => $offers,
  'knowledge' => $knowledge,
  'posts'     => $posts,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
