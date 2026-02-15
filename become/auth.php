<?php
// become/includes/auth.php — Username-based auth with admin-managed users

session_start();

function getTrainContent() {
    static $data = null;
    if ($data === null) {
        $file = __DIR__ . '/../../train-content.json';
        $data = file_exists($file) ? json_decode(file_get_contents($file), true) : ['manuals' => [], 'settings' => []];
    }
    return $data;
}

function getTrainUsers() {
    static $data = null;
    if ($data === null) {
        $file = __DIR__ . '/../../train-users.json';
        $data = file_exists($file) ? json_decode(file_get_contents($file), true) : ['users' => []];
    }
    return $data;
}

function findUser($username) {
    $data = getTrainUsers();
    foreach ($data['users'] ?? [] as $u) {
        if (strtolower($u['username']) === strtolower($username)) return $u;
    }
    return null;
}

function getPortalPassword() {
    $tc = getTrainContent();
    return $tc['settings']['portal_password'] ?? 'Become';
}

function checkUserPassword($user, $password) {
    // If user has a personal password set, use that
    if (!empty($user['password'])) {
        return $password === $user['password'];
    }
    // Otherwise fall back to the shared portal password
    return $password === getPortalPassword();
}

function isTrainAuthenticated() {
    return !empty($_SESSION['train_user_id']);
}

function requireTrainAuth() {
    if (!isTrainAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

function currentTrainUser() {
    return [
        'id'              => $_SESSION['train_user_id'] ?? null,
        'username'        => $_SESSION['train_username'] ?? null,
        'first_name'      => $_SESSION['train_first_name'] ?? '',
        'last_name'       => $_SESSION['train_last_name'] ?? '',
        'role'            => $_SESSION['train_role'] ?? 'rep',
        'unlocked_level'  => $_SESSION['train_unlocked_level'] ?? 0,
    ];
}

function refreshUserFromFile() {
    // Re-read user data from JSON in case admin changed their level
    if (!empty($_SESSION['train_username'])) {
        $user = findUser($_SESSION['train_username']);
        if ($user) {
            $_SESSION['train_unlocked_level'] = $user['unlocked_level'] ?? 0;
            $_SESSION['train_first_name'] = $user['first_name'] ?? '';
            $_SESSION['train_last_name'] = $user['last_name'] ?? '';
            $_SESSION['train_role'] = $user['role'] ?? 'rep';
        }
    }
}

function getTrainProgress() {
    // Per-user progress stored in session (JSON mode)
    $uid = $_SESSION['train_user_id'] ?? 'anon';
    return $_SESSION['train_progress_' . $uid] ?? [];
}

function markModuleComplete($moduleId) {
    $uid = $_SESSION['train_user_id'] ?? 'anon';
    $key = 'train_progress_' . $uid;
    if (!isset($_SESSION[$key])) $_SESSION[$key] = [];
    if (!in_array($moduleId, $_SESSION[$key])) {
        $_SESSION[$key][] = $moduleId;
    }
}

function getUserLevel() {
    return $_SESSION['train_unlocked_level'] ?? 0;
}

function formatLevel($levelInt) {
    if ($levelInt === 0) return '0';
    $major = intdiv($levelInt, 10);
    $minor = $levelInt % 10;
    return $minor > 0 ? "{$major}.{$minor}" : "{$major}.0";
}

function esc($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
