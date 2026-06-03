<?php
/**
 * become/includes/mailer.php — Notification email helper
 * Location: public_html/become/includes/mailer.php
 *
 * Starts on PHP mail() (works on Hostinger for same-domain From).
 * To switch to Zoho SMTP later, replace the body of portal_send_mail()
 * with a PHPMailer SMTP call — every caller stays the same.
 *
 * config.php keys used (all optional, with sensible defaults):
 *   'notify_email'  => 'dwest@yourenergybest.com'   // who gets pass-off alerts
 *   'mail_from'     => 'no-reply@yourenergybest.com'
 *   'site_url'      => 'https://yourenergybest.com'
 */

function portal_mail_config() {
    static $cfg = null;
    if ($cfg === null) {
        $cfg = [];
        $p = __DIR__ . '/../../config.php';
        if (file_exists($p)) {
            $loaded = require $p;
            if (is_array($loaded)) $cfg = $loaded;
        }
    }
    return [
        'notify_email' => $cfg['notify_email'] ?? 'dwest@yourenergybest.com',
        'mail_from'    => $cfg['mail_from']    ?? 'no-reply@yourenergybest.com',
        'site_url'     => $cfg['site_url']     ?? 'https://yourenergybest.com',
    ];
}

/**
 * Send a plain notification email. Returns true on accepted-for-delivery.
 * Never throws — notification failure must not break the request.
 */
function portal_send_mail($to, $subject, $bodyText) {
    $cfg = portal_mail_config();
    $from = $cfg['mail_from'];
    $headers = implode("\r\n", [
        'From: Your Energy Best Training <' . $from . '>',
        'Reply-To: ' . $from,
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: become-portal',
    ]);
    try {
        return @mail($to, $subject, $bodyText, $headers, '-f' . $from);
    } catch (Exception $e) {
        return false;
    }
}

/** Notify the admin that a rep requested a level pass-off. */
function notify_level_passoff($rep, $level) {
    $cfg  = portal_mail_config();
    $name = trim(($rep['first_name'] ?? '') . ' ' . ($rep['last_name'] ?? ''));
    if ($name === '') $name = $rep['email'] ?? ('User #' . ($rep['id'] ?? '?'));

    $subject = "Pass-off request: {$name} → Level {$level}";
    $body = "{$name} has completed every module in Level {$level} and is requesting a pass-off to advance.\n\n"
          . "Rep: {$name}\n"
          . "Email: " . ($rep['email'] ?? 'n/a') . "\n"
          . "Level cleared: {$level}\n\n"
          . "Review and approve in the management console:\n"
          . rtrim($cfg['site_url'], '/') . "/become/manage.php#passoffs\n";

    return portal_send_mail($cfg['notify_email'], $subject, $body);
}
