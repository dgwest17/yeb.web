<?php
/**
 * cities.php — where we work, by utility territory (David's notes, 2026-11-09).
 * Used by the landing pages (landing.php). Each territory says what's genuinely
 * different there for that page's topic, so the city sections aren't copy-paste
 * (search engines push copy-paste city pages down). Confirm with David before
 * adding a city.
 */
return [
  'sdcp' => [
    'name'  => 'San Diego Community Power (SDCP)',
    'cities' => [
      'San Diego'  => '',
      'Encinitas'  => 'The coastal marine layer can delay morning production, so we size for it.',
      'Ramona'     => 'Real fire risk and outage history: backup power often matters more than savings here.',
    ],
    'about' => [
      'quote'      => 'SDCP customers can pair new solar with SDCP\'s battery rebate while funding lasts.',
      'battery'    => 'SDCP\'s battery rebate applies to new and existing solar. Funding is limited and first come, first served.',
      'audit'      => 'If you already have solar here, the audit checks whether adding a battery could qualify for SDCP\'s rebate.',
      'inspection' => 'Adding a battery later? An inspection first makes sure the system and panel are ready for it.',
    ],
  ],
  'cea' => [
    'name'  => 'Clean Energy Alliance (CEA)',
    'cities' => [
      'Carlsbad'     => '',
      'Oceanside'    => 'Lots of older homes: we check the main electrical panel up front.',
      'Vista'        => '',
      'San Marcos'   => '',
      'Solana Beach' => '',
      'Del Mar'      => 'Salt air: we check mounting and wiring for corrosion.',
      'Escondido'    => 'Hot inland summers: air conditioning shapes system and battery sizing.',
    ],
    'about' => [
      'quote'      => 'CEA Solar Plus: $0 down, no credit check, Tesla equipment, payments on your utility bill.',
      'battery'    => 'CEA Solar Plus can add a battery for $0 down with no credit check, paid on your utility bill.',
      'audit'      => 'Owners here can add a battery through CEA Solar Plus with $0 down — the audit shows whether it pays.',
      'inspection' => 'Coastal and inland homes wear differently; the inspection covers both.',
    ],
  ],
  'sdge-oc' => [
    'name'  => 'SDG&E in South Orange County',
    'cities' => [ 'San Clemente' => '', 'Dana Point' => '', 'San Juan Capistrano' => '' ],
    'about' => [
      'quote'      => 'These cities are SDG&E, not Edison, so SDG&E\'s rates and rules apply.',
      'battery'    => 'SDG&E rates and time-of-use rules apply here (not Edison\'s), which is what makes a battery pay.',
      'audit'      => 'SDG&E bills and true-ups work differently from Edison\'s; the audit reads yours.',
      'inspection' => 'Salt air on the coast: we check mounting and wiring for corrosion.',
    ],
  ],
  'sce' => [
    'name'  => 'Southern California Edison (SCE)',
    'cities' => [ 'Long Beach' => '', 'the rest of Orange County' => '' ],
    'about' => [
      'quote'      => 'Fewer local incentives on Edison; virtual power plant (VPP) programs that pay you for battery support are the main one.',
      'battery'    => 'Edison has fewer battery incentives; virtual power plant (VPP) programs are the main one, and they pay you for letting your battery help the grid.',
      'audit'      => 'Edison rate plans differ from SDG&E\'s; the audit checks you\'re on the right one.',
      'inspection' => 'Same independent inspection, wherever your installer was based.',
    ],
  ],
];
