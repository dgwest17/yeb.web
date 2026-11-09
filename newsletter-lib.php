<?php
/**
 * newsletter-lib.php — newsletters written in Admin → Newsletters, sent by Zoho Campaigns.
 *
 *   newsletters.json        what David writes (admin-saved, server-only):
 *                           { settings: { from_email, from_name, reply_to, list_key, topic_id, address },
 *                             items: [{ id, key, name, template, subject, preheader, date, greeting, intro,
 *                                       stories: [{ title, text, link_label, link_url }], headline, body,
 *                                       deadline, cta_label, cta_url, signoff, also_blog }] }
 *   newsletters-zoho.json   what Zoho says about each one (written only by newsletters.php):
 *                           { <id>: { campaignKey, list, created, contentHash, status, sent, report } }
 *
 * Templates: monthly (intro + stories), alert (rebate / deadline, urgent), tip (one idea).
 * Email HTML is table-based with inline styles, 600px wide — what email clients render.
 * Zoho fetches the email from /newsletter/view.php (content_url), which is also the
 * "view in browser" page.
 */
const NL_BASE = 'https://yourenergybest.com';
const NL_ZOHO_API = 'https://campaigns.zoho.com/api/v1.1';

function nl_data() {
  $j = json_decode(@file_get_contents(__DIR__ . '/newsletters.json') ?: '', true) ?: [];
  return $j + ['settings' => [], 'items' => []];
}
function nl_find($id) { foreach (nl_data()['items'] as $n) if (($n['id'] ?? '') === $id) return $n; return null; }
function nl_zoho_state() { return json_decode(@file_get_contents(__DIR__ . '/newsletters-zoho.json') ?: '', true) ?: []; }
function nl_zoho_save($id, array $patch) {
  $all = nl_zoho_state();
  $all[$id] = array_merge($all[$id] ?? [], $patch);
  file_put_contents(__DIR__ . '/newsletters-zoho.json', json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
  return $all[$id];
}
function nl_view_url($n) { return NL_BASE . '/newsletter/view.php?id=' . rawurlencode($n['id']) . '&k=' . rawurlencode($n['key'] ?? ''); }
/** Fingerprint of what the email says — to tell if it changed after Zoho imported it. */
function nl_hash($n) { $c = $n; unset($c['also_blog'], $c['name']); return substr(sha1(json_encode($c)), 0, 12); }

/* ───────────────────────────── Rendering ───────────────────────────── */

function nl_e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
/** Rich text from the admin editor → email-safe HTML with inline styles. */
function nl_rich($html, $color = '#33424d') {
  $h = preg_replace('#<(script|style|iframe)[^>]*>.*?</\1>#is', '', (string)$html);
  $h = strip_tags($h, '<p><br><strong><b><em><i><u><a><ul><ol><li><h3><h4>');
  $h = preg_replace('/\s(on\w+|style|class)\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $h);
  $h = preg_replace('/href\s*=\s*"(?!https?:|mailto:|tel:)[^"]*"/i', 'href="' . NL_BASE . '/"', $h);
  if (!preg_match('/<(p|ul|ol|h3|h4)\b/i', $h)) $h = '<p>' . nl2br($h) . '</p>';
  $p = "margin:0 0 14px;font-size:16px;line-height:1.6;color:$color;";
  return strtr($h, ['<p>' => "<p style=\"$p\">", '<ul>' => "<ul style=\"margin:0 0 14px;padding-left:22px;color:$color;\">",
    '<ol>' => "<ol style=\"margin:0 0 14px;padding-left:22px;color:$color;\">", '<li>' => '<li style="margin:0 0 6px;font-size:16px;line-height:1.5;">',
    '<a ' => '<a style="color:#1A8A94;font-weight:600;" ', '<h3>' => '<h3 style="margin:18px 0 8px;font-size:18px;color:#023047;">']);
}
function nl_button($label, $url, $bg = '#FB8500') {
  if (!trim((string)$label) || !trim((string)$url)) return '';
  return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 6px;"><tr><td style="border-radius:10px;background:' . $bg . ';">'
    . '<a href="' . nl_e($url) . '" style="display:inline-block;padding:14px 26px;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:10px;">'
    . nl_e($label) . '</a></td></tr></table>';
}

/**
 * The email. $forZoho keeps Zoho's merge tags (unsubscribe link); the browser view swaps them out.
 * Unsubscribe uses Zoho Campaigns' $[LI:UNSUBSCRIBE]$ tag — confirm it in the first test from Zoho.
 */
function nl_render($n, $settings, $forZoho = true) {
  $t = $n['template'] ?? 'monthly';
  $accent = $t === 'alert' ? '#EF476F' : '#219EBC';
  $date = !empty($n['date']) ? date('F j, Y', strtotime($n['date'])) : date('F j, Y');
  $kicker = ['monthly' => 'Monthly update', 'alert' => 'Heads up', 'tip' => 'Quick tip'][$t] ?? 'Update';
  $body = '';
  if ($t === 'monthly') {
    if (trim($n['greeting'] ?? '')) $body .= '<p style="margin:0 0 14px;font-size:17px;line-height:1.5;color:#023047;font-weight:bold;">' . nl_e($n['greeting']) . '</p>';
    if (trim(strip_tags($n['intro'] ?? ''))) $body .= nl_rich($n['intro']);
    foreach ($n['stories'] ?? [] as $s) {
      if (!trim(($s['title'] ?? '') . strip_tags($s['text'] ?? ''))) continue;
      $body .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0 4px;border-top:1px solid #e3e8ec;"><tr><td style="padding-top:18px;">'
        . (trim($s['title'] ?? '') ? '<h2 style="margin:0 0 8px;font-family:Georgia,serif;font-size:21px;line-height:1.3;color:#023047;font-weight:normal;">' . nl_e($s['title']) . '</h2>' : '')
        . nl_rich($s['text'] ?? '') . nl_button($s['link_label'] ?? '', $s['link_url'] ?? '', '#219EBC') . '</td></tr></table>';
    }
  } else {
    if (trim($n['headline'] ?? '')) $body .= '<h1 style="margin:0 0 12px;font-family:Georgia,serif;font-size:27px;line-height:1.25;color:#023047;font-weight:normal;">' . nl_e($n['headline']) . '</h1>';
    if ($t === 'alert' && trim($n['deadline'] ?? '')) $body .= '<p style="margin:0 0 16px;padding:10px 14px;background:#fdecef;border-left:4px solid #EF476F;font-size:15px;color:#9b1c38;font-weight:bold;">' . nl_e($n['deadline']) . '</p>';
    $body .= nl_rich($n['body'] ?? '');
  }
  $body .= '<div style="margin-top:18px;">' . nl_button($n['cta_label'] ?? '', $n['cta_url'] ?? '') . '</div>';
  $sign = trim($n['signoff'] ?? '') !== '' ? $n['signoff'] : "Talk soon,\nDavid West";
  $body .= '<p style="margin:22px 0 0;font-size:16px;line-height:1.5;color:#33424d;">' . nl2br(nl_e($sign)) . '</p>';

  $address = trim($settings['address'] ?? '');
  $unsub = $forZoho ? '<a href="$[LI:UNSUBSCRIBE]$" style="color:#5b6b77;">Unsubscribe</a>' : '<span>Unsubscribe link appears in the email</span>';
  $pre = nl_e($n['preheader'] ?? '');

  return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
    . '<title>' . nl_e($n['subject'] ?? 'Newsletter') . '</title></head>'
    . '<body style="margin:0;padding:0;background:#eef2f5;">'
    . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;">' . $pre . '&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;</div>'
    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eef2f5;"><tr><td align="center" style="padding:24px 12px;">'
    . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;font-family:Arial,Helvetica,sans-serif;">'
    . '<tr><td style="background:#023047;padding:20px 28px;">'
    .   '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
    .   '<td><img src="' . NL_BASE . '/img/logo.png" width="64" height="64" alt="Your Energy Best" style="display:block;border:0;"></td>'
    .   '<td align="right" style="font-size:12px;letter-spacing:1px;text-transform:uppercase;color:#8ECAE6;">' . nl_e($kicker) . '<br><span style="color:#ffffff;letter-spacing:0;text-transform:none;font-size:13px;">' . nl_e($date) . '</span></td>'
    .   '</tr></table></td></tr>'
    . '<tr><td style="height:4px;background:' . $accent . ';font-size:0;line-height:0;">&nbsp;</td></tr>'
    . '<tr><td style="padding:28px 28px 30px;">' . $body . '</td></tr>'
    . '<tr><td style="background:#f6f9fb;padding:18px 28px;font-size:12px;line-height:1.6;color:#5b6b77;">'
    .   'Your Energy Best · Solar + battery in San Diego &amp; Orange County · <a href="' . NL_BASE . '/" style="color:#5b6b77;">yourenergybest.com</a><br>'
    .   ($address !== '' ? nl_e($address) . '<br>' : '')
    .   'You\'re getting this because you have solar with us or we\'ve met about your energy. ' . $unsub
    . '</td></tr></table></td></tr></table></body></html>';
}

/** Plain-text version of a newsletter — for the blog post and Luma. */
function nl_text_html($n) {
  $t = $n['template'] ?? 'monthly';
  $h = '';
  if ($t === 'monthly') {
    $h .= (trim($n['intro'] ?? '') ? $n['intro'] : '');
    foreach ($n['stories'] ?? [] as $s) {
      if (trim($s['title'] ?? '')) $h .= '<h3>' . nl_e($s['title']) . '</h3>';
      $h .= $s['text'] ?? '';
      if (trim($s['link_url'] ?? '')) $h .= '<p><a href="' . nl_e($s['link_url']) . '">' . nl_e($s['link_label'] ?: 'Read more') . '</a></p>';
    }
  } else {
    if ($t === 'alert' && trim($n['deadline'] ?? '')) $h .= '<p><strong>' . nl_e($n['deadline']) . '</strong></p>';
    $h .= $n['body'] ?? '';
  }
  if (trim($n['cta_url'] ?? '')) $h .= '<p><a href="' . nl_e($n['cta_url']) . '">' . nl_e($n['cta_label'] ?: 'Learn more') . '</a></p>';
  return $h;
}

/* ───────────────────────────── Zoho Campaigns ───────────────────────────── */

/** One access token from the site's Zoho refresh token (the grant must include the Campaigns scopes). */
function nl_zoho_token() {
  static $tok = null;
  if ($tok) return $tok;
  $cfg = file_exists(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];
  $refresh = trim(@file_get_contents(__DIR__ . '/.zoho_refresh_token') ?: '');
  if ($refresh === '') throw new RuntimeException('Zoho isn\'t connected yet (Admin → Connections).');
  $ch = curl_init('https://accounts.zoho.com/oauth/v2/token');
  curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_POSTFIELDS => http_build_query([
    'refresh_token' => $refresh, 'client_id' => $cfg['zoho_client_id'] ?? '', 'client_secret' => $cfg['zoho_client_secret'] ?? '', 'grant_type' => 'refresh_token'])]);
  $j = json_decode(curl_exec($ch) ?: '', true) ?: [];
  curl_close($ch);
  if (empty($j['access_token'])) throw new RuntimeException('Zoho refused the connection (' . ($j['error'] ?? 'no token') . ').');
  return $tok = $j['access_token'];
}

/** Call a Zoho Campaigns method. Returns the decoded JSON. */
function nl_zoho($method, array $params = [], $post = true) {
  $params['resfmt'] = 'JSON';
  $url = NL_ZOHO_API . '/' . $method;
  $ch = curl_init($post ? $url : $url . '?' . http_build_query($params));
  $opt = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25,
          CURLOPT_HTTPHEADER => ['Authorization: Zoho-oauthtoken ' . nl_zoho_token(), 'Content-Type: application/x-www-form-urlencoded']];
  if ($post) { $opt[CURLOPT_POST] = true; $opt[CURLOPT_POSTFIELDS] = http_build_query($params); }
  curl_setopt_array($ch, $opt);
  $raw = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  $j = json_decode($raw ?: '', true);
  if (!is_array($j)) throw new RuntimeException("Zoho Campaigns answered HTTP $code: " . substr((string)$raw, 0, 160));
  if (isset($j['code']) && $j['code'] === 'INVALID_SCOPE' || (isset($j['message']) && stripos((string)$j['message'], 'scope') !== false && ($j['status'] ?? '') === 'error'))
    throw new RuntimeException('The Zoho connection doesn\'t include Zoho Campaigns yet — reconnect in Admin → Connections with the scopes shown there.');
  return $j;
}
