<?php
/**
 * stripe-verify.php — Called by services.html after Stripe redirects back with ?session_id=
 * 1. Verifies with Stripe that the session is actually PAID (never trust the redirect alone)
 * 2. Upserts a confirmed Lead into Zoho via zoho.php internals
 * 3. Issues a signed access token so the customer can open the auditing tool
 *
 * GET ?session_id=cs_live_...
 * Returns: { paid: true, plan, name, email, audit_url }
 */
header('Content-Type: application/json');
require_once __DIR__ . '/config.php'; // STRIPE_SECRET_KEY, AUDIT_ACCESS_SECRET

$sessionId = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['session_id'] ?? '');
if (!$sessionId) { http_response_code(400); echo json_encode(['error' => 'Missing session']); exit; }

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . $sessionId);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . STRIPE_SECRET_KEY],
  CURLOPT_TIMEOUT => 20,
]);
$resp = curl_exec($ch);
curl_close($ch);
$s = json_decode($resp, true);

if (empty($s['id']) || ($s['payment_status'] ?? '') !== 'paid') {
  echo json_encode(['paid' => false]);
  exit;
}

$plan  = $s['metadata']['plan']  ?? '';
$name  = $s['metadata']['name']  ?? '';
$phone = $s['metadata']['phone'] ?? '';
$zip   = $s['metadata']['zip']   ?? '';
$opp   = $s['metadata']['opp']   ?? '';
$email = $s['customer_details']['email'] ?? ($s['customer_email'] ?? '');
$amount = number_format(($s['amount_total'] ?? 0) / 100, 2);

// --- Confirm the lead in Zoho (server-to-server; reuses your zoho.php endpoint) ---
$zohoPayload = json_encode([
  'action' => 'lead',
  'source_form' => 'StripeIntake',
  'lead_source' => 'Website',
  'opportunity_type' => $opp,
  'name' => $name, 'email' => $email, 'phone' => $phone, 'zip' => $zip,
  'payment_note' => 'Stripe payment CONFIRMED: $' . $amount . ' | ' . $plan . ' | session:' . $sessionId,
  'customer_option' => $plan,
]);
$zc = curl_init('https://yourenergybest.com/zoho.php');
curl_setopt_array($zc, [
  CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => $zohoPayload,
  CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
  CURLOPT_TIMEOUT => 15,
]);
curl_exec($zc); // best-effort; payment success shouldn't block on CRM
curl_close($zc);

// --- Issue signed audit-tool access token (valid 1 year) ---
$expires = time() + 31536000;
$payload = $email . '|' . $plan . '|' . $expires;
$sig = hash_hmac('sha256', $payload, AUDIT_ACCESS_SECRET);
$token = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=') . '.' . $sig;

echo json_encode([
  'paid' => true,
  'plan' => $plan,
  'name' => $name,
  'email' => $email,
  'audit_url' => 'https://yourenergybest.com/audit-access.php?t=' . urlencode($token),
]);
