<?php
/**
 * become/includes/auth.php — Portal Auth (MySQL-backed)
 * Location: public_html/become/includes/auth.php
 *
 * Checks session, loads user from MySQL, exposes role + hierarchy helpers.
 * Sets $_SESSION['portal_user_id'], ['portal_role'], ['portal_full_access'].
 */

if (session_status() === PHP_SESSION_NONE) session_start();

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

function get_portal_user() {
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

function is_leader()    { return has_role('leader'); }
function is_trainer()   { return has_role('trainer'); }
function portal_role()  { return $_SESSION['portal_role'] ?? 'rep'; }
function is_engineer()  { return !empty($_SESSION['portal_full_access']); }

/**
 * Can the current user manage (add/view) the target user?
 *  - admin/leader: everyone
 *  - trainer: only their own direct reports (parent_id = trainer's id), and themselves
 *  - rep/engineer: only themselves
 */
function portal_can_manage($targetUser) {
    $me   = (int)($_SESSION['portal_user_id'] ?? 0);
    $role = portal_role();
    if ($role === 'admin' || $role === 'leader') return true;
    if (!is_array($targetUser)) return false;
    if ((int)$targetUser['id'] === $me) return true;
    if ($role === 'trainer') return (int)($targetUser['parent_id'] ?? 0) === $me;
    return false;
}

require_portal_auth();
$current_user = get_portal_user();
if (!$current_user) {
    session_destroy();
    header('Location: /become/login.php');
    exit;
}

// Keep session capability flags fresh from the DB row.
$_SESSION['portal_full_access'] = !empty($current_user['full_access']) ? 1 : 0;
$_SESSION['portal_role']        = $current_user['role'] ?? ($_SESSION['portal_role'] ?? 'rep');
