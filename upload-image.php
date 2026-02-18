<?php
/**
 * upload-image.php — SECURED Image Upload Handler
 * 
 * Requires admin authentication + CSRF token.
 * Validates MIME type via finfo (not user-supplied).
 * Renames files to random hashes to prevent shell uploads.
 * Scans for embedded PHP code.
 */

require_once __DIR__ . '/security-config.php';
set_security_headers();

header('Content-Type: application/json');

// ─── Only POST ───
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST required']);
    exit;
}

// ─── Require admin auth ───
require_admin_auth();

// ─── CSRF check (from header since this is typically a form upload) ───
// For multipart forms, the CSRF token should be in a form field or header
$csrf_token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

// ─── Check for file ───
if (empty($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No image file uploaded']);
    exit;
}

$file = $_FILES['image'];

// ─── Validate the upload ───
$errors = validate_upload($file, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], 5);

if (!empty($errors)) {
    security_log('upload_rejected', ['errors' => $errors, 'original_name' => $file['name']]);
    http_response_code(400);
    echo json_encode(['error' => implode(' ', $errors)]);
    exit;
}

// ─── Generate safe filename ───
$safe_name = safe_filename($file['name']);
$upload_dir = __DIR__ . '/uploads/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$dest = $upload_dir . $safe_name;

// ─── Move uploaded file ───
if (!move_uploaded_file($file['tmp_name'], $dest)) {
    security_log('upload_move_failed', ['safe_name' => $safe_name]);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
    exit;
}

// ─── Strip EXIF data for privacy (if GD available) ───
$ext = pathinfo($safe_name, PATHINFO_EXTENSION);
if (in_array($ext, ['jpg', 'jpeg']) && function_exists('imagecreatefromjpeg')) {
    $img = imagecreatefromjpeg($dest);
    if ($img) {
        imagejpeg($img, $dest, 90);
        imagedestroy($img);
    }
}

security_log('upload_success', ['safe_name' => $safe_name, 'original_name' => $file['name']]);

echo json_encode([
    'success' => true,
    'url' => '/uploads/' . $safe_name,
    'filename' => $safe_name
]);
