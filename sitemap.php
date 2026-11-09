<?php
/**
 * sitemap.php — served as /sitemap.xml (see .htaccess). Built on every request,
 * so new Knowledge Center answers and blog posts show up without anyone
 * remembering to update a file. Submit https://yourenergybest.com/sitemap.xml
 * once in Google Search Console and Bing Webmaster Tools.
 */
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$BASE = 'https://yourenergybest.com';
$urls = [];
$add = function ($path, $file = null, $priority = '0.6') use (&$urls, $BASE) {
  $mod = $file && file_exists(__DIR__ . '/' . $file) ? date('Y-m-d', filemtime(__DIR__ . '/' . $file)) : null;
  $urls[] = ['loc' => $BASE . $path, 'lastmod' => $mod, 'priority' => $priority];
};

// The pages people should land on (not admin, endpoints, or the flyer-only /locals-audit/)
$add('/',                'index.html',                '1.0');
$add('/quote/',          'quote/index.html',          '0.9');
$add('/audit/',          'audit/index.html',          '0.9');
$add('/knowledge/',      'knowledge/index.html',      '0.9');
$add('/services/audit/', 'services/audit/index.html', '0.8');
$add('/services/',       'services/index.html',       '0.7');
$add('/options/',        'options/index.html',        '0.7');
$add('/start/',          'start/index.html',          '0.6');
$add('/blog.html',       'blog.html',                 '0.6');
$add('/build/',          'build/index.html',          '0.3');

// Knowledge Center answers, each on its own page (once knowledge.json exists)
foreach (['knowledge.json', 'knowledge-seed.json'] as $kf) {
  if (!file_exists(__DIR__ . "/$kf")) continue;
  $k = json_decode(file_get_contents(__DIR__ . "/$kf"), true) ?: [];
  foreach ($k['entries'] ?? [] as $e) {
    if (($e['live'] ?? true) === false || empty($e['slug'])) continue;
    $urls[] = ['loc' => "$BASE/knowledge/" . rawurlencode($e['slug']) . '/', 'lastmod' => $e['updated'] ?? null, 'priority' => '0.7'];
  }
  break;
}

// Blog posts
$content = json_decode(@file_get_contents(__DIR__ . '/content.json') ?: '{}', true) ?: [];
foreach ($content['blog_posts'] ?? [] as $p) {
  if (empty($p['id']) || empty($p['title'])) continue;
  $urls[] = ['loc' => "$BASE/blog.html?p=" . rawurlencode(preg_replace('/[^a-z0-9-]/i', '', $p['id'])), 'lastmod' => $p['date'] ?? null, 'priority' => '0.6'];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
  echo '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc>';
  if (!empty($u['lastmod']) && preg_match('/^\d{4}-\d{2}-\d{2}/', $u['lastmod'])) echo '<lastmod>' . substr($u['lastmod'], 0, 10) . '</lastmod>';
  echo '<priority>' . $u['priority'] . "</priority></url>\n";
}
echo "</urlset>\n";
