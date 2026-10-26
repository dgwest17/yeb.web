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

// Server-side price map — NEVER trust a price from the browser. Prices live in plans.php only.
$plans = require __DIR__ . '/plans.php';

$planKey = $input['plan'] ?? '';
if (!isset($plans[$planKey])) { http_response_code(400); echo json_encode(['error' => 'Unknown plan']); exit; }
$plan  = $plans[$planKey];
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$email) { http_response_code(400); echo json_encode(['error' => 'Invalid email']); exit; }

$origin = 'https://yourenergybest.com';
// Send the customer back to the page they bought from (allowlisted), else the plan's default
$returnPath = in_array($input['return'] ?? '', ['/services/', '/services/audit/'], true)
  ? $input['return'] : ($plan['return'] ?? '/services/');
// Zoho Opportunity_Type when the page didn't send one (the audit page doesn't)
$defaultOpp = $plan['mode'] === 'subscription' && strpos($planKey, 'audit') !== 0
  ? 'Solar Owner – Under Service Plan' : 'Solar Owner – Audit / Review';

$params = [
  'mode' => $plan['mode'],
  'customer_email' => $email,
  'line_items[0][price_data][currency]' => 'usd',
  'line_items[0][price_data][product_data][name]' => $plan['name'],
  'line_items[0][price_data][unit_amount]' => $plan['amount'],
  'line_items[0][quantity]' => 1,
  'success_url' => $origin . $returnPath . '?session_id={CHECKOUT_SESSION_ID}&plan=' . $planKey,
  'cancel_url'  => $origin . $returnPath . '?canceled=1',
  'metadata[plan]'  => $planKey,
  'metadata[name]'  => substr($input['name']  ?? '', 0, 100),
  'metadata[phone]' => substr($input['phone'] ?? '', 0, 30),
  'metadata[zip]'   => substr($input['zip']   ?? '', 0, 10),
  'metadata[opp]'   => substr(($input['opp'] ?? '') ?: $defaultOpp, 0, 100),
];
// Campaign attribution (captured site-wide by nav.js) rides along to Zoho on payment
foreach (['utm_source', 'utm_medium', 'utm_campaign', 'referrer'] as $k) {
  if (!empty($input[$k])) $params["metadata[$k]"] = substr($input[$k], 0, 200);
}

if ($plan['mode'] === 'subscription') {
  $params['line_items[0][price_data][recurring][interval]'] = 'year';
  // Lets the webhook name the plan on renewals, failed cards, and cancellations
  $params['subscription_data[metadata][plan]'] = $planKey;
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
