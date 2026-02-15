<?php
// become/includes/auth.php — Training portal auth (JSON-based for now, MySQL later)

session_start();

function getTrainContent() {
    static $data = null;
    if ($data === null) {
        $file = __DIR__ . '/../../train-content.json';
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
        } else {
            $data = ['manuals' => [], 'settings' => ['portal_password' => 'Become']];
        }
    }
    return $data;
}

function getPortalPassword() {
    $tc = getTrainContent();
    return $tc['settings']['portal_password'] ?? 'Become';
}

function isTrainAuthenticated() {
    return !empty($_SESSION['train_auth']) && $_SESSION['train_auth'] === true;
}

function requireTrainAuth() {
    if (!isTrainAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

function getTrainProgress() {
    // Returns array of completed module IDs from session (JSON mode)
    // Will switch to DB queries when MySQL is set up
    return $_SESSION['train_progress'] ?? [];
}

function markModuleComplete($moduleId) {
    if (!isset($_SESSION['train_progress'])) {
        $_SESSION['train_progress'] = [];
    }
    if (!in_array($moduleId, $_SESSION['train_progress'])) {
        $_SESSION['train_progress'][] = $moduleId;
    }
}

function getUserLevel() {
    // In JSON mode: calculate from completed modules
    // Each completed level's worth of modules bumps the level
    return $_SESSION['train_level'] ?? 0;
}

function setUserLevel($level) {
    $_SESSION['train_level'] = $level;
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
