<?php
/**
 * become/api/index.php — Portal API Router
 * Location: public_html/become/api/index.php
 * 
 * Routes:
 *   ?route=segments/{id}/complete  (POST) — Complete a segment
 *   ?route=segments/{id}/edit      (POST) — Edit segment content (leaders)
 *   ?route=modules/{id}/edit       (POST) — Edit module fields (leaders)
 */
session_start();
header('Content-Type: application/json');

// Auth required
if (empty($_SESSION['portal_user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ProgressionEngine.php';

$engine = new ProgressionEngine();
$userId = (int)$_SESSION['portal_user_id'];
$role   = $_SESSION['portal_role'] ?? 'rep';
$method = $_SERVER['REQUEST_METHOD'];
$route  = $_GET['route'] ?? '';

try {
    // ── Complete segment: POST ?route=segments/{id}/complete ──
    if (preg_match('#^segments/(\d+)/complete$#', $route, $m) && $method === 'POST') {
        $segId = (int)$m[1];
        $result = $engine->completeSegment($userId, $segId);
        echo json_encode($result);
        exit;
    }

    // ── Edit segment: POST ?route=segments/{id}/edit ──
    if (preg_match('#^segments/(\d+)/edit$#', $route, $m) && $method === 'POST') {
        if (!in_array($role, ['leader', 'admin'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Leader access required']);
            exit;
        }
        $segId = (int)$m[1];
        $input = json_decode(file_get_contents('php://input'), true);
        $html  = $input['content_html'] ?? '';
        $engine->updateSegment($segId, $html);
        echo json_encode(['success' => true]);
        exit;
    }

    // ── Edit module: POST ?route=modules/{id}/edit ──
    if (preg_match('#^modules/(\d+)/edit$#', $route, $m) && $method === 'POST') {
        if (!in_array($role, ['leader', 'admin'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Leader access required']);
            exit;
        }
        $modId = (int)$m[1];
        $input = json_decode(file_get_contents('php://input'), true);
        $engine->updateModule($modId, $input);
        echo json_encode(['success' => true]);
        exit;
    }

    // ── User stats: GET ?route=stats ──
    if ($route === 'stats' && $method === 'GET') {
        echo json_encode($engine->getUserStats($userId));
        exit;
    }

    // ── Next action: GET ?route=next ──
    if ($route === 'next' && $method === 'GET') {
        echo json_encode($engine->resolveNextAction($userId));
        exit;
    }

    // ── Content tree: GET ?route=content ──
    if ($route === 'content' && $method === 'GET') {
        echo json_encode($engine->getAccessibleContent($userId));
        exit;
    }

    http_response_code(404);
    echo json_encode(['error' => 'Unknown route: ' . $route]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
