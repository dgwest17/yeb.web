<?php
/**
 * plans.php — THE price list. Change a price here and nowhere else.
 *
 * - stripe-checkout.php charges from this map (the browser only ever sends a plan key).
 * - /services/ and /services/audit/ load /prices.js, which reads GET /plans.php
 *   and writes these prices into every [data-price="<key>"] on the page.
 *   The numbers typed into the HTML are only a fallback if that request fails.
 *
 * amount is in cents. mode: 'payment' (one-time) or 'subscription' (renews yearly).
 */
$PLANS = [
  // Audit tiers
  'audit'         => ['name' => 'Self-Audit',                           'amount' => 12900, 'mode' => 'payment'],
  'full'          => ['name' => 'Full Professional Audit & Evaluation', 'amount' => 24900, 'mode' => 'payment'],
  'audit-premium' => ['name' => 'Premium Audit & Inspection',           'amount' => 47900, 'mode' => 'payment',      'return' => '/services/audit/'],
  'audit-annual'  => ['name' => 'Annual Audit & Recommendations Plan',  'amount' => 10900, 'mode' => 'subscription', 'return' => '/services/audit/'],
  // Annual service plans
  'basic'         => ['name' => 'Service Plan — Essential',             'amount' => 14900, 'mode' => 'subscription'],
  'plus'          => ['name' => 'Service Plan — Plus',                  'amount' => 26900, 'mode' => 'subscription'],
  'totalcare'     => ['name' => 'Service Plan — Total Care',            'amount' => 46500, 'mode' => 'subscription'],
];

// Requested directly → public price list as JSON. Included → hand the map to the caller.
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
  header('Content-Type: application/json');
  header('Cache-Control: public, max-age=300');
  $out = [];
  foreach ($PLANS as $key => $p) {
    $out[$key] = ['name' => $p['name'], 'price' => $p['amount'] / 100, 'recurring' => $p['mode'] === 'subscription'];
  }
  echo json_encode($out);
  exit;
}

return $PLANS;
