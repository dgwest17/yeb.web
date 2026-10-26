<?php
/**
 * stripe-lib.php — Shared Stripe helpers for stripe-verify.php and stripe-webhook.php
 * Requires config.php to be loaded first (STRIPE_SECRET_KEY, AUDIT_ACCESS_SECRET).
 *
 * The browser redirect (stripe-verify) and the Stripe webhook both report the same
 * paid checkout. record_paid_checkout() makes sure Zoho hears about it exactly once,
 * whichever arrives first, and no matter how many times the success page reloads.
 */

define('STRIPE_PROCESSED_DIR', __DIR__ . '/.stripe_processed');

function stripe_get($path) {
  $ch = curl_init('https://api.stripe.com/v1/' . ltrim($path, '/'));
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . STRIPE_SECRET_KEY],
    CURLOPT_TIMEOUT => 20,
  ]);
  $resp = curl_exec($ch);
  curl_close($ch);
  return json_decode($resp ?: '', true) ?: [];
}

/**
 * Server-to-server post to zoho.php. Signed so zoho.php skips the spam filters —
 * a paying customer with an all-caps name must never be dropped as "gibberish".
 */
function zoho_internal_post(array $payload) {
  $body = json_encode($payload);
  $zc = curl_init('https://yourenergybest.com/zoho.php');
  curl_setopt_array($zc, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
      'X-YEB-Internal: ' . hash_hmac('sha256', $body, AUDIT_ACCESS_SECRET),
    ],
    CURLOPT_TIMEOUT => 15,
  ]);
  $resp = curl_exec($zc);
  curl_close($zc);
  return json_decode($resp ?: '', true) ?: [];
}

/** Atomically claim an event key. Returns false if it was already claimed. */
function stripe_claim_once($key) {
  if (!is_dir(STRIPE_PROCESSED_DIR)) {
    @mkdir(STRIPE_PROCESSED_DIR, 0755, true);
    @file_put_contents(STRIPE_PROCESSED_DIR . '/.htaccess', "Require all denied\n");
  }
  $file = STRIPE_PROCESSED_DIR . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $key);
  $fp = @fopen($file, 'x'); // fails if the file already exists
  if (!$fp) return false;
  fwrite($fp, date('c'));
  fclose($fp);
  return true;
}

/** Give a claim back so the next trigger (e.g. a Stripe webhook retry) tries again. */
function stripe_release($key) {
  @unlink(STRIPE_PROCESSED_DIR . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $key));
}

/**
 * Push a paid Checkout Session to Zoho, once. $s is the Stripe session object.
 * Returns false only when zoho.php could not be reached (the claim is released).
 */
function record_paid_checkout(array $s) {
  if (empty($s['id']) || ($s['payment_status'] ?? '') !== 'paid') return true;
  if (!stripe_claim_once($s['id'])) return true;

  $m = $s['metadata'] ?? [];
  $amount = number_format(($s['amount_total'] ?? 0) / 100, 2);
  $note = 'Stripe payment CONFIRMED: $' . $amount . ' | ' . ($m['plan'] ?? '') . ' | session:' . $s['id'];

  $res = zoho_internal_post([
    'action' => 'lead',
    'source_form' => 'StripeIntake',
    'lead_source' => 'Website',
    'opportunity_type' => $m['opp'] ?? '',
    'name'  => $m['name'] ?? '',
    'email' => $s['customer_details']['email'] ?? ($s['customer_email'] ?? ''),
    'phone' => $m['phone'] ?? '',
    'zip'   => $m['zip'] ?? '',
    'payment_note' => $note,
    'customer_option' => $m['plan'] ?? '',
    'utm_source'   => $m['utm_source'] ?? '',
    'utm_medium'   => $m['utm_medium'] ?? '',
    'utm_campaign' => $m['utm_campaign'] ?? '',
    'referrer'     => $m['referrer'] ?? '',
    'zoho_note'    => ['title' => 'Payment received', 'content' => $note],
  ]);
  if (empty($res['success'])) { stripe_release($s['id']); return false; }
  return true;
}

/** Signed audit-tool access link (valid 1 year). */
function audit_access_url($email, $plan) {
  $expires = time() + 31536000;
  $payload = $email . '|' . $plan . '|' . $expires;
  $sig = hash_hmac('sha256', $payload, AUDIT_ACCESS_SECRET);
  $token = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=') . '.' . $sig;
  return 'https://yourenergybest.com/audit-access.php?t=' . urlencode($token);
}
