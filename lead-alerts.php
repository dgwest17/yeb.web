<?php
/**
 * lead-alerts.php — tell David when a website lead did NOT reach Zoho.
 * Used by zoho.php and prep.php. The visitor still sees success (their details are
 * backed up and emailed), so this email is the only signal that something broke.
 */
const YEB_ALERT_TO = 'info@yourenergybest.com, dwest@yourenergybest.com';

function yeb_lead_alert($what, $why, $data = []) {
  $body = "$what\n\nWhy: $why\n\nThis person is not in Zoho. Add or contact them yourself.\n\n";
  foreach ($data as $k => $v) {
    if (in_array($k, ['website', 'company_url', '_ts', 'zoho_note'], true) || $v === '' || $v === null) continue;
    $body .= ucfirst(str_replace('_', ' ', (string)$k)) . ': ' . (is_scalar($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE)) . "\n";
  }
  $body .= "\nZoho connection check: https://yourenergybest.com/zoho-health.php\n";
  return @mail(YEB_ALERT_TO, 'ACTION: ' . str_replace(["\r", "\n"], ' ', $what), $body, "From: noreply@yourenergybest.com\r\n");
}
