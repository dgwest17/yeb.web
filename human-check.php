<?php
/**
 * human-check.php — Cloudflare Turnstile, checked on our server for the website's lead
 * forms (zoho.php, prep.php). The browser side is in nav.js: one invisible widget per
 * page adds an X-Turnstile header to every form post to those two endpoints.
 *
 * Needs define('TURNSTILE_SECRET', '…') in config.php (the same widget's Secret Key the
 * lumaria apps use; the site key 0x4AAAAAAGJ0iCXjMOv6QHsv is public). Without it, or if
 * Cloudflare can't be reached, forms are accepted as before: a lead is never lost to
 * this check. A failed check HOLDS the lead (backup + email), it never drops it.
 *
 * Returns [true, ''] for a person (or when the check can't run), [false, reason] otherwise.
 */
function human_check() {
  if (!defined('TURNSTILE_SECRET') || TURNSTILE_SECRET === '') return [true, 'not configured'];
  $token = trim((string)($_SERVER['HTTP_X_TURNSTILE'] ?? ''));
  if ($token === '') return [false, 'no human check'];
  $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 8,
    CURLOPT_POSTFIELDS => http_build_query(['secret' => TURNSTILE_SECRET, 'response' => $token, 'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '']),
  ]);
  $raw = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  $j = json_decode($raw ?: '', true);
  if ($code !== 200 || !is_array($j)) return [true, 'cloudflare unreachable'];   // fail open: never lose a lead
  if (!empty($j['success'])) return [true, ''];
  return [false, 'human check failed: ' . implode(',', (array)($j['error-codes'] ?? []))];
}
