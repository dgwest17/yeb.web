<?php
/**
 * stripe-verify.php — Called by /services/ and /services/audit/ after Stripe redirects back with ?session_id=
 * 1. Verifies with Stripe that the session is actually PAID (never trust the redirect alone)
 * 2. Records the paid lead in Zoho — once per session, shared with stripe-webhook.php
 * 3. Issues a signed access token so the customer can open the auditing tool
 *
 * GET ?session_id=cs_live_...
 * Returns: { paid: true, plan, name, email, audit_url }
 */
header('Content-Type: application/json');
require_once __DIR__ . '/config.php'; // STRIPE_SECRET_KEY, AUDIT_ACCESS_SECRET
require_once __DIR__ . '/stripe-lib.php';

$sessionId = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['session_id'] ?? '');
if (!$sessionId) { http_response_code(400); echo json_encode(['error' => 'Missing session']); exit; }

$s = stripe_get('checkout/sessions/' . $sessionId);

if (empty($s['id']) || ($s['payment_status'] ?? '') !== 'paid') {
  echo json_encode(['paid' => false]);
  exit;
}

record_paid_checkout($s); // best-effort; payment success shouldn't block on CRM

$plan  = $s['metadata']['plan'] ?? '';
$email = $s['customer_details']['email'] ?? ($s['customer_email'] ?? '');

echo json_encode([
  'paid' => true,
  'plan' => $plan,
  'name' => $s['metadata']['name'] ?? '',
  'email' => $email,
  'audit_url' => audit_access_url($email, $plan),
]);
