<?php
/**
 * solar-cities.php — the city pages (/solar/<slug>/, built by city.php through landing.php).
 *
 * Provider checked 2026-11-24:
 *   SDCP  San Diego, Chula Vista, Encinitas, Imperial Beach, La Mesa, National City and the
 *         unincorporated county (Ramona, Alpine, Lakeside, Spring Valley, Fallbrook, Valley Center…)
 *   CEA   Carlsbad, Del Mar, Solana Beach, Escondido, San Marcos, Oceanside, Vista
 *   SDG&E El Cajon, Santee, Poway, Lemon Grove; Coronado until it joins SDCP (service ~2028)
 *   SDG&E in South Orange County: San Clemente, Dana Point, San Juan Capistrano
 * Re-check when a city joins or leaves a community power program.
 *
 * 'feel' picks the local angle (coastal | inland | backcountry | older); 'note' is David's own
 * local note where he has one. Each page must say something true and specific about its city.
 */
return [
  // ── San Diego Community Power ──
  'san-diego'      => ['name' => 'San Diego', 'p' => 'sdcp', 'feel' => 'older', 'note' => 'From coastal neighborhoods to hot inland ones, the right size depends on where in the city you are. Older homes in mid-city often need a main panel check first.'],
  'chula-vista'    => ['name' => 'Chula Vista', 'p' => 'sdcp', 'feel' => 'inland', 'note' => 'Newer eastern neighborhoods often have roofs and panels ready for solar; summer heat pushes evening air conditioning into the 4–9 pm peak.'],
  'encinitas'      => ['name' => 'Encinitas', 'p' => 'sdcp', 'feel' => 'coastal', 'note' => 'The coastal marine layer can delay morning production, so we size for it.'],
  'imperial-beach' => ['name' => 'Imperial Beach', 'p' => 'sdcp', 'feel' => 'coastal'],
  'la-mesa'        => ['name' => 'La Mesa', 'p' => 'sdcp', 'feel' => 'older'],
  'national-city'  => ['name' => 'National City', 'p' => 'sdcp', 'feel' => 'older'],
  'ramona'         => ['name' => 'Ramona', 'p' => 'sdcp', 'feel' => 'backcountry', 'uninc' => true, 'note' => 'Real fire risk and outage history: backup power often matters more than savings here.'],
  'alpine'         => ['name' => 'Alpine', 'p' => 'sdcp', 'feel' => 'backcountry', 'uninc' => true],
  'lakeside'       => ['name' => 'Lakeside', 'p' => 'sdcp', 'feel' => 'inland', 'uninc' => true],
  'spring-valley'  => ['name' => 'Spring Valley', 'p' => 'sdcp', 'feel' => 'inland', 'uninc' => true],
  'fallbrook'      => ['name' => 'Fallbrook', 'p' => 'sdcp', 'feel' => 'backcountry', 'uninc' => true],
  'valley-center'  => ['name' => 'Valley Center', 'p' => 'sdcp', 'feel' => 'backcountry', 'uninc' => true],
  'jamul'          => ['name' => 'Jamul', 'p' => 'sdcp', 'feel' => 'backcountry', 'uninc' => true],
  'julian'         => ['name' => 'Julian', 'p' => 'sdcp', 'feel' => 'backcountry', 'uninc' => true],
  // ── Clean Energy Alliance ──
  'carlsbad'       => ['name' => 'Carlsbad', 'p' => 'cea', 'feel' => 'coastal'],
  'oceanside'      => ['name' => 'Oceanside', 'p' => 'cea', 'feel' => 'older', 'note' => 'Lots of older homes: we check the main electrical panel up front.'],
  'vista'          => ['name' => 'Vista', 'p' => 'cea', 'feel' => 'inland'],
  'san-marcos'     => ['name' => 'San Marcos', 'p' => 'cea', 'feel' => 'inland'],
  'solana-beach'   => ['name' => 'Solana Beach', 'p' => 'cea', 'feel' => 'coastal'],
  'del-mar'        => ['name' => 'Del Mar', 'p' => 'cea', 'feel' => 'coastal', 'note' => 'Salt air: we check mounting and wiring for corrosion.'],
  'escondido'      => ['name' => 'Escondido', 'p' => 'cea', 'feel' => 'inland', 'note' => 'Hot inland summers: air conditioning shapes system and battery sizing.'],
  // ── SDG&E (no community power program yet) ──
  'el-cajon'       => ['name' => 'El Cajon', 'p' => 'sdge', 'feel' => 'inland'],
  'santee'         => ['name' => 'Santee', 'p' => 'sdge', 'feel' => 'inland'],
  'poway'          => ['name' => 'Poway', 'p' => 'sdge', 'feel' => 'inland'],
  'lemon-grove'    => ['name' => 'Lemon Grove', 'p' => 'sdge', 'feel' => 'older'],
  'coronado'       => ['name' => 'Coronado', 'p' => 'sdge', 'feel' => 'coastal', 'note' => 'Coronado has voted to join San Diego Community Power, with service expected around 2028. Until then, SDG&E supplies your power.'],
  // ── SDG&E in South Orange County ──
  'san-clemente'   => ['name' => 'San Clemente', 'p' => 'sdge-oc', 'feel' => 'coastal'],
  'dana-point'     => ['name' => 'Dana Point', 'p' => 'sdge-oc', 'feel' => 'coastal'],
  'san-juan-capistrano' => ['name' => 'San Juan Capistrano', 'p' => 'sdge-oc', 'feel' => 'inland'],
];
