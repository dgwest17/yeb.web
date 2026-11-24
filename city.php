<?php
/**
 * city.php — /solar/ (where we work) and /solar/<city>/ (see .htaccess). Builds one page
 * description from solar-cities.php and hands it to landing.php, so city pages share the
 * look, schema, breadcrumbs and FAQ of every other landing page.
 *
 * What each provider means is written once below (PROVIDERS); each city adds its own local
 * angle (FEEL + David's note), so no two pages say the same thing.
 */
$CITY_LIST = require __DIR__ . '/solar-cities.php';

const PROVIDERS = [
  'sdcp' => ['short' => 'San Diego Community Power', 'abbr' => 'SDCP',
    'power' => 'SDG&E delivers your power and San Diego Community Power (SDCP) supplies it',
    'program' => ['SDCP’s battery rebate', 'SDCP customers can be paid an upfront rebate and ongoing performance incentives to add a solar-charged battery, including to solar they already have.', '/sdcp-battery-rebate/'],
    'lede' => 'SDCP’s battery rebate makes adding storage here worth a serious look.'],
  'cea' => ['short' => 'Clean Energy Alliance', 'abbr' => 'CEA',
    'power' => 'SDG&E delivers your power and Clean Energy Alliance (CEA) supplies it',
    'program' => ['CEA Solar Plus', 'CEA’s Solar Plus program offers solar and batteries with $0 down, no credit check and payments on your utility bill.', '/options/'],
    'lede' => 'CEA Solar Plus puts $0-down solar and batteries on your utility bill.'],
  'sdge' => ['short' => 'SDG&E', 'abbr' => 'SDG&E',
    'power' => 'SDG&E both supplies and delivers your power',
    'program' => ['SDG&E time-of-use rates', 'Without a community power program, SDG&E’s own rates apply. The 4–9 pm peak is where a battery or a better rate plan saves the most.', '/solar-billing-plan-explained/'],
    'lede' => 'SDG&E’s 4–9 pm peak decides most of what solar and a battery save you here.'],
  'sdge-oc' => ['short' => 'SDG&E', 'abbr' => 'SDG&E',
    'power' => 'SDG&E supplies and delivers your power (not Edison, despite being in Orange County)',
    'program' => ['SDG&E, not Edison', 'These cities are on SDG&E’s rates and rules, including the 4–9 pm peak, so quotes built for Edison customers don’t apply.', '/solar-billing-plan-explained/'],
    'lede' => 'You’re in Orange County but on SDG&E, so SDG&E’s rates and programs apply.'],
];
const FEEL = [
  'coastal' => ['Coastal conditions', 'Morning marine layer trims early production, and salt air is hard on connectors and mounting. We check for corrosion and size for the cloudier mornings.'],
  'inland'  => ['Inland heat', 'Hot summers push air conditioning into the 4–9 pm peak. That makes battery sizing and the right rate plan matter more than on the coast.'],
  'backcountry' => ['Fire risk and outages', 'Wildfire risk and power shutoffs make backup power a real need here, not a nice-to-have. We size batteries for the circuits you can’t lose.'],
  'older'   => ['Older homes', 'Many homes here have original electrical panels. We check the main panel up front, so a needed upgrade is in the quote, not a surprise later.'],
];

$slug = strtolower(preg_replace('/[^a-z-]/i', '', $_GET['c'] ?? ''));

if ($slug === '') {
  // ── /solar/: every city, grouped by provider ──
  $related = [];
  foreach ($CITY_LIST as $s => $c) $related[] = [$c['name'], '/solar/' . $s . '/', PROVIDERS[$c['p']]['short']];
  $EXTRA_PAGE = [
    'title' => 'Solar & Batteries Across San Diego County | Cities We Serve',
    'desc'  => 'Solar, batteries, free solar audits and repair help across San Diego County and South Orange County, city by city: which power provider you have and what programs apply.',
    'kicker' => 'Where we work',
    'h1'    => 'Solar help in every corner of San Diego County.',
    'lede'  => 'Your city decides your power provider, and your provider decides which programs and rates apply. Find yours.',
    'answer' => 'In San Diego County, SDG&E delivers everyone’s power, but who supplies it depends on your city: San Diego Community Power (SDCP), Clean Energy Alliance (CEA), or SDG&E itself. That choice decides which programs apply, such as SDCP’s battery rebate or CEA’s $0-down Solar Plus. A few South Orange County cities are on SDG&E too, not Edison.',
    'points' => [
      ['SDCP cities', 'San Diego, Chula Vista, Encinitas, Imperial Beach, La Mesa, National City and unincorporated areas.'],
      ['CEA cities', 'Carlsbad, Oceanside, Vista, San Marcos, Solana Beach, Del Mar and Escondido.'],
      ['SDG&E only', 'El Cajon, Santee, Poway, Lemon Grove and Coronado, plus San Clemente, Dana Point and San Juan Capistrano.'],
    ],
    'related_title' => 'Find your city', 'related' => $related, 'itemlist' => true,
    'checked' => 'November 24, 2026',
    'sources' => [['San Diego Community Power', 'https://sdcommunitypower.org/'], ['Clean Energy Alliance', 'https://thecleanenergyalliance.org/']],
    'price' => 'savings-audit', 'cta' => ['Book a free solar audit →', '/audit/'], 'alt' => ['New to solar? Get a quote', '/quote/'],
    'topic' => 'quote', 'service' => 'Solar, batteries and solar audits in San Diego County',
    'faq' => ['whats-a-cca-and-what-are-sdcp-and-cea', 'do-i-actually-need-a-battery-in-california', 'what-is-a-solar-health-audit-and-do-i-need-one'],
  ];
  $LANDING = 'solar';
  require __DIR__ . '/landing.php';
  exit;
}

$c = $CITY_LIST[$slug] ?? null;
if (!$c) { header('Location: /solar/', true, 302); exit; }
$pv = PROVIDERS[$c['p']];
[$feelTitle, $feelText] = FEEL[$c['feel']];
$name = $c['name'];
$local = !empty($c['note']) ? $c['note'] : $feelText;
$neighbors = [];
foreach ($CITY_LIST as $s => $o) if ($s !== $slug && $o['p'] === $c['p']) $neighbors[] = [$o['name'], '/solar/' . $s . '/', PROVIDERS[$o['p']]['short']];

$EXTRA_PAGE = [
  'title' => "Solar & Batteries in $name, CA | Free Solar Audit | Your Energy Best",
  'desc'  => "Solar and home batteries in $name: " . lcfirst($pv['power']) . ". What that means for your rates and programs, plus free solar audits and repair help for $name homes.",
  'kicker' => "$name · " . $pv['short'],
  'h1'    => "Solar and batteries in $name",
  'lede'  => $pv['lede'],
  'answer' => "In $name, " . $pv['power'] . '. ' . $pv['program'][1] . ' ' . ($c['uninc'] ?? false ? 'Some households opted out and stay with SDG&E, so check the generation provider on your bill. ' : '') . $local,
  'points' => [
    [$pv['program'][0], $pv['program'][1]],
    [$feelTitle, $local],
    ['Already have solar?', 'Our solar audit is free through December 31: production, bills, rate plan and whether a battery pays, for your home.'],
  ],
  'sections' => [
    ["What applies in $name", [
      ['Your provider', ucfirst($pv['power']) . '.'],
      ['The 4–9 pm peak', 'SDG&E’s most expensive hours are 4–9 pm. Solar alone covers the daytime; a battery covers the evening.'],
      ['Your NEM status', 'Systems connected before April 2023 usually keep net metering for 20 years. Newer systems are on the Solar Billing Plan.'],
    ]],
  ],
  'related_title' => 'Nearby cities', 'related' => array_slice($neighbors, 0, 8),
  'crumb' => ['Where we work', '/solar/'],
  'area' => [$name],
  'checked' => 'November 24, 2026',
  'price' => 'savings-audit', 'cta' => ["Book a free audit in $name →", '/audit/'], 'alt' => ["New to solar in $name? Get a quote", '/quote/'],
  'topic' => 'quote',
  'service' => "Solar, batteries and solar audits in $name, CA",
  'faq' => ['whats-a-cca-and-what-are-sdcp-and-cea', 'do-i-actually-need-a-battery-in-california', 'what-is-a-solar-health-audit-and-do-i-need-one', 'what-are-time-of-use-rates-and-why-do-they-matter'],
];
$LANDING = 'solar/' . $slug;
require __DIR__ . '/landing.php';
