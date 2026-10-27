<?php
/**
 * admin-auth.php — Admin Authentication Endpoint
 * 
 * Handles login, logout, session check, and CSRF token retrieval
 * for the admin panel. Place in public_html/.
 * 
 * Endpoints (all POST except session check):
 *   POST ?action=login     { username, password }
 *   POST ?action=logout
 *   GET  ?action=check      → { authenticated: bool, csrf_token: string }
 *   GET  ?action=csrf        → { csrf_token: string }
 */

require_once __DIR__ . '/security-config.php';
set_security_headers();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ─── LOGIN ───
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'POST required']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Username and password required']);
            exit;
        }

        $result = admin_login($username, $password);

        if ($result['success']) {
            security_log('admin_login_success', ['username' => $username]);
            echo json_encode([
                'success' => true,
                'csrf_token' => csrf_token()
            ]);
        } else {
            security_log('admin_login_failed', ['username' => $username]);
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => $result['error']
            ]);
        }
        break;

    // ─── LOGOUT ───
    case 'logout':
        security_log('admin_logout');
        admin_logout();
        echo json_encode(['success' => true]);
        break;

    // ─── SESSION CHECK ───
    case 'check':
        echo json_encode([
            'authenticated' => is_admin_authenticated(),
            'csrf_token' => is_admin_authenticated() ? csrf_token() : null
        ]);
        break;

    // ─── GET CSRF TOKEN (for authenticated sessions) ───
    case 'csrf':
        if (!is_admin_authenticated()) {
            http_response_code(403);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }
        echo json_encode(['csrf_token' => csrf_token()]);
        break;

    // ─── LOGIN TROUBLESHOOTING (no secrets) ───
    // GET ?action=status[&email=...] → is login set up, which Supabase project,
    // is this email allowed, and how long until this IP may try again.
    case 'status':
        $email = strtolower(trim($_GET['email'] ?? ''));
        $rl = RATE_LIMIT_DIR . md5('admin_login_' . get_client_ip()) . '.json';
        $tries = file_exists($rl) ? array_values(array_filter((json_decode(file_get_contents($rl), true)['attempts'] ?? []), fn($t) => $t > time() - 900)) : [];
        echo json_encode([
            'supabase_configured' => SUPABASE_URL !== '' && SUPABASE_ANON_KEY !== '',
            'supabase_project'    => parse_url(SUPABASE_URL, PHP_URL_HOST) ?: null,
            'allowlist_size'      => count($GLOBALS['ADMIN_ALLOWED_EMAILS']),
            'email_allowed'       => $email === '' ? null : in_array($email, $GLOBALS['ADMIN_ALLOWED_EMAILS'], true),
            'failed_tries_15min'  => count($tries),
            'locked_for_seconds'  => count($tries) >= 5 ? max(0, min($tries) + 900 - time()) : 0,
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
