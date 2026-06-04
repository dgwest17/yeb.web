<?php
/**
 * become/includes/ZohoClient.php — Minimal Zoho CRM API client
 * Location: public_html/become/includes/ZohoClient.php
 *
 * Reuses your CRM OAuth credentials (the same Zoho app behind zoho.php).
 * Reads from public_html/config.php:
 *   'zoho_client_id'      => '...'
 *   'zoho_client_secret'  => '...'
 *   'zoho_refresh_token'  => '...'                       // must include CRM custom-module READ scope
 *   'zoho_accounts_domain'=> 'https://accounts.zoho.com' // .eu/.in/.com.au if your DC differs
 *   'zoho_api_domain'     => 'https://www.zohoapis.com'  // matches your DC
 *
 * The access token is cached on disk so we only refresh when it expires.
 */

class ZohoClient {
    private $cfg;
    private $cacheFile;

    public function __construct() {
        $path = __DIR__ . '/../../config.php';
        $this->cfg = file_exists($path) ? (require $path) : [];
        $this->cacheFile = sys_get_temp_dir() . '/yeb_zoho_token.json';
    }

    private function cfg($k, $default = null) {
        return $this->cfg[$k] ?? $default;
    }

    public function isConfigured() {
        return $this->cfg('zoho_client_id') && $this->cfg('zoho_client_secret') && $this->cfg('zoho_refresh_token');
    }

    /** Returns a valid access token, refreshing (and caching) only when needed. */
    public function getAccessToken() {
        // Try cache first.
        if (is_readable($this->cacheFile)) {
            $c = json_decode(@file_get_contents($this->cacheFile), true);
            if (is_array($c) && !empty($c['access_token']) && ($c['expires_at'] ?? 0) > time() + 60) {
                return $c['access_token'];
            }
        }

        $accounts = rtrim($this->cfg('zoho_accounts_domain', 'https://accounts.zoho.com'), '/');
        $params = http_build_query([
            'refresh_token' => $this->cfg('zoho_refresh_token'),
            'client_id'     => $this->cfg('zoho_client_id'),
            'client_secret' => $this->cfg('zoho_client_secret'),
            'grant_type'    => 'refresh_token',
        ]);

        $resp = $this->httpPost($accounts . '/oauth/v2/token', $params);
        $data = json_decode($resp, true);
        if (empty($data['access_token'])) {
            throw new Exception('Zoho token refresh failed: ' . ($data['error'] ?? substr((string)$resp, 0, 200)));
        }

        $token  = $data['access_token'];
        $expSec = (int)($data['expires_in'] ?? 3600);
        @file_put_contents($this->cacheFile, json_encode([
            'access_token' => $token,
            'expires_at'   => time() + $expSec,
        ]));
        @chmod($this->cacheFile, 0600);
        return $token;
    }

    /** GET {api_domain}/crm/v3/{path}?query . Returns decoded array. */
    public function apiGet($path, array $query = []) {
        $api = rtrim($this->cfg('zoho_api_domain', 'https://www.zohoapis.com'), '/');
        $url = $api . '/crm/v3/' . ltrim($path, '/');
        if ($query) $url .= '?' . http_build_query($query);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Zoho-oauthtoken ' . $this->getAccessToken()],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false)       throw new Exception('Zoho API curl error: ' . $err);
        if ($code === 204 || $body === '') return ['data' => []];   // No Content
        $data = json_decode($body, true);
        if ($code >= 400) throw new Exception('Zoho API ' . $code . ': ' . substr($body, 0, 300));
        return is_array($data) ? $data : ['data' => []];
    }

    /**
     * Search records in a module by a criteria string.
     * Criteria example: (Recruit_Status:equals:Hired)
     * Returns the array of record rows (may be empty).
     */
    public function searchRecords($module, $criteria, $fields = null, $perPage = 200) {
        $q = ['criteria' => $criteria, 'per_page' => $perPage];
        if ($fields) $q['fields'] = $fields;
        $res = $this->apiGet(rawurlencode($module) . '/search', $q);
        return $res['data'] ?? [];
    }

    /** Fetch all records from a module, paging through results. Returns array of rows. */
    public function getAllRecords($module, $fields = null, $maxPages = 25, $perPage = 200) {
        $rows = []; $page = 1; $more = false;
        do {
            $q = ['per_page' => $perPage, 'page' => $page];
            if ($fields) $q['fields'] = $fields;
            $res = $this->apiGet(rawurlencode($module), $q);
            foreach (($res['data'] ?? []) as $b) $rows[] = $b;
            $more = !empty($res['info']['more_records']);
            $page++;
        } while ($more && $page <= $maxPages);
        return $rows;
    }

    /** Update records: $records = [ ['id'=>'...','Field'=>val], ... ]. Chunks by 100. Returns count updated. */
    public function updateRecords($module, array $records) {
        $updated = 0;
        foreach (array_chunk($records, 100) as $chunk) {
            $res = $this->apiPut(rawurlencode($module), json_encode(['data' => array_values($chunk)]));
            foreach (($res['data'] ?? []) as $row) {
                if (($row['code'] ?? '') === 'SUCCESS') $updated++;
            }
        }
        return $updated;
    }

    /** PUT JSON to /crm/v3/{path}. Returns decoded array. */
    public function apiPut($path, $jsonBody) {
        $api = rtrim($this->cfg('zoho_api_domain', 'https://www.zohoapis.com'), '/');
        $url = $api . '/crm/v3/' . ltrim($path, '/');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => $jsonBody,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Zoho-oauthtoken ' . $this->getAccessToken(),
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($body === false) throw new Exception('Zoho API curl error: ' . $err);
        $data = json_decode($body, true);
        if ($code >= 400) throw new Exception('Zoho API ' . $code . ': ' . substr($body, 0, 300));
        return is_array($data) ? $data : [];
    }

    private function httpPost($url, $body) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($resp === false) throw new Exception('Zoho token curl error: ' . $err);
        return $resp;
    }
}
