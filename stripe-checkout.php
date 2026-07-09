<?php
/**
 * stripe-checkout.php — Creates a Stripe Checkout Session (plain cURL, no SDK/composer needed)
 * Deploy to public_html/. Requires STRIPE_SECRET_KEY defined in config.php (gitignored).
 *
 * POST JSON: { plan, planName, price, opp, name, email, phone, zip }
 * Returns:   { url: "https://checkout.stripe.com/..." }
 */
header('Content-Type: application/json');
require_once __DIR__ . '/config.php'; // must define STRIPE_SECRET_KEY and AUDIT_ACCESS_SECRET

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { http_response_code(400); echo json_encode(['error' => 'Invalid request']); exit; }

// Server-side price map — NEVER trust a price from the browser
$plans = [
  // One-time services
  'audit'     => ['name' => 'Self-Audit',                     'amount' => 12900, 'mode' => 'payment'],
  'full'      => ['name' => 'Full Inspection & Audit',        'amount' => 24900, 'mode' => 'payment'],
  // Solar audit tiers (services/audit page)
  'audit-report'     => ['name' => 'Full Audit Report',                    'amount' => 12900, 'mode' => 'payment',      'return' => '/services/audit/'],
  'audit-inspection' => ['name' => 'Full Audit + In-Person Inspection',    'amount' => 24900, 'mode' => 'payment',      'return' => '/services/audit/'],
  'audit-annual'     => ['name' => 'Annual Audit & Recommendations Plan',  'amount' => 10900, 'mode' => 'subscription', 'return' => '/services/audit/'],
  // Annual service plans (auto-renewing yearly subscriptions)
  'basic'     => ['name' => 'Service Plan — Essential',       'amount' => 14900, 'mode' => 'subscription'],
  'plus'      => ['name' => 'Service Plan — Plus',            'amount' => 26900, 'mode' => 'subscription'],
  'totalcare' => ['name' => 'Service Plan — Total Care',      'amount' => 46500, 'mode' => 'subscription'],
];

$planKey = $input['plan'] ?? '';
if (!isset($plans[$planKey])) { http_response_code(400); echo json_encode(['error' => 'Unknown plan']); exit; }
$plan  = $plans[$planKey];
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$email) { http_response_code(400); echo json_encode(['error' => 'Invalid email']); exit; }

$origin = 'https://yourenergybest.com';
$params = [
  'mode' => $plan['mode'],
  'customer_email' => $email,
  'line_items[0][price_data][currency]' => 'usd',
  'line_items[0][price_data][product_data][name]' => $plan['name'],
  'line_items[0][price_data][unit_amount]' => $plan['amount'],
  'line_items[0][quantity]' => 1,
  'success_url' => $origin . ($plan['return'] ?? '/services/') . '?session_id={CHECKOUT_SESSION_ID}&plan=' . $planKey,
  'cancel_url'  => $origin . ($plan['return'] ?? '/services/') . '?canceled=1',
  'metadata[plan]'  => $planKey,
  'metadata[name]'  => substr($input['name']  ?? '', 0, 100),
  'metadata[phone]' => substr($input['phone'] ?? '', 0, 30),
  'metadata[zip]'   => substr($input['zip']   ?? '', 0, 10),
  'metadata[opp]'   => substr($input['opp']   ?? '', 0, 100),
];

if ($plan['mode'] === 'subscription') {
  $params['line_items[0][price_data][recurring][interval]'] = 'year';
}

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => http_build_query($params),
  CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . STRIPE_SECRET_KEY],
  CURLOPT_TIMEOUT => 20,
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($resp, true);
if ($code !== 200 || empty($data['url'])) {
  error_log('Stripe session error: ' . $resp);
  http_response_code(502);
  echo json_encode(['error' => 'Could not start checkout. Please call (760) 860-7862.']);
  exit;
}
echo json_encode(['url' => $data['url']]);
