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
      'Chula Vista' => '', 'Imperial Beach' => '', 'La Mesa' => '', 'National City' => '',
      'Alpine' => '', 'Lakeside' => '', 'Spring Valley' => '', 'Fallbrook' => '', 'Valley Center' => '', 'Jamul' => '', 'Julian' => '',
    ],
    'about' => [
      'quote'      => 'SDCP customers can pair new solar with SDCP\'s battery rebate while funding lasts.',
      'battery'    => 'SDCP\'s battery rebate applies to new and existing solar. Funding is limited and first come, first served.',
      'audit'      => 'If you already have solar here, the audit checks whether adding a battery could qualify for SDCP\'s rebate.',
      'inspection' => 'Adding a battery later? An inspection first makes sure the system and panel are ready for it.',
      'bill'       => 'Your bill can show SDCP generation charges alongside SDG&E delivery charges. Read both before deciding what went wrong.',
      'repair'     => 'Fixing a system anyway? It\'s a good moment to see whether a battery would qualify for SDCP\'s rebate.',
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
      'bill'       => 'CEA supplies the generation and SDG&E delivers it, so solar credits and charges from both appear on your account.',
      'repair'     => 'Repairing or replacing equipment? CEA Solar Plus can add a battery at the same time, $0 down.',
    ],
  ],
  'sdge' => [
    'name'  => 'SDG&E (no community power program yet)',
    'cities' => [ 'El Cajon' => '', 'Santee' => '', 'Poway' => '', 'Lemon Grove' => '', 'Coronado' => 'Joining San Diego Community Power, with service expected around 2028.' ],
    'about' => [
      'quote'      => 'SDG&E supplies and delivers your power here, so SDG&E’s own rates and time-of-use periods decide what solar saves.',
      'battery'    => 'SDG&E’s 4–9 pm peak is where a battery earns its keep; virtual power plant programs can pay you for letting it help the grid.',
      'audit'      => 'On SDG&E’s own generation, the rate plan you’re on matters a lot; the audit checks it.',
      'inspection' => 'Inland heat is hard on inverters and wiring; the inspection checks both.',
      'bill'       => 'Your bill is all SDG&E: generation and delivery, plus the Base Services Charge.',
      'repair'     => 'Hot inland summers shorten inverter life; we check the inverter first.',
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
      'bill'       => 'These cities are on SDG&E, so the 4–9 pm peak and SDG&E\'s Base Services Charge apply.',
      'repair'     => 'Coastal salt air is a common cause of corroded connectors and failing inverters; we check for it first.',
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
      'bill'       => 'The same causes apply on Edison, but its peak hours, plans and charges differ from the SDG&E figures above.',
      'repair'     => 'Same troubleshooting on Edison systems, wherever your installer was based.',
    ],
  ],
];
