<?php
/**
 * zoho-health.php — is the website's Zoho connection working?
 * Says only yes/no and Zoho's error code for each step: no records, tokens or ids.
 * 200 when leads can be saved, 503 when not (an uptime monitor can watch it).
 * Cached 10 minutes, because Zoho limits how often a token can be refreshed.
 */
header('Content-Type: application/json');
header('Cache-Control: no-store');
$cacheFile = __DIR__ . '/.zoho_health.json';
$out = json_decode(@file_get_contents($cacheFile) ?: '', true);

if (!is_array($out) || time() - ($out['checked'] ?? 0) > 600) {
  $cfg = file_exists(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];
  $refresh = trim(@file_get_contents(__DIR__ . '/.zoho_refresh_token') ?: '');
  $call = function ($url, $headers = [], $body = null) {
    $ch = curl_init($url);
    $o = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => $headers];
    if ($body !== null) { $o[CURLOPT_POST] = true; $o[CURLOPT_POSTFIELDS] = $body; }
    curl_setopt_array($ch, $o);
    $raw = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$code, json_decode($raw ?: '', true) ?: []];
  };
  $word = fn($v) => substr(preg_replace('/[^A-Za-z_ ]/', '', is_string($v) ? $v : ''), 0, 60);
  $out = ['ok' => false, 'connected' => $refresh !== '', 'client_configured' => !empty($cfg['zoho_client_id']) && !empty($cfg['zoho_client_secret'])];
  if ($out['connected'] && $out['client_configured']) {
    [$code, $j] = $call('https://accounts.zoho.com/oauth/v2/token', [], http_build_query(['refresh_token' => $refresh,
      'client_id' => $cfg['zoho_client_id'], 'client_secret' => $cfg['zoho_client_secret'], 'grant_type' => 'refresh_token']));
    $tok = $j['access_token'] ?? '';
    $out['token'] = ['ok' => $tok !== '', 'http' => $code, 'error' => $word($j['error'] ?? '')];
    if ($tok !== '') {
      $h = ['Authorization: Zoho-oauthtoken ' . $tok];
      [$code, $j] = $call('https://www.zohoapis.com/crm/v2/Leads?per_page=1&fields=id', $h);
      $out['leads'] = ['ok' => $code === 200 || $code === 204, 'http' => $code, 'error' => $word($j['code'] ?? '')];
      [$code, $j] = $call('https://www.zohoapis.com/crm/v2/coql', array_merge($h, ['Content-Type: application/json']),
        json_encode(['select_query' => 'select id from Contacts where id is not null limit 1']));
      $out['map_query'] = ['ok' => $code === 200 || $code === 204, 'http' => $code, 'error' => $word($j['code'] ?? '')];
      $out['ok'] = $out['leads']['ok'];
    }
  }
  $out['checked'] = time();
  @file_put_contents($cacheFile, json_encode($out), LOCK_EX);
}
http_response_code(!empty($out['ok']) ? 200 : 503);
echo json_encode($out);
