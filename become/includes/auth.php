<?php
/**
 * become/includes/auth.php — Portal Auth (MySQL-backed)
 * Location: public_html/become/includes/auth.php
 * 
 * Checks session, loads user from MySQL.
 * Sets $_SESSION['portal_user_id'] and $_SESSION['portal_role'] for the API.
 */

session_start();

function require_portal_auth() {
    if (empty($_SESSION['portal_user_id'])) {
        if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
        header('Location: /become/login.php');
        exit;
    }
    // 8-hour timeout
    if (time() - ($_SESSION['portal_login_time'] ?? 0) > 28800) {
        session_destroy();
        header('Location: /become/login.php?expired=1');
        exit;
    }
}

function get_current_user() {
    if (empty($_SESSION['portal_user_id'])) return null;
    try {
        require_once __DIR__ . '/db.php';
        $db = Database::getInstance();
        $s = $db->prepare("SELECT * FROM training_users WHERE id = ? AND is_active = 1");
        $s->execute([$_SESSION['portal_user_id']]);
        return $s->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function has_role($required) {
    $hierarchy = ['rep'=>1, 'trainer'=>2, 'leader'=>3, 'admin'=>4];
    $role = $_SESSION['portal_role'] ?? 'rep';
    return ($hierarchy[$role] ?? 0) >= ($hierarchy[$required] ?? 0);
}

function is_leader() { return has_role('leader'); }

require_portal_auth();
$current_user = get_current_user();
if (!$current_user) {
    session_destroy();
    header('Location: /become/login.php');
    exit;
}
