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
 * Templates: update (the Lumaria Energy Update; older ones are "monthly"), welcome, alert (rebate /
 * deadline, urgent), tip (one idea). See nl_render.
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
 * The email — rebuilt from David's Canva designs ("Lumaria Energy Update" and "Welcome to
 * Lumaria Energy", Nov 2026): the official logo, Lumaria teal + sunset orange, the palm
 * photos from those designs (img/email/), cards that stack on phones, a teal footer.
 *
 *   update   the newsletter: title + palms, "This month at Lumaria Energy", up to three
 *            cards (Energy tip · Did you know? · Customer corner), refer a friend
 *            (older "monthly" ones show the same way; their stories become the cards)
 *   welcome  for someone new: "Welcome to Lumaria Energy", the message, Contact our team,
 *            the four promises, "Thanks for choosing lumaria energy"
 *   alert    a rebate or deadline;   tip   one idea
 *
 * $forZoho keeps Zoho Campaigns' unsubscribe tag ($[LI:UNSUBSCRIBE]$ — confirm it in the
 * first test from Zoho); the browser view swaps it out.
 */
const NL_TEAL = '#0E97A0';
const NL_ORANGE = '#F7861E';
const NL_NAVY = '#0B2A4A';
const NL_INK = '#3B4A55';
const NL_TINT = '#E6F6F7';
const NL_PHONE = '(858) 786-2204';
const NL_TEL = 'tel:+18587862204';

function nl_img($url) { $u = trim((string)$url); if ($u === '') return ''; return preg_match('#^https?://#', $u) ? $u : NL_BASE . '/' . ltrim($u, '/'); }
function nl_photo($url, $alt = '', $width = 600, $radius = 0) {
  $src = nl_img($url); if ($src === '') return '';
  return '<img src="' . nl_e($src) . '" alt="' . nl_e($alt) . '" width="' . (int)$width . '" style="display:block;width:100%;max-width:' . (int)$width . 'px;height:auto;border:0;' . ($radius ? "border-radius:{$radius}px;" : '') . '">';
}
function nl_head($kicker) {
  return '<tr><td align="center" style="padding:22px 24px 12px;background:#ffffff;">'
    . '<img src="' . NL_BASE . '/img/brand/lumaria-energy-with-logo.png" width="150" alt="lumaria energy" style="display:block;width:150px;height:auto;border:0;margin:0 auto;">'
    . '<div style="margin-top:8px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:' . NL_TEAL . ';font-weight:bold;">' . nl_e($kicker) . '</div></td></tr>';
}
function nl_values($items, $bg = '#ffffff') {
  $w = floor(100 / count($items));
  $dots = [NL_ORANGE, NL_TEAL, '#FFC94A', '#5FC2C7'];
  $cells = '';
  foreach ($items as $i => [$icon, $title, $text]) {
    $cells .= '<td class="col" width="' . $w . '%" valign="top" align="center" style="padding:10px 6px;">'
      . '<div style="width:48px;height:48px;line-height:48px;border-radius:50%;background:' . $dots[$i % 4] . ';color:#ffffff;font-size:22px;margin:0 auto 8px;">' . $icon . '</div>'
      . '<div style="font-size:12px;font-weight:bold;letter-spacing:.5px;text-transform:uppercase;color:' . NL_NAVY . ';">' . nl_e($title) . '</div>'
      . ($text !== '' ? '<div style="font-size:12px;line-height:1.4;color:' . NL_INK . ';margin-top:4px;">' . nl_e($text) . '</div>' : '') . '</td>';
  }
  return '<tr><td style="padding:14px 18px 20px;background:' . $bg . ';"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>' . $cells . '</tr></table></td></tr>';
}
/** The newsletter's cards: its own, or (an older "monthly" one) its stories. */
function nl_cards($n) {
  $cards = $n['cards'] ?? null;
  if (!is_array($cards)) $cards = array_map(fn($s) => ['title' => $s['title'] ?? '', 'text' => $s['text'] ?? '', 'link_label' => $s['link_label'] ?? '', 'link_url' => $s['link_url'] ?? ''], $n['stories'] ?? []);
  return array_values(array_filter($cards, fn($c) => trim(($c['title'] ?? '') . strip_tags($c['text'] ?? '')) !== ''));
}

function nl_render($n, $settings, $forZoho = true) {
  $t = $n['template'] ?? 'update';
  if ($t === 'monthly') $t = 'update';
  $month = !empty($n['date']) ? date('F Y', strtotime($n['date'])) : date('F Y');
  $rows = '';

  if ($t === 'update') {
    $rows .= nl_head('Solar tips · Updates · Community');
    $hero = trim($n['hero_image'] ?? '') !== '' ? $n['hero_image'] : 'img/email/nl-hero.jpg';
    $rows .= '<tr><td style="padding:0;background:' . NL_TINT . ';"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
      . '<td class="col" width="55%" valign="middle" style="padding:24px 22px 22px 28px;">'
      .   '<div style="font-size:30px;line-height:1.08;font-weight:bold;color:' . NL_TEAL . ';text-transform:uppercase;">' . nl_e(($n['title'] ?? '') !== '' ? $n['title'] : 'Lumaria Energy') . '<br><span style="color:' . NL_ORANGE . ';">' . nl_e(($n['title_accent'] ?? '') !== '' ? $n['title_accent'] : 'Update') . '</span></div>'
      .   '<div style="margin:14px 0 12px;background:#FFC94A;border-radius:6px;padding:9px 12px;font-size:14px;line-height:1.4;color:' . NL_NAVY . ';">' . nl_e(($n['subtitle'] ?? '') !== '' ? $n['subtitle'] : 'Tips, updates & everything you need to get your energy best.') . '</div>'
      .   '<span style="display:inline-block;border:1px solid ' . NL_TEAL . ';background:#ffffff;border-radius:6px;padding:6px 12px;font-size:12px;font-weight:bold;letter-spacing:1px;color:' . NL_TEAL . ';text-transform:uppercase;">' . nl_e($month) . '</span>'
      . '</td><td class="col" width="45%" valign="middle" style="padding:0;">' . nl_photo($hero, 'Palm trees on the San Diego coast', 270) . '</td>'
      . '</tr></table></td></tr>';
    $rows .= '<tr><td align="center" style="padding:28px 30px 6px;background:#ffffff;">'
      . '<div style="font-size:20px;font-weight:bold;color:' . NL_TEAL . ';text-transform:uppercase;letter-spacing:.5px;">' . nl_e(($n['intro_heading'] ?? '') !== '' ? $n['intro_heading'] : 'This month at Lumaria Energy') . '</div>'
      . '<div style="width:46px;height:3px;background:#FFC94A;margin:10px auto 14px;font-size:0;line-height:0;">&nbsp;</div>'
      . (trim($n['greeting'] ?? '') !== '' ? '<p style="margin:0 0 12px;font-size:16px;color:' . NL_NAVY . ';font-weight:bold;">' . nl_e($n['greeting']) . '</p>' : '')
      . '<div style="text-align:center;">' . nl_rich($n['intro'] ?? '', NL_INK) . '</div></td></tr>';
    $cards = nl_cards($n);
    if ($cards) {
      $w = floor(100 / count($cards));
      $badge = [NL_ORANGE, NL_TEAL, '#FFC94A'];
      $cells = '';
      foreach ($cards as $i => $c) {
        $cells .= '<td class="col" width="' . $w . '%" valign="top" style="padding:8px;">'
          . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff;border:1px solid #e3eef0;border-radius:10px;overflow:hidden;">'
          . (trim($c['image'] ?? '') !== '' ? '<tr><td style="padding:0;">' . nl_photo($c['image'], $c['title'] ?? '', 210) . '</td></tr>' : '<tr><td style="height:5px;background:' . $badge[$i % 3] . ';font-size:0;line-height:0;">&nbsp;</td></tr>')
          . '<tr><td style="padding:14px 14px 16px;">'
          .   '<div style="font-size:15px;line-height:1.25;font-weight:bold;color:' . NL_TEAL . ';text-transform:uppercase;margin-bottom:8px;">' . nl_e($c['title'] ?? '') . '</div>'
          .   '<div style="font-size:14px;line-height:1.5;color:' . NL_INK . ';">' . strip_tags((string)($c['text'] ?? ''), '<strong><b><em><i><br><a>') . '</div>'
          .   (trim($c['link_url'] ?? '') !== '' ? '<a href="' . nl_e($c['link_url']) . '" style="display:inline-block;margin-top:10px;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;color:' . NL_TEAL . ';text-decoration:none;">' . nl_e(($c['link_label'] ?? '') !== '' ? $c['link_label'] : 'Learn more') . ' &rarr;</a>' : '')
          . '</td></tr></table></td>';
      }
      $rows .= '<tr><td style="padding:8px 14px 12px;background:#ffffff;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>' . $cells . '</tr></table></td></tr>';
    }
    if (!isset($n['referral']) || $n['referral']) {
      $rows .= '<tr><td style="padding:10px 22px 22px;background:#ffffff;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#FFF6DC;border-radius:12px;overflow:hidden;"><tr>'
        . '<td class="col" width="42%" valign="middle" style="padding:0;">' . nl_photo('img/email/nl-refer.jpg', 'Sunset palms', 230) . '</td>'
        . '<td class="col" valign="middle" style="padding:18px 20px;">'
        . '<div style="font-size:19px;line-height:1.2;font-weight:bold;color:' . NL_TEAL . ';text-transform:uppercase;">Know someone thinking about solar?</div>'
        . '<div style="font-size:14px;line-height:1.5;color:' . NL_INK . ';margin:8px 0 12px;">We love helping friends and family of our customers explore their energy options too.</div>'
        . nl_button('Refer a friend →', ($n['referral_url'] ?? '') !== '' ? $n['referral_url'] : NL_BASE . '/quote/', NL_ORANGE)
        . '</td></tr></table></td></tr>';
    }
    if (trim($n['cta_url'] ?? '') !== '') $rows .= '<tr><td align="center" style="padding:0 28px 20px;background:#ffffff;">' . nl_button($n['cta_label'] ?? '', $n['cta_url'], NL_ORANGE) . '</td></tr>';
    $rows .= nl_values([['&#9788;', 'Cleaner energy', 'Brighter futures'], ['&#8962;', 'Stronger communities', ''], ['&#8776;', 'A more sustainable California', '']]);

  } elseif ($t === 'welcome') {
    $rows .= nl_head('Solar · Savings · A brighter tomorrow');
    $rows .= '<tr><td align="center" bgcolor="#F2A65A" style="padding:34px 24px 30px;background:#F2A65A;background-image:linear-gradient(180deg,#F7C77E 0%,#EE9A4D 55%,#1F5F7A 100%);">'
      . '<div style="font-family:\'Brush Script MT\',\'Segoe Script\',cursive;font-size:38px;line-height:1;color:#ffffff;">' . nl_e(($n['title_small'] ?? '') !== '' ? $n['title_small'] : 'Welcome to') . '</div>'
      . '<div style="font-size:32px;line-height:1.15;font-weight:bold;color:#ffffff;text-transform:uppercase;margin-top:6px;">' . nl_e(($n['title'] ?? '') !== '' ? $n['title'] : 'Lumaria Energy') . '</div>'
      . '<div style="font-size:12px;letter-spacing:1.5px;text-transform:uppercase;color:#ffffff;font-weight:bold;margin-top:8px;">' . nl_e(($n['subtitle'] ?? '') !== '' ? $n['subtitle'] : 'Simple energy solutions for a brighter tomorrow') . '</div></td></tr>';
    $rows .= '<tr><td style="padding:26px 34px 10px;background:#ffffff;">'
      . (trim($n['greeting'] ?? '') !== '' ? '<p style="margin:0 0 14px;font-size:17px;color:' . NL_NAVY . ';">' . nl_e($n['greeting']) . '</p>' : '')
      . nl_rich($n['body'] ?? '', NL_INK) . '</td></tr>';
    $rows .= '<tr><td align="center" style="padding:4px 28px 22px;background:#ffffff;">' . nl_button(($n['cta_label'] ?? '') !== '' ? $n['cta_label'] : 'Contact our team →', ($n['cta_url'] ?? '') !== '' ? $n['cta_url'] : NL_TEL, NL_ORANGE) . '</td></tr>';
    $rows .= nl_values([['&#9788;', 'Cleaner energy', 'A brighter, more sustainable tomorrow.'], ['&#8962;', 'Lower bills', 'More savings for what matters most.'],
                        ['&#9831;', 'A healthier planet', 'Good for your home and your community.'], ['&#9825;', 'A team you can count on', 'We\'re here to help, always.']], '#F4FAFB');

  } else {   // alert, tip
    $rows .= nl_head($t === 'alert' ? 'Heads up' : 'Energy tip');
    if (trim($n['hero_image'] ?? '') !== '') $rows .= '<tr><td style="padding:0;">' . nl_photo($n['hero_image'], $n['headline'] ?? '') . '</td></tr>';
    $rows .= '<tr><td style="padding:24px 30px 10px;background:#ffffff;">'
      . '<div style="font-size:12px;letter-spacing:1.5px;text-transform:uppercase;color:' . NL_ORANGE . ';font-weight:bold;margin-bottom:6px;">' . nl_e($month) . '</div>'
      . '<div style="font-size:26px;line-height:1.2;font-weight:bold;color:' . NL_TEAL . ';margin-bottom:12px;">' . nl_e($n['headline'] ?? '') . '</div>'
      . ($t === 'alert' && trim($n['deadline'] ?? '') !== '' ? '<div style="margin:0 0 16px;padding:12px 14px;background:#FFF6DC;border-left:4px solid ' . NL_ORANGE . ';font-size:15px;font-weight:bold;color:' . NL_NAVY . ';">' . nl_e($n['deadline']) . '</div>' : '')
      . nl_rich($n['body'] ?? '', NL_INK) . '</td></tr>';
    if (trim($n['cta_url'] ?? '') !== '') $rows .= '<tr><td align="center" style="padding:4px 28px 22px;background:#ffffff;">' . nl_button($n['cta_label'] ?? '', $n['cta_url'], NL_ORANGE) . '</td></tr>';
  }

  // sign-off (all but welcome, which signs as the team in the footer)
  if ($t !== 'welcome' && trim($n['signoff'] ?? '') !== '')
    $rows .= '<tr><td style="padding:0 30px 24px;background:#ffffff;font-size:15px;line-height:1.5;color:' . NL_INK . ';">' . nl2br(nl_e($n['signoff'])) . '</td></tr>';

  $address = trim($settings['address'] ?? '');
  $unsub = $forZoho ? '<a href="$[LI:UNSUBSCRIBE]$" style="color:#ffffff;">Unsubscribe</a>' : '<span>Unsubscribe link appears in the email</span>';
  $thanks = $t === 'welcome' ? '<div style="font-family:\'Brush Script MT\',\'Segoe Script\',cursive;font-size:26px;color:#ffffff;">Thanks for choosing</div><div style="font-size:18px;font-weight:bold;color:#ffffff;margin-bottom:4px;">lumaria energy</div><div style="font-size:13px;color:#ffffff;margin-bottom:14px;">The Lumaria Energy Team</div>' : '';
  $footer = '<tr><td align="center" style="background:' . NL_TEAL . ';padding:24px 26px;">' . $thanks
    . '<img src="' . NL_BASE . '/img/brand/lumaria-energy-with-logo-on-dark.png" width="110" alt="lumaria energy" style="display:block;width:110px;height:auto;border:0;margin:0 auto 12px;">'
    . '<div style="font-size:13px;line-height:1.7;color:#e6f7f8;">'
    .   '<a href="' . NL_BASE . '/" style="color:#ffffff;">yourenergybest.com</a> · <a href="https://instagram.com/energy.best.ca" style="color:#ffffff;">Instagram</a> · <a href="' . NL_TEL . '" style="color:#ffffff;">' . NL_PHONE . '</a><br>'
    .   ($address !== '' ? nl_e($address) . '<br>' : '')
    .   'You\'re getting this because you\'ve worked with us or talked with us about your energy. ' . $unsub
    . '</div></td></tr>';

  $pre = nl_e($n['preheader'] ?? '');
  return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
    . '<title>' . nl_e($n['subject'] ?? 'Lumaria Energy') . '</title>'
    . '<style>@media only screen and (max-width:600px){.col{display:block!important;width:100%!important;max-width:100%!important;box-sizing:border-box}}</style></head>'
    . '<body style="margin:0;padding:0;background:#eef4f5;">'
    . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;">' . $pre . '&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;</div>'
    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eef4f5;"><tr><td align="center" style="padding:20px 10px;">'
    . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;font-family:Montserrat,Arial,Helvetica,sans-serif;">'
    . $rows . $footer
    . '</table></td></tr></table></body></html>';
}

/** Plain-text version of a newsletter — for the blog post and Luma. */
function nl_text_html($n) {
  $t = $n['template'] ?? 'monthly';
  $h = '';
  if ($t === 'monthly' || $t === 'update') {
    $h .= (trim($n['intro'] ?? '') ? $n['intro'] : '');
    foreach (nl_cards($n) as $s) {
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
