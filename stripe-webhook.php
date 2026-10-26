<?php
/**
 * stripe-webhook.php — Stripe → Zoho, server to server.
 *
 * Why this exists: stripe-verify.php only runs if the customer's browser makes it back
 * to our success page. Close the tab, lose signal, or a renewal a year later — none of
 * those ever reach the site. Stripe calls this endpoint directly for all of them.
 *
 * Setup (Stripe Dashboard → Developers → Webhooks → Add endpoint):
 *   URL:    https://yourenergybest.com/stripe-webhook.php
 *   Events: checkout.session.completed, checkout.session.async_payment_succeeded,
 *           invoice.paid, invoice.payment_failed, customer.subscription.deleted
 * Then add the signing secret to config.php, above `return [`:
 *   define('STRIPE_WEBHOOK_SECRET', 'whsec_...');
 */
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/stripe-lib.php';

function done($code, $msg) { http_response_code($code); echo json_encode(['received' => $code < 300, 'msg' => $msg]); exit; }

if (!defined('STRIPE_WEBHOOK_SECRET') || STRIPE_WEBHOOK_SECRET === '') done(500, 'Webhook secret not configured');

// ─── Verify the Stripe-Signature header (t=...,v1=...) ───
$body = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$ts = null; $sigs = [];
foreach (explode(',', $sigHeader) as $part) {
  [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
  if ($k === 't') $ts = (int)$v;
  if ($k === 'v1') $sigs[] = $v;
}
if (!$ts || !$sigs || abs(time() - $ts) > 300) done(400, 'Bad signature header');
$expected = hash_hmac('sha256', $ts . '.' . $body, STRIPE_WEBHOOK_SECRET);
$valid = false;
foreach ($sigs as $sig) { if (hash_equals($expected, $sig)) { $valid = true; break; } }
if (!$valid) done(400, 'Signature mismatch');

$event = json_decode($body, true);
$obj = $event['data']['object'] ?? [];
$type = $event['type'] ?? '';

/** Timeline note on the customer's Zoho lead (created if they're not there yet). */
function zoho_note($email, $name, $title, $content) {
  $res = zoho_internal_post([
    'action' => 'note', 'source_form' => 'StripeWebhook', 'lead_source' => 'Website',
    'email' => $email, 'name' => $name,
    'zoho_note' => ['title' => $title, 'content' => $content],
  ]);
  return !empty($res['success']);
}

function sub_plan(array $o) {
  return $o['parent']['subscription_details']['metadata']['plan']   // API 2025+
      ?? $o['subscription_details']['metadata']['plan']             // older API versions
      ?? $o['metadata']['plan'] ?? 'subscription';
}

switch ($type) {
  case 'checkout.session.completed':
  case 'checkout.session.async_payment_succeeded':
    if (!record_paid_checkout($obj)) done(500, 'Zoho unreachable — retry');
    done(200, 'checkout recorded');

  case 'invoice.paid':
    // The first invoice is already covered by checkout.session.completed
    if (($obj['billing_reason'] ?? '') !== 'subscription_cycle') done(200, 'not a renewal');
    if (!stripe_claim_once('inv_paid_' . $obj['id'])) done(200, 'duplicate');
    $amt = number_format(($obj['amount_paid'] ?? 0) / 100, 2);
    if (!zoho_note($obj['customer_email'] ?? '', $obj['customer_name'] ?? '', 'Annual plan renewed',
        'Renewal paid: $' . $amt . ' | ' . sub_plan($obj) . ' | invoice:' . $obj['id'])) {
      stripe_release('inv_paid_' . $obj['id']); done(500, 'Zoho unreachable — retry');
    }
    done(200, 'renewal noted');

  case 'invoice.payment_failed':
    $key = 'inv_failed_' . $obj['id'] . '_' . ($obj['attempt_count'] ?? 0);
    if (!stripe_claim_once($key)) done(200, 'duplicate');
    $amt = number_format(($obj['amount_due'] ?? 0) / 100, 2);
    if (!zoho_note($obj['customer_email'] ?? '', $obj['customer_name'] ?? '', 'Payment FAILED — follow up',
        'Card declined for $' . $amt . ' | ' . sub_plan($obj) . ' | attempt ' . ($obj['attempt_count'] ?? '?') . ' | invoice:' . $obj['id'])) {
      stripe_release($key); done(500, 'Zoho unreachable — retry');
    }
    done(200, 'failure noted');

  case 'customer.subscription.deleted':
    if (!stripe_claim_once('sub_deleted_' . $obj['id'])) done(200, 'duplicate');
    $cust = stripe_get('customers/' . ($obj['customer'] ?? ''));
    $reason = $obj['cancellation_details']['reason'] ?? 'canceled';
    if (!zoho_note($cust['email'] ?? '', $cust['name'] ?? '', 'Annual plan ended',
        'Subscription ended (' . $reason . ') | ' . ($obj['metadata']['plan'] ?? 'subscription') . ' | ' . $obj['id'])) {
      stripe_release('sub_deleted_' . $obj['id']); done(500, 'Zoho unreachable — retry');
    }
    done(200, 'cancellation noted');

  default:
    done(200, 'ignored');
}
