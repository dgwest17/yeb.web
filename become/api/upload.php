<?php
/**
 * become/api/upload.php — File Upload Handler
 * Location: public_html/become/api/upload.php
 * 
 * Accepts image and PDF uploads, saves to /become/uploads/
 * Returns the public URL for embedding in content.
 * Requires leader or admin session.
 */
session_start();
header('Content-Type: application/json');

$role = $_SESSION['portal_role'] ?? '';
if (!in_array($role, ['leader', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

if (empty($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];

// Validate
$maxSize = 20 * 1024 * 1024; // 20MB
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large (max 20MB)']);
    exit;
}

$allowed = [
    // Images
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    'image/svg+xml' => 'svg',
    // Documents
    'application/pdf' => 'pdf',
    // Video (for small clips)
    'video/mp4' => 'mp4',
    'video/webm' => 'webm',
];

$mime = mime_content_type($file['tmp_name']);
if (!isset($allowed[$mime])) {
    http_response_code(400);
    echo json_encode(['error' => 'File type not allowed: ' . $mime, 'allowed' => array_keys($allowed)]);
    exit;
}

$ext = $allowed[$mime];
$uploadDir = __DIR__ . '/../uploads';

// Create upload directory if needed
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$basename = pathinfo($file['name'], PATHINFO_FILENAME);
$basename = preg_replace('/[^a-zA-Z0-9_-]/', '', $basename); // sanitize
$basename = substr($basename, 0, 50); // limit length
$filename = $basename . '-' . substr(uniqid(), -6) . '.' . $ext;
$filepath = $uploadDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
    exit;
}

// Build public URL
$url = '/become/uploads/' . $filename;

// Determine type category
$isImage = strpos($mime, 'image/') === 0;
$isPDF = $mime === 'application/pdf';
$isVideo = strpos($mime, 'video/') === 0;

echo json_encode([
    'success' => true,
    'url' => $url,
    'filename' => $filename,
    'type' => $isImage ? 'image' : ($isPDF ? 'pdf' : ($isVideo ? 'video' : 'file')),
    'size' => $file['size'],
    'mime' => $mime,
]);
