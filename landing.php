<?php
/**
 * landing.php — the four search landing pages: /solar-quote/, /home-battery/,
 * /solar-audit/, /solar-inspection/. Each folder's index.php sets $LANDING and
 * requires this file.
 *
 * What feeds them:
 *   - the copy below (one entry per page in $PAGES)
 *   - cities.php — what's different in each utility territory, for this topic
 *   - Admin → Knowledge — the FAQ section is the listed Knowledge Center answers,
 *     so correcting an answer there corrects every page that shows it
 *   - plans.php — prices (through prices.js)
 * Site look: style.css + audit-book.css + landing.css.
 */
require_once __DIR__ . '/knowledge/_lib.php';
$CITIES = require __DIR__ . '/cities.php';

$PAGES = [
  'solar-quote' => [
    'title' => 'Solar Quote San Diego | Compare Solar & Battery Options',
    'desc'  => 'Get an honest solar or battery quote in San Diego and Orange County, built for 2026: the homeowner tax credit ended, so we compare PPA, prepaid, loan, cash and CEA $0-down side by side.',
    'kicker' => 'Solar & battery quotes · San Diego & Orange County',
    'h1'    => 'A solar quote that\'s honest about 2026.',
    'lede'  => 'The homeowner tax credit ended December 31, 2025, and plenty of quotes haven\'t caught up. Ours start from what actually applies to your home.',
    'points' => [
      ['The 2026 math', 'Buying with cash or a loan no longer gets 30% back. That value now flows through programs where a company owns the system (PPA, prepaid, lease) as a lower price.'],
      ['Every option side by side', 'PPA, prepaid, loan, cash, and CEA\'s $0-down Solar Plus in CEA cities — with the escalators and dealer fees shown, not buried.'],
      ['No pressure', 'Four quick questions. A real person reviews your roof and usage. No credit check, no obligation.'],
    ],
    'cta'  => ['Get my free quote →', '/quote/?option=new'],
    'alt'  => ['Compare your options first', '/options/'],
    'topic' => 'quote',
    'service' => 'Residential solar and battery quotes',
    'faq' => ['wait-homeowners-really-cant-claim-the-federal-tax-credit-anymore', 'prepaid-esa-vs-ppa-vs-cash-the-30-second-version',
              'whats-an-escalator-and-why-do-sales-reps-hide-it', 'what-are-dealer-fees-and-are-they-a-scam', 'how-do-i-know-if-im-getting-a-fair-price-the-ppw-secret',
              'cea-solar-plus-0-down-no-credit-check', 'energy-community-adder-up-to-10-more-off-based-on-your-zip-code', 'what-happens-to-my-solar-agreement-if-i-sell-my-home'],
  ],
  'home-battery' => [
    'title' => 'Home Battery Installation San Diego | Tesla & Enphase Battery',
    'desc'  => 'Home battery installation in San Diego and Orange County: Tesla Powerwall 3, Enphase IQ Battery 10C and FranklinWH. Under net billing, storing your own solar is the strategy — plus backup power and SDCP or CEA programs.',
    'kicker' => 'Home batteries · Tesla · Enphase · FranklinWH',
    'h1'    => 'Under net billing, the battery is the strategy.',
    'lede'  => 'Solar sent to the grid at midday earns little now. A battery keeps that power for the evening, when it\'s worth the most — and keeps your lights on in an outage.',
    'points' => [
      ['Use your own solar at night', 'Store what your panels make at midday and use it from 4 to 9 pm, when SDG&E power costs the most.'],
      ['Backup that fits your home', 'Whole-home or essentials-only — one battery rarely backs up a whole house, and we\'ll say so up front.'],
      ['Programs that change the math', 'SDCP\'s battery rebate, CEA\'s $0-down Solar Plus, and virtual power plant payments, depending on where you live.'],
    ],
    'cta'  => ['Get a battery quote →', '/quote/?option=existing'],
    'alt'  => ['Not sure it pays? Book an audit', '/audit/'],
    'topic' => 'battery',
    'service' => 'Home battery installation (Tesla Powerwall 3, Enphase IQ Battery 10C, FranklinWH)',
    'faq' => ['do-i-actually-need-a-battery-in-california', 'what-does-a-battery-cost-in-2026', 'whats-the-difference-between-net-metering-and-net-billing-nem-3-0', 'will-one-battery-back-up-my-whole-house-the-1-battery-myth',
              'capacity-vs-power-the-highway-analogy-that-makes-batteries-click', 'battery-warranties-the-fine-print-that-actually-matters', 'sdcp-battery-rebate-3-000-to-6-000-per-battery',
              'cea-solar-plus-0-down-no-credit-check', 'sdg-es-secret-1-3-50-kwh-export-window'],
  ],
  'solar-audit' => [
    'title' => 'Solar Audit San Diego | Solar System Performance Analysis',
    'desc'  => 'An independent solar savings audit in San Diego and Orange County: production versus what you were promised, your bills and true-up, rate plan and agreement — from someone who didn\'t sell you the system.',
    'kicker' => 'Solar savings audits · already have solar?',
    'h1'    => 'I\'m not the company that sold you the system. That\'s the point.',
    'lede'  => 'A solar savings audit checks what your system actually makes and saves against what you were promised: production, bills, true-up, rate plan and agreement.',
    'points' => [
      ['Production vs. the promise', 'What your panels make, against what that roof and system size should make.'],
      ['Bills, true-up and rate plan', 'Why you may still be paying SDG&E, and whether a different rate plan fixes part of it.'],
      ['What to do next', 'A straight answer on batteries, repairs or doing nothing — including SDCP and CEA programs you may qualify for.'],
    ],
    'price' => 'savings-audit',
    'cta'  => ['Book a solar audit →', '/audit/'],
    'alt'  => ['Or check it yourself free in lumaria', 'https://www.lumaria.energy/'],
    'topic' => 'audit',
    'service' => 'Independent solar savings audit',
    'faq' => ['what-is-a-solar-health-audit-and-do-i-need-one', 'i-got-a-surprise-true-up-bill-what-happened', 'what-should-i-watch-in-my-monitoring-app-and-when-should-i-worry',
              'what-are-time-of-use-rates-and-why-do-they-matter', 'my-installer-went-out-of-business-am-i-screwed', 'how-much-maintenance-does-solar-actually-need', 'do-i-actually-need-a-battery-in-california'],
  ],
  'solar-inspection' => [
    'title' => 'Solar Inspection San Diego | Independent Solar System Inspection',
    'desc'  => 'An independent on-site solar inspection in San Diego and Orange County: roof penetrations, mounting, wiring, panels, inverters and batteries checked by someone with no workmanship to defend.',
    'kicker' => 'Independent solar inspections',
    'h1'    => 'An inspection from someone with no workmanship to defend.',
    'lede'  => 'If we didn\'t install it, we have no reason to tell you it\'s fine. We check the roof, mounting, wiring, panels, inverters and battery on site, and photograph what we find.',
    'points' => [
      ['Roof & mounting', 'Penetrations, flashing and racking — the places leaks and loose arrays start.'],
      ['Electrical & equipment', 'Wiring, the main panel, inverters and panel-level diagnostics; battery health if you have one.'],
      ['A written report', 'Photos of every finding and a plain list of what\'s urgent, what can wait, and what\'s fine.'],
    ],
    'price' => 'audit-premium',
    'cta'  => ['Book an inspection →', '/services/audit/'],
    'alt'  => ['Just want the savings check? Book an audit', '/audit/'],
    'topic' => 'inspection',
    'service' => 'Independent on-site solar inspection',
    'faq' => ['will-solar-leak-my-roof', 'my-installer-went-out-of-business-am-i-screwed', 'what-should-i-watch-in-my-monitoring-app-and-when-should-i-worry',
              'the-main-panel-upgrade-solars-silent-budget-killer', 'how-much-maintenance-does-solar-actually-need', 'panel-cleaning-diy-or-hire-it-out', 'what-is-a-solar-health-audit-and-do-i-need-one'],
  ],
];

$P = $PAGES[$LANDING ?? ""] ?? null;
if (!$P) { http_response_code(404); exit('Not found'); }
$url = KC_BASE . '/' . $LANDING . '/';
$faq = array_values(array_filter(array_map('kc_find', $P['faq'])));   // only published answers show
$cityNames = [];
foreach ($CITIES as $t) foreach (array_keys($t['cities']) as $c) $cityNames[] = $c;

$schema = [
  ['@context' => 'https://schema.org', '@type' => 'Service', 'name' => $P['service'], 'serviceType' => $P['service'], 'url' => $url,
   'provider' => ['@id' => KC_BASE . '/#business', '@type' => 'LocalBusiness', 'name' => 'Your Energy Best', 'telephone' => '+1-760-454-7868', 'url' => KC_BASE . '/'],
   'areaServed' => array_map(fn($c) => ['@type' => 'Place', 'name' => (stripos($c, 'orange county') !== false ? 'Orange County' : $c) . ', CA'], $cityNames)],
];
if ($faq) $schema[] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn($e) => [
  '@type' => 'Question', 'name' => $e['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => kc_text($e['answer'])]], $faq)];
$priceTag = function ($key, $fallback) { return '<span data-price-of="' . kc_e($key) . '">' . kc_e($fallback) . '</span>'; };
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= kc_e($P['title']) ?></title>
<meta name="description" content="<?= kc_e($P['desc']) ?>">
<link rel="canonical" href="<?= kc_e($url) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Your Energy Best">
<meta property="og:url" content="<?= kc_e($url) ?>">
<meta property="og:title" content="<?= kc_e($P['title']) ?>">
<meta property="og:description" content="<?= kc_e($P['desc']) ?>">
<meta property="og:image" content="https://yourenergybest.com/img/gallery-1770755863-698b9717bd812.jpeg">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" type="image/png" href="/img/logo.png">
<link rel="stylesheet" href="/style.css">
<link rel="stylesheet" href="/audit-book.css">
<link rel="stylesheet" href="/landing.css">
<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<script src="/nav.js?v=20261111" defer></script>
</head>
<body>

<section class="ab-hero">
  <span class="eyebrow"><?= kc_e($P['kicker']) ?></span>
  <h1><?= kc_e($P['h1']) ?></h1>
  <p><?= kc_e($P['lede']) ?></p>
</section>

<main class="ab-wrap lp">
  <div class="ab-card lp-cta">
    <a class="btn btn--primary btn--lg" href="<?= kc_e($P['cta'][1]) ?>"><?= kc_e($P['cta'][0]) ?></a>
    <a class="lp-alt" href="<?= kc_e($P['alt'][1]) ?>"><?= kc_e($P['alt'][0]) ?></a>
<?php if (!empty($P['price'])): ?>
    <p class="fine"><?= $P['price'] === 'savings-audit' ? $priceTag('savings-audit', '$219') . ' · 20–45 minutes with David · fee may be applied toward a battery or new system' : $priceTag('audit-premium', '$479') . ' · on site · includes the full savings audit' ?></p>
<?php else: ?>
    <p class="fine">Free · no credit check · no obligation</p>
<?php endif; ?>
  </div>

  <div class="points">
<?php foreach ($P['points'] as $i => [$h, $t]): ?>
    <div class="point"><span class="n"><?= sprintf('%02d', $i + 1) ?></span><h3><?= kc_e($h) ?></h3><p><?= kc_e($t) ?></p></div>
<?php endforeach; ?>
  </div>

  <section class="ab-section">
    <h2>Where we work</h2>
    <p class="lp-sub">What's different depends on your utility. Find your city:</p>
<?php foreach ($CITIES as $t): ?>
    <div class="note lp-terr">
      <h3><?= kc_e($t['name']) ?></h3>
      <p class="lp-cities"><?= kc_e(implode(' · ', array_map('ucfirst', array_keys($t['cities'])))) ?></p>
      <p><?= kc_e($t['about'][$P['topic']] ?? '') ?></p>
<?php $local = array_filter($t['cities']); if ($local): ?>
      <ul class="lp-local">
<?php foreach ($local as $city => $note): ?>
        <li><strong><?= kc_e($city) ?>:</strong> <?= kc_e($note) ?></li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
    </div>
<?php endforeach; ?>
  </section>

<?php if ($faq): ?>
  <section class="ab-section">
    <h2>Straight answers</h2>
<?php foreach ($faq as $e): ?>
    <details class="ab-card lp-faq">
      <summary><?= kc_e($e['question']) ?></summary>
      <div class="lp-answer"><?= kc_html($e['answer']) ?></div>
      <a class="lp-more" href="<?= kc_e(kc_url($e)) ?>">Read this answer on its own page →</a>
    </details>
<?php endforeach; ?>
    <p class="lp-sub"><a href="/knowledge/">More in the Knowledge Center →</a></p>
  </section>
<?php endif; ?>

  <div class="ab-card lp-cta lp-cta--end">
    <h2><?= kc_e($P['cta'][0] === 'Get my free quote →' ? 'Ready to see your real numbers?' : 'Ready when you are.') ?></h2>
    <a class="btn btn--primary btn--lg" href="<?= kc_e($P['cta'][1]) ?>"><?= kc_e($P['cta'][0]) ?></a>
    <p class="contact">Or call or text David · <a href="tel:+17604547868">(760) 454-7868</a></p>
  </div>
</main>

<footer class="footer">
  <div class="container">
    <div class="footer__grid">
      <div>
        <img src="/img/logo.png" alt="Your Energy Best" class="footer__logo" />
        <p>Solar + battery in San Diego &amp; Orange County.</p>
        <div class="footer__contact"><a href="tel:+17604547868">📞 (760) 454-7868</a><span style="opacity:.8;font-size:.9em;white-space:nowrap;"> · 454-SUN8</span></div>
      </div>
      <div class="footer__col"><h5>Get started</h5><a href="/quote/">Solar &amp; battery quote</a><a href="/audit/">Solar audit</a><a href="https://www.lumaria.energy/">lumaria app</a></div>
      <div class="footer__col"><h5>Learn</h5><a href="/knowledge/">Knowledge Center</a><a href="/solar-quote/">Solar quotes</a><a href="/home-battery/">Home batteries</a><a href="/solar-audit/">Solar audits</a><a href="/solar-inspection/">Solar inspections</a></div>
    </div>
    <div class="footer__bottom"><span>&copy; <?= date('Y') ?> Your Energy Best. All rights reserved.</span></div>
  </div>
</footer>
<script src="/prices.js" defer></script>
</body>
</html>
