<?php
/**
 * security-config.php — Centralized Security Configuration
 * yourenergybest.com
 * 
 * Place this file in public_html/ alongside config.php
 * Include it at the top of any PHP file that needs auth/security.
 * 
 * Usage: require_once __DIR__ . '/security-config.php';
 */

// ============================================================
// 1. SESSION CONFIGURATION
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    // Secure session settings BEFORE session_start()
    ini_set('session.cookie_httponly', 1);   // JS can't read session cookie
    ini_set('session.cookie_secure', 1);     // HTTPS only
    ini_set('session.cookie_samesite', 'Strict'); // Prevent CSRF via cookies
    ini_set('session.use_strict_mode', 1);   // Reject uninitialized session IDs
    ini_set('session.gc_maxlifetime', 3600); // 1 hour session lifetime
    session_start();
}

// ============================================================
// 2. SECURITY HEADERS (call early in any page)
// ============================================================
function set_security_headers() {
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    // Prevent MIME sniffing
    header('X-Content-Type-Options: nosniff');
    // XSS protection
    header('X-XSS-Protection: 1; mode=block');
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Content Security Policy — adjust as needed for your CDNs
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://unpkg.com https://www.paypal.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://unpkg.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https://*.cdninstagram.com https://tile.openstreetmap.org https://*.basemaps.cartocdn.com https://www.paypalobjects.com; connect-src 'self' https://graph.instagram.com https://www.googleapis.com; frame-src https://www.paypal.com https://www.youtube.com https://player.vimeo.com;");
    // Permissions policy
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

// ============================================================
// 3. ADMIN AUTHENTICATION
// ============================================================

// Admin credentials — CHANGE THESE
// Generate a new hash: php -r "echo password_hash('YOUR_NEW_PASSWORD', PASSWORD_BCRYPT);"
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', password_hash('beacons', PASSWORD_BCRYPT)); 
// ⚠️  IMPORTANT: After first deploy, replace the line above with a pre-computed hash:
// define('ADMIN_PASSWORD_HASH', '$2y$10$XXXXX...your_precomputed_hash...');
// Then delete the password_hash() call so the plain-text password isn't in source.

/**
 * Verify admin login credentials
 */
function admin_login($username, $password) {
    // Rate limit check first
    if (is_rate_limited('admin_login_' . get_client_ip(), 5, 900)) {
        return ['success' => false, 'error' => 'Too many login attempts. Try again in 15 minutes.'];
    }

    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true); // Prevent session fixation
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_login_time'] = time();
        $_SESSION['admin_ip'] = get_client_ip();
        clear_rate_limit('admin_login_' . get_client_ip());
        return ['success' => true];
    }

    record_rate_limit('admin_login_' . get_client_ip());
    return ['success' => false, 'error' => 'Invalid credentials.'];
}

/**
 * Check if current request has valid admin session
 */
function require_admin_auth() {
    if (!is_admin_authenticated()) {
        http_response_code(403);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
}

/**
 * Check if admin is authenticated (without blocking)
 */
function is_admin_authenticated() {
    if (empty($_SESSION['admin_authenticated'])) return false;
    
    // Session timeout: 2 hours
    if (time() - ($_SESSION['admin_login_time'] ?? 0) > 7200) {
        admin_logout();
        return false;
    }
    
    // IP binding — same IP that logged in
    if (($_SESSION['admin_ip'] ?? '') !== get_client_ip()) {
        admin_logout();
        return false;
    }
    
    return true;
}

/**
 * Destroy admin session
 */
function admin_logout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}


// ============================================================
// 4. CSRF TOKEN MANAGEMENT
// ============================================================

/**
 * Generate or retrieve CSRF token for current session
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate an HTML hidden input with the CSRF token
 */
function csrf_field() {
    return '<input type="hidden" name="_csrf_token" value="' . csrf_token() . '">';
}

/**
 * Validate CSRF token from request
 * Checks both POST body and X-CSRF-Token header
 */
function validate_csrf() {
    $token = $_POST['_csrf_token'] 
        ?? $_SERVER['HTTP_X_CSRF_TOKEN'] 
        ?? '';
    
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid or missing CSRF token']);
        exit;
    }
}


// ============================================================
// 5. RATE LIMITING (File-based)
// ============================================================
define('RATE_LIMIT_DIR', __DIR__ . '/rate-limits/');

/**
 * Check if an action is rate-limited
 * @param string $key Unique key (e.g., 'login_192.168.1.1')
 * @param int $max_attempts Max attempts allowed
 * @param int $window_seconds Time window in seconds
 */
function is_rate_limited($key, $max_attempts = 5, $window_seconds = 900) {
    $file = RATE_LIMIT_DIR . md5($key) . '.json';
    if (!file_exists($file)) return false;
    
    $data = json_decode(file_get_contents($file), true);
    if (!$data) return false;
    
    // Clean old attempts outside window
    $cutoff = time() - $window_seconds;
    $data['attempts'] = array_filter($data['attempts'] ?? [], function($t) use ($cutoff) {
        return $t > $cutoff;
    });
    
    return count($data['attempts']) >= $max_attempts;
}

/**
 * Record a rate-limited action attempt
 */
function record_rate_limit($key) {
    if (!is_dir(RATE_LIMIT_DIR)) {
        mkdir(RATE_LIMIT_DIR, 0700, true);
    }
    
    $file = RATE_LIMIT_DIR . md5($key) . '.json';
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    $data['attempts'][] = time();
    
    // Keep only last 20 attempts
    $data['attempts'] = array_slice($data['attempts'], -20);
    file_put_contents($file, json_encode($data), LOCK_EX);
}

/**
 * Clear rate limit for a key (e.g., after successful login)
 */
function clear_rate_limit($key) {
    $file = RATE_LIMIT_DIR . md5($key) . '.json';
    if (file_exists($file)) unlink($file);
}


// ============================================================
// 6. TRAINING PORTAL USER AUTH (with hashed passwords)
// ============================================================
define('TRAIN_USERS_FILE', __DIR__ . '/train-users.json');

/**
 * Hash a training portal password
 */
function hash_portal_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify training portal login
 */
function portal_login($username, $password) {
    // Rate limit
    if (is_rate_limited('portal_login_' . get_client_ip(), 5, 900)) {
        return ['success' => false, 'error' => 'Too many login attempts. Try again in 15 minutes.'];
    }
    
    $users = load_train_users();
    $user = null;
    
    foreach ($users as $u) {
        if (strtolower($u['username'] ?? '') === strtolower($username)) {
            $user = $u;
            break;
        }
    }
    
    if (!$user) {
        record_rate_limit('portal_login_' . get_client_ip());
        return ['success' => false, 'error' => 'Invalid username or password.'];
    }
    
    // Check password — support both hashed and legacy plain text during migration
    $stored_password = $user['password'] ?? $user['customPassword'] ?? '';
    $password_valid = false;
    
    if (str_starts_with($stored_password, '$2y$') || str_starts_with($stored_password, '$2b$')) {
        // Already hashed
        $password_valid = password_verify($password, $stored_password);
    } else {
        // Legacy plain text — verify and upgrade
        $password_valid = ($stored_password === $password);
        if ($password_valid) {
            // Auto-upgrade to hashed password
            upgrade_user_password($username, $password);
        }
    }
    
    // Also check shared portal password as fallback
    if (!$password_valid) {
        $train_content = json_decode(file_get_contents(__DIR__ . '/train-content.json'), true);
        $shared_password = $train_content['portalPassword'] ?? 'Become';
        
        if (str_starts_with($shared_password, '$2y$') || str_starts_with($shared_password, '$2b$')) {
            $password_valid = password_verify($password, $shared_password);
        } else {
            $password_valid = ($shared_password === $password);
        }
    }
    
    if (!$password_valid) {
        record_rate_limit('portal_login_' . get_client_ip());
        return ['success' => false, 'error' => 'Invalid username or password.'];
    }
    
    // Success
    session_regenerate_id(true);
    $_SESSION['portal_user'] = $user['username'];
    $_SESSION['portal_role'] = $user['role'] ?? 'rep';
    $_SESSION['portal_login_time'] = time();
    clear_rate_limit('portal_login_' . get_client_ip());
    
    return ['success' => true, 'user' => $user];
}

/**
 * Auto-upgrade a plain text password to hashed
 */
function upgrade_user_password($username, $plain_password) {
    $file = TRAIN_USERS_FILE;
    $users = load_train_users();
    
    foreach ($users as &$u) {
        if (strtolower($u['username'] ?? '') === strtolower($username)) {
            // Hash and store
            if (isset($u['customPassword'])) {
                $u['customPassword'] = hash_portal_password($plain_password);
            }
            if (isset($u['password'])) {
                $u['password'] = hash_portal_password($plain_password);
            }
            break;
        }
    }
    
    $data = json_decode(file_get_contents($file), true);
    $data['users'] = $users;
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

/**
 * Load training users from JSON
 */
function load_train_users() {
    $file = TRAIN_USERS_FILE;
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return $data['users'] ?? [];
}


// ============================================================
// 7. INPUT SANITIZATION
// ============================================================

/**
 * Sanitize a string for safe HTML output
 */
function clean($str) {
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize and validate email
 */
function clean_email($email) {
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

/**
 * Validate file upload (for upload-image.php)
 */
function validate_upload($file, $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], $max_size_mb = 5) {
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Upload error code: ' . $file['error'];
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > $max_size_mb * 1024 * 1024) {
        $errors[] = "File exceeds {$max_size_mb}MB limit.";
    }
    
    // Check MIME type via finfo (not user-supplied type)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $real_type = $finfo->file($file['tmp_name']);
    
    if (!in_array($real_type, $allowed_types)) {
        $errors[] = "File type '{$real_type}' not allowed.";
    }
    
    // Check for PHP in file content (prevent polyglot uploads)
    $content = file_get_contents($file['tmp_name']);
    if (preg_match('/<\?php|<\?=|<script\b/i', $content)) {
        $errors[] = 'File contains suspicious content.';
    }
    
    return $errors;
}

/**
 * Generate a safe filename for uploads
 */
function safe_filename($original_name) {
    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($ext, $allowed_ext)) {
        $ext = 'jpg'; // Default
    }
    
    return bin2hex(random_bytes(16)) . '.' . $ext;
}


// ============================================================
// 8. UTILITY FUNCTIONS
// ============================================================

/**
 * Get client IP (respects proxies)
 */
function get_client_ip() {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            // X-Forwarded-For can have multiple IPs — take the first
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

/**
 * Log security events
 */
function security_log($event, $details = []) {
    $log_dir = __DIR__ . '/logs/';
    if (!is_dir($log_dir)) mkdir($log_dir, 0700, true);
    
    $entry = [
        'time' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => get_client_ip(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'details' => $details
    ];
    
    file_put_contents(
        $log_dir . 'security.log',
        json_encode($entry) . "\n",
        FILE_APPEND | LOCK_EX
    );
}
