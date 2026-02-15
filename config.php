<?php
// config.php — Database + App Configuration
// ═══════════════════════════════════════════
// IMPORTANT: Fill in your Hostinger MySQL credentials below.
// This file should NOT be committed to GitHub.
// Add "config.php" to your .gitignore file.

// ─── Database ───
define('DB_HOST', 'localhost');          // Hostinger usually uses localhost
define('DB_NAME', 'your_db_name');       // e.g. u123456789_yeb
define('DB_USER', 'your_db_user');       // e.g. u123456789_admin
define('DB_PASS', 'your_db_password');   // The password you set in Hostinger

// ─── App Settings ───
define('ADMIN_PASSWORD', 'beacons');     // Existing admin password
define('SITE_URL', 'https://yourenergybest.com');

// ─── Session Config ───
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);

// ─── PDO Connection ───
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed']));
        }
    }
    return $pdo;
}

// ─── CSRF Helper ───
function generateCSRF() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRF($token) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ─── Auth Helper ───
function requireAuth($minRole = 'rep') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    $roles = ['rep' => 1, 'trainer' => 2, 'admin' => 3];
    if (($roles[$_SESSION['user_role']] ?? 0) < ($roles[$minRole] ?? 0)) {
        http_response_code(403);
        die('Access denied');
    }
}

function currentUser() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'role' => $_SESSION['user_role'] ?? null,
        'unlocked_level' => $_SESSION['unlocked_level'] ?? 0,
    ];
}

// ─── Level Display Helper ───
function formatLevel($levelInt) {
    if ($levelInt === 0) return 'Level 0';
    $major = intdiv($levelInt, 10);
    $minor = $levelInt % 10;
    return $minor > 0 ? "Level {$major}.{$minor}" : "Level {$major}.0";
}

function esc($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
