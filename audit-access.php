<?php
/**
 * audit-access.php — Gate for the paid auditing tool.
 * Validates the signed token from stripe-verify.php, then grants access.
 * Point $AUDIT_TOOL_URL at your actual auditing platform page.
 */
require_once __DIR__ . '/config.php'; // AUDIT_ACCESS_SECRET

$AUDIT_TOOL_URL = '/audit.html'; // <-- change to your auditing tool entry page

function deny($msg) {
  http_response_code(403);
  echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Access Required</title><style>body{font-family:sans-serif;background:#091B2A;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;text-align:center;padding:2rem}a{color:#22A8B3;font-weight:700}</style></head><body><div><h1>🔒 ' . htmlspecialchars($msg) . '</h1><p>Purchase a Solar Health Audit to unlock this tool.</p><p><a href="/services/">View Plans →</a> &nbsp;|&nbsp; <a href="tel:7608607862">(760) 860-7862</a></p></div></body></html>';
  exit;
}

$token = $_GET['t'] ?? ($_COOKIE['yeb_audit'] ?? '');
if (!$token || strpos($token, '.') === false) deny('Access link required');

list($b64, $sig) = explode('.', $token, 2);
$payload = base64_decode(strtr($b64, '-_', '+/'));
if (!$payload || !hash_equals(hash_hmac('sha256', $payload, AUDIT_ACCESS_SECRET), $sig)) deny('Invalid access link');

list($email, $plan, $expires) = array_pad(explode('|', $payload), 3, '');
if (time() > (int)$expires) deny('Access link expired');

// Valid — set a cookie so they can return without the link, then hand off to the tool
setcookie('yeb_audit', $token, ['expires' => (int)$expires, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
session_start();
$_SESSION['audit_email'] = $email;
$_SESSION['audit_plan']  = $plan;
header('Location: ' . $AUDIT_TOOL_URL);
exit;
