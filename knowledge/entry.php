<?php
/**
 * /knowledge/<slug>/ — one answer on its own page (rewritten here by .htaccess),
 * so a search for that exact question can land on it. Title, description,
 * FAQ + breadcrumb markup, the next step (quote / audit / lumaria) and related
 * questions all come from Admin → Knowledge.
 */
require __DIR__ . '/_lib.php';

$slug = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['slug'] ?? ''));
$e = $slug !== '' ? kc_find($slug) : null;
if (!$e) {   // renamed or unpublished answer: send people to the full list rather than a dead end
  header('Location: /knowledge/', true, 302);
  exit;
}
$cat = kc_cat($e['cat']);
$url = KC_BASE . kc_url($e);
$desc = kc_summary($e['answer']);
$related = array_slice(array_values(array_filter(kc_live(), fn($x) => $x['cat'] === $e['cat'] && $x['slug'] !== $e['slug'])), 0, 6);
$updated = preg_match('/^\d{4}-\d{2}-\d{2}/', $e['updated'] ?? '') ? substr($e['updated'], 0, 10) : null;

$schema = [
  ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => [[
    '@type' => 'Question', 'name' => $e['question'],
    'acceptedAnswer' => ['@type' => 'Answer', 'text' => kc_text($e['answer'])]]]],
  ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Knowledge Center', 'item' => KC_BASE . '/knowledge/'],
    ['@type' => 'ListItem', 'position' => 2, 'name' => preg_replace('/:.*$/', '', $cat['title']), 'item' => KC_BASE . '/knowledge/#' . $cat['id']],
    ['@type' => 'ListItem', 'position' => 3, 'name' => $e['question'], 'item' => $url]]],
];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= kc_e($e['question']) ?> — Your Energy Best Knowledge Center</title>
<meta name="description" content="<?= kc_e($desc) ?>">
<link rel="canonical" href="<?= kc_e($url) ?>">
<meta property="og:type" content="article">
<meta property="og:site_name" content="Your Energy Best">
<meta property="og:url" content="<?= kc_e($url) ?>">
<meta property="og:title" content="<?= kc_e($e['question']) ?>">
<meta property="og:description" content="<?= kc_e($desc) ?>">
<meta property="og:image" content="https://yourenergybest.com/img/gallery-1770755863-698b9717bd812.jpeg">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" type="image/png" href="/img/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/knowledge/kc.css">
<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<script src="/nav.js?v=20261112" defer></script>
</head>
<body>

<header class="entry-hero">
  <div class="container">
    <p class="crumbs"><a href="/knowledge/">Knowledge Center</a> › <a href="/knowledge/#<?= kc_e($cat['id']) ?>"><?= kc_e(trim($cat['icon'] . ' ' . preg_replace('/:.*$/', '', $cat['title']))) ?></a></p>
    <h1><?= kc_e($e['question']) ?></h1>
    <p class="entry-meta">David West · Your Energy Best, San Diego<?= $updated ? ' · Updated ' . kc_e(date('F j, Y', strtotime($updated))) : '' ?></p>
  </div>
</header>

<main class="container">
  <article class="entry-body">
    <?= kc_html($e['answer']) ?>
  </article>

  <?= kc_next_html($e) ?>

<?php if ($related): ?>
  <section class="related">
    <h2>More on <?= kc_e(preg_replace('/:.*$/', '', $cat['title'])) ?></h2>
<?php foreach ($related as $r): ?>
    <a href="<?= kc_e(kc_url($r)) ?>"><?= kc_e($r['question']) ?> →</a>
<?php endforeach; ?>
  </section>
<?php endif; ?>
  <p style="margin-top:2rem"><a href="/knowledge/" style="color:var(--sky,#8ECAE6)">← Every question, honestly answered</a></p>
</main>

<footer class="page-footer">
  <div class="container">
    <p>© <?= date('Y') ?> Your Energy Best · San Diego, CA · <a href="/">yourenergybest.com</a> · <a href="tel:8587862204">(858) 786-2204</a><span style="opacity:.8;font-size:.9em;white-space:nowrap;"> · 858-SUN-2204</span></p>
    <p class="disclaimer">This knowledge center is educational. Program terms, rebate availability, rates, and regulations change — sometimes quickly. Nothing here is tax, legal, or financial advice; confirm specifics for your home before you decide.</p>
  </div>
</footer>
</body>
</html>
