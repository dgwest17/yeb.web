<?php
/**
 * /newsletter/view.php?id=…&k=… — one newsletter as its email.
 * Zoho Campaigns imports the email from this address (content_url), with ?zoho=1
 * so Zoho's unsubscribe merge tag stays in; without it, it's the browser view.
 * The key (k) is random per newsletter, so drafts can't be guessed.
 */
require dirname(__DIR__) . '/newsletter-lib.php';
header('X-Robots-Tag: noindex');

$n = nl_find($_GET['id'] ?? '');
if (!$n || empty($n['key']) || !hash_equals($n['key'], (string)($_GET['k'] ?? ''))) {
  http_response_code(404);
  echo 'Not found';
  exit;
}
header('Content-Type: text/html; charset=utf-8');
echo nl_render($n, nl_data()['settings'], !empty($_GET['zoho']));
