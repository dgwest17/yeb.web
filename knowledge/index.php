<?php
/**
 * /knowledge/ — built from Admin → Knowledge (knowledge.json; knowledge-seed.json
 * until the first save). Design, search and chips are the original page's;
 * styles live in /knowledge/kc.css, shared with each answer's page (entry.php).
 */
require __DIR__ . '/_lib.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Solar Knowledge Center — Your Energy Best</title>
<link rel="canonical" href="https://yourenergybest.com/knowledge/">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Your Energy Best">
<meta property="og:url" content="https://yourenergybest.com/knowledge/">
<meta property="og:title" content="Solar Knowledge Center — Your Energy Best">
<meta property="og:description" content="Every question about solar, batteries, financing, rebates, repairs, and the grid — answered honestly by a San Diego solar expert. No fluff, no sales pitch.">
<meta property="og:image" content="https://yourenergybest.com/img/gallery-1770755863-698b9717bd812.jpeg">
<meta name="twitter:card" content="summary_large_image">
<meta name="description" content="Every question about solar, batteries, financing, rebates, repairs, and the grid — answered honestly by a San Diego solar expert. No fluff, no sales pitch.">
<link rel="icon" type="image/png" href="/img/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<?php
$faq = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn($e) => [
  '@type' => 'Question', 'name' => $e['question'], 'url' => KC_BASE . kc_url($e),
  'acceptedAnswer' => ['@type' => 'Answer', 'text' => kc_text($e['answer'])]], kc_live())];
echo '<script type="application/ld+json">' . json_encode($faq, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>';
?>
<link rel="stylesheet" href="/knowledge/kc.css">
  <script src="/nav.js" defer></script>
</head>
<body>

<!-- ═══ YEB HEADER ═══ -->
<!-- ═══ HERO + SEARCH ═══ -->
<header class="hero">
    <div class="hero-bg"></div>
    <div class="container hero-inner">
        <p class="hero-kicker">Your Energy Best · Knowledge Center</p>
        <h1>Every Solar Question.<br><em>Honestly Answered.</em></h1>
        <p class="hero-sub">Financing, rebates, batteries, repairs, the grid, recycling — everything I've learned since 2020, organized so you can find it in seconds.</p>
        <div class="search-wrap">
            <div class="search-bar">
                <span class="s-icon">🔍</span>
                <input type="text" id="kcSearch" placeholder='Try "battery rebate", "escalator", "roof leak", "recycling"…' autocomplete="off">
                <button class="s-clear" id="searchClear">✕</button>
            </div>
            <p class="search-hint"><span class="pulse"></span>Smart search today — a live AI energy expert coming soon</p>
            <p class="search-count" id="searchCount"></p>
        </div>
    </div>
    <div class="wave-divider"><svg viewBox="0 0 1440 60" preserveAspectRatio="none"><path d="M0,30 C240,55 480,5 720,30 C960,55 1200,10 1440,30 L1440,60 L0,60Z" fill="#02304755"/><path d="M0,40 C280,60 560,18 840,40 C1080,55 1280,22 1440,40 L1440,60 L0,60Z" fill="#011627"/></svg></div>
</header>

<!-- ═══ CATEGORY CHIPS ═══ -->
<div class="cat-chips" id="catChips">
<?php foreach (kc_data()['cats'] as $c): if (!array_filter(kc_live(), fn($e) => $e['cat'] === $c['id'])) continue; ?>
    <a href="#<?= kc_e($c['id']) ?>" class="chip"><?= kc_e(trim($c['icon'] . ' ' . preg_replace('/:.*$/', '', $c['title']))) ?></a>
<?php endforeach; ?>
</div>

<main class="container">

<!-- ═══ NO RESULTS ═══ -->
<div class="no-results" id="noResults">
    <div class="nr-icon">🌊</div>
    <h3>Nothing surfaced for that one</h3>
    <p>That's a great question though — and exactly the kind I answer personally. Ask me directly and I'll get you a straight answer (and probably add it here for the next person).</p>
    <a href="/quote/" class="btn-gold" style="font-size:.9rem;padding:.8rem 1.9rem">Ask Dave Directly →</a>
</div>

<!-- ═══════════ 1. START HERE ═══════════ -->
<?php foreach (kc_data()['cats'] as $c):
  $list = array_values(array_filter(kc_live(), fn($e) => $e['cat'] === $c['id']));
  if (!$list) continue; ?>
<section class="kc-section" id="<?= kc_e($c['id']) ?>">
    <div class="kc-head"><span class="kc-icon"><?= kc_e($c['icon']) ?></span><h2><?= kc_e($c['title']) ?></h2></div>
<?php if (!empty($c['intro'])): ?>    <p class="kc-desc"><?= kc_e($c['intro']) ?></p>
<?php endif; foreach ($list as $e): ?>
    <div class="acc" data-tags="<?= kc_e($e['tags'] ?? '') ?>">
        <button class="acc-q"><?= kc_e($e['question']) ?><span class="a-icon">+</span></button>
        <div class="acc-a">
            <?= kc_html($e['answer']) ?>
            <a class="acc-more" href="<?= kc_e(kc_url($e)) ?>">Open this answer on its own page →</a>
        </div>
    </div>
<?php endforeach; ?>
</section>
<?php endforeach; ?>

<!-- ═══ AUDIT CTA ═══ -->
<div class="audit-cta">
    <div class="ac-icon">🔍</div>
    <h3>Not sure your solar is pulling its weight?</h3>
    <p>Whether your installer vanished, your true-up surprised you, or you just want proof your system is performing — my Solar Health Audit gives you the real picture. Production, savings, agreement terms, missed rebates. No guesswork, no pitch.</p>
    <a href="/services/audit/" class="btn-gold">Get My System Audited →</a>
</div>

</main>

<footer class="page-footer">
    <div class="container">
        <p>© 2026 Your Energy Best · San Diego, CA · <a href="/">yourenergybest.com</a> · <a href="tel:7604547868">(760) 454-7868</a></p>
        <p class="disclaimer">This knowledge center is educational. Program terms, rebate availability, rates, and regulations change — sometimes quickly. Nothing here is tax, legal, or financial advice; confirm current terms in writing with any provider and consult the relevant professionals about your specific situation. Content current as of mid-2026.</p>
    </div>
</footer>

<script>
// ═══ ACCORDIONS ═══
document.querySelectorAll('.acc-q').forEach(btn => {
    btn.addEventListener('click', () => btn.parentElement.classList.toggle('open'));
});

// ═══ SEARCH ═══
const searchInput = document.getElementById('kcSearch');
const searchClear = document.getElementById('searchClear');
const searchCount = document.getElementById('searchCount');
const noResults = document.getElementById('noResults');
const allAccs = Array.from(document.querySelectorAll('.acc'));
const allSections = Array.from(document.querySelectorAll('.kc-section'));
const catChips = document.getElementById('catChips');

// Build searchable text once
allAccs.forEach(acc => {
    acc._search = (
        acc.querySelector('.acc-q').textContent + ' ' +
        acc.querySelector('.acc-a').textContent + ' ' +
        (acc.dataset.tags || '')
    ).toLowerCase();
});

function runSearch() {
    const q = searchInput.value.trim().toLowerCase();
    searchClear.classList.toggle('show', q.length > 0);

    if (!q) {
        allAccs.forEach(a => { a.classList.remove('hidden'); a.classList.remove('open'); });
        allSections.forEach(s => s.style.display = '');
        document.querySelectorAll('.feature-card,.daves-take,.audit-cta').forEach(el => el.style.display = '');
        searchCount.classList.remove('show');
        noResults.classList.remove('show');
        return;
    }

    const terms = q.split(/\s+/);
    let total = 0;

    allAccs.forEach(acc => {
        const match = terms.every(t => acc._search.includes(t));
        acc.classList.toggle('hidden', !match);
        acc.classList.toggle('open', match);   // auto-expand matches
        if (match) total++;
    });

    // Hide sections with no visible items
    allSections.forEach(sec => {
        const visible = sec.querySelectorAll('.acc:not(.hidden)').length;
        sec.style.display = visible ? '' : 'none';
    });
    // Hide extras while searching
    document.querySelectorAll('.feature-card,.audit-cta').forEach(el => el.style.display = total ? '' : 'none');

    searchCount.textContent = total ? total + ' answer' + (total===1?'':'s') + ' found' : '';
    searchCount.classList.toggle('show', total > 0);
    noResults.classList.toggle('show', total === 0);
}

searchInput.addEventListener('input', runSearch);

// Category chips: clear any active search so the target section is visible, then jump
catChips.querySelectorAll('.chip').forEach(chip => {
    chip.addEventListener('click', () => {
        if (searchInput.value) { searchInput.value = ''; runSearch(); }
    });
});
searchClear.addEventListener('click', () => { searchInput.value=''; runSearch(); searchInput.focus(); });

// Category chips clear any active search, then jump
document.querySelectorAll('#catChips .chip').forEach(chip => {
    chip.addEventListener('click', () => {
        if (searchInput.value) { searchInput.value = ''; runSearch(); }
    });
});

// Focus search with "/" key
document.addEventListener('keydown', e => {
    if (e.key === '/' && document.activeElement !== searchInput) { e.preventDefault(); searchInput.focus(); }
});
</script>
</body>
</html>
