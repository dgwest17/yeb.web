<?php
/**
 * newsletters.php — Admin → Newsletters talking to Zoho Campaigns. Admin session
 * required; anything that changes something also needs the CSRF token.
 *
 *   GET  ?action=status            → Zoho state for every newsletter + whether Zoho Campaigns is reachable
 *   GET  ?action=lists             → Zoho Campaigns mailing lists [{ key, name, contacts }]
 *   POST { action:'preview', id }  → emails the newsletter to the signed-in admin only
 *   POST { action:'create', id }   → creates the campaign in Zoho (Zoho imports the email from view.php)
 *   POST { action:'send', id }     → sends it now (Zoho may hold it for review first)
 *   POST { action:'refresh', id }  → status / opens / clicks from Zoho
 *
 * Content is saved by the admin through save.php (newsletters.json) before any of these run.
 */
require_once __DIR__ . '/security-config.php';
require_once __DIR__ . '/newsletter-lib.php';
header('Content-Type: application/json');
require_admin_auth();

function out($a, $code = 200) { http_response_code($code); echo json_encode($a); exit; }

$action = $_GET['action'] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  validate_csrf();
  $in = json_decode(file_get_contents('php://input'), true) ?: [];
  $action = $in['action'] ?? '';
}

try {
  if ($action === 'status') out(['ok' => true, 'zoho' => nl_zoho_state()]);

  if ($action === 'lists') {
    $j = nl_zoho('getmailinglists', ['sort' => 'asc', 'fromindex' => 1, 'range' => 50], false);
    $lists = array_map(fn($l) => ['key' => $l['listkey'] ?? '', 'name' => $l['listname'] ?? '', 'contacts' => (int)($l['noofcontacts'] ?? 0)],
                       $j['list_of_details'] ?? $j['lists'] ?? []);
    out(['ok' => true, 'lists' => $lists]);
  }

  $id = (string)($in['id'] ?? '');
  $n = nl_find($id);
  if (!$n) out(['ok' => false, 'error' => 'Publish the newsletter first (it isn\'t saved yet).'], 404);
  $s = nl_data()['settings'];

  switch ($action) {
    case 'preview':
      $to = $_SESSION['admin_email'] ?? '';
      if (!filter_var($to, FILTER_VALIDATE_EMAIL)) out(['ok' => false, 'error' => 'No email on your admin session — sign in again.']);
      $html = nl_render($n, $s, false);
      $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: Your Energy Best <noreply@yourenergybest.com>\r\n";
      $ok = @mail($to, '[Preview] ' . ($n['subject'] ?? 'Newsletter'), $html, $headers);
      out($ok ? ['ok' => true, 'to' => $to] : ['ok' => false, 'error' => 'The server could not send the preview email.']);

    case 'create':
      foreach (['from_email' => 'From email', 'list_key' => 'Mailing list', 'address' => 'Mailing address'] as $k => $label)
        if (trim($s[$k] ?? '') === '') out(['ok' => false, 'error' => "Fill in \"$label\" in Newsletter settings first."]);
      if (trim($n['subject'] ?? '') === '') out(['ok' => false, 'error' => 'Add a subject line first.']);
      $params = [
        'campaignname' => substr(($n['name'] ?: $n['subject']) . ' · ' . date('M j, Y'), 0, 100),
        'from_email'   => $s['from_email'],
        'from_name'    => $s['from_name'] ?: 'David West',
        'subject'      => $n['subject'],
        'content_url'  => nl_view_url($n) . '&zoho=1',
        'list_details' => json_encode([$s['list_key'] => []]),
      ];
      if (trim($s['reply_to'] ?? '') !== '') $params['reply_to'] = $s['reply_to'];
      if (trim($s['topic_id'] ?? '') !== '') $params['topicId'] = $s['topic_id'];
      $j = nl_zoho('createCampaign', $params);
      $key = $j['campaignKey'] ?? $j['campaignkey'] ?? null;
      if (!$key) out(['ok' => false, 'error' => 'Zoho said: ' . ($j['message'] ?? json_encode($j))]);
      security_log('newsletter_created_in_zoho', ['id' => $id]);
      out(['ok' => true, 'zoho' => nl_zoho_save($id, ['campaignKey' => (string)$key, 'list' => $s['list_key'], 'created' => date('c'),
                                                    'contentHash' => nl_hash($n), 'status' => 'draft', 'message' => null])]);

    case 'send':
      $z = nl_zoho_state()[$id] ?? [];
      if (empty($z['campaignKey'])) out(['ok' => false, 'error' => 'Create it in Zoho first.']);
      if (($z['contentHash'] ?? '') !== nl_hash($n)) out(['ok' => false, 'error' => 'It changed after it went to Zoho. Create it in Zoho again so the latest version is what sends.']);
      $j = nl_zoho('sendcampaign', ['campaignkey' => $z['campaignKey']]);
      $r = $j['response'] ?? $j;
      $code = (string)($r['code'] ?? '');
      if ($code === '200' || $code === '0') {
        security_log('newsletter_sent', ['id' => $id]);
        out(['ok' => true, 'zoho' => nl_zoho_save($id, ['status' => 'sending', 'sent' => date('c'), 'message' => null])]);
      }
      $review = in_array($code, ['6611', '6609', '6608'], true);
      $msg = $review ? 'Zoho is reviewing this campaign before it can go out (normal for new accounts). Check back and press Send again once it\'s approved.'
                     : 'Zoho said: ' . ($r['message'] ?? json_encode($j));
      out(['ok' => false, 'error' => $msg, 'zoho' => nl_zoho_save($id, ['status' => $review ? 'review' : 'error', 'message' => $msg])]);

    case 'refresh':
      $z = nl_zoho_state()[$id] ?? [];
      if (empty($z['campaignKey'])) out(['ok' => true, 'zoho' => $z]);
      $patch = [];
      $rc = nl_zoho('recentcampaigns', ['status' => 'all', 'range' => 50, 'sort' => 'desc'], false);
      foreach ($rc['recent_campaigns'] ?? [] as $c) {
        if ((string)($c['campaign_key'] ?? '') === (string)$z['campaignKey']) { $patch['status'] = strtolower($c['campaign_status'] ?? ''); break; }
      }
      if (in_array($patch['status'] ?? $z['status'] ?? '', ['sent', 'sending', 'inprogress'], true)) {
        $rep = nl_zoho('campaignreports', ['campaignkey' => $z['campaignKey']], false);
        $c = $rep['campaign-reports'][0] ?? $rep['campaign-reports'] ?? [];
        if ($c) $patch['report'] = ['sent' => (int)($c['emails_sent_count'] ?? 0), 'delivered' => (int)($c['delivered_count'] ?? 0),
          'opens' => (int)($c['opens_count'] ?? 0), 'open_pct' => $c['open_percent'] ?? null, 'clicks' => (int)($c['unique_clicks_count'] ?? 0),
          'unsubs' => (int)($c['unsub_count'] ?? 0), 'bounces' => (int)($c['bounces_count'] ?? 0), 'at' => date('c')];
      }
      out(['ok' => true, 'zoho' => nl_zoho_save($id, $patch)]);
  }
  out(['ok' => false, 'error' => 'Unknown action'], 400);
} catch (Throwable $e) {
  out(['ok' => false, 'error' => $e->getMessage()]);
}
