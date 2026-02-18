<?php
/**
 * /become/includes/auth.php — SECURED Session Auth
 * 
 * Include at the top of every /become/ page that requires login.
 * Replaces the old plain-text password check.
 * 
 * Usage:
 *   require_once __DIR__ . '/includes/auth.php';
 *   // $current_user is now available with user data
 */

require_once __DIR__ . '/../../security-config.php';

/**
 * Require portal authentication — redirects to login if not authenticated
 */
function require_portal_auth() {
    if (empty($_SESSION['portal_user'])) {
        header('Location: /become/login.php');
        exit;
    }
    
    // Session timeout: 8 hours for training portal
    if (time() - ($_SESSION['portal_login_time'] ?? 0) > 28800) {
        session_destroy();
        header('Location: /become/login.php?expired=1');
        exit;
    }
}

/**
 * Get current portal user data from train-users.json
 */
function get_current_user() {
    if (empty($_SESSION['portal_user'])) return null;
    
    $users = load_train_users();
    foreach ($users as $user) {
        if (strtolower($user['username'] ?? '') === strtolower($_SESSION['portal_user'])) {
            return $user;
        }
    }
    
    // User was deleted from JSON — force logout
    session_destroy();
    header('Location: /become/login.php');
    exit;
}

/**
 * Check if current user has a specific role
 */
function has_role($required_role) {
    $role = $_SESSION['portal_role'] ?? 'rep';
    $hierarchy = ['rep' => 1, 'trainer' => 2, 'admin' => 3];
    return ($hierarchy[$role] ?? 0) >= ($hierarchy[$required_role] ?? 0);
}

// ─── Auto-run auth check when this file is included ───
require_portal_auth();
$current_user = get_current_user();
