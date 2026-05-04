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
        $db = Database::getInstance();

        // Check if this is a passoff segment (graceful - works even without passoff table)
        try {
            $s = $db->prepare("SELECT segment_type FROM segments WHERE id=?");
            $s->execute([$segId]);
            $seg = $s->fetch();

            if ($seg && ($seg['segment_type'] ?? 'lesson') === 'passoff') {
                try {
                    $s = $db->prepare("SELECT status FROM passoff_requests WHERE user_id=? AND segment_id=? ORDER BY id DESC LIMIT 1");
                    $s->execute([$userId, $segId]);
                    $req = $s->fetch();

                    if ($req && $req['status'] === 'passed') {
                        $result = $engine->completeSegment($userId, $segId);
                        echo json_encode($result);
                        exit;
                    } elseif ($req && $req['status'] === 'pending') {
                        echo json_encode(['passoff_pending' => true, 'message' => 'Waiting for leader approval']);
                        exit;
                    } else {
                        echo json_encode(['needs_passoff' => true, 'message' => 'This segment requires a leader pass-off']);
                        exit;
                    }
                } catch (Exception $e) {
                    // passoff_requests table may not exist — treat as regular segment
                }
            }
        } catch (Exception $e) {
            // segment_type column may not exist — treat as regular lesson
        }

        // Regular lesson completion
        $result = $engine->completeSegment($userId, $segId);
        echo json_encode($result);
        exit;
    }

    // ── Request pass-off: POST ?route=segments/{id}/request-passoff ──
    if (preg_match('#^segments/(\d+)/request-passoff$#', $route, $m) && $method === 'POST') {
        $segId = (int)$m[1];
        $db = Database::getInstance();
        // Insert or update request
        $s = $db->prepare("INSERT INTO passoff_requests (user_id, segment_id, status) VALUES (?, ?, 'pending') ON DUPLICATE KEY UPDATE status='pending', requested_at=NOW()");
        $s->execute([$userId, $segId]);
        echo json_encode(['success' => true, 'message' => 'Pass-off requested! A leader will review.']);
        exit;
    }

    // ── Approve pass-off: POST ?route=passoff/{id}/approve (leader only) ──
    if (preg_match('#^passoff/(\d+)/approve$#', $route, $m) && $method === 'POST') {
        if (!in_array($role, ['leader', 'admin'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Leader access required']);
            exit;
        }
        $requestId = (int)$m[1];
        $db = Database::getInstance();

        // Get the request
        $s = $db->prepare("SELECT * FROM passoff_requests WHERE id=?");
        $s->execute([$requestId]);
        $req = $s->fetch();
        if (!$req) { echo json_encode(['error' => 'Request not found']); exit; }

        // Mark as passed
        $s = $db->prepare("UPDATE passoff_requests SET status='passed', reviewed_by=?, reviewed_at=NOW() WHERE id=?");
        $s->execute([$userId, $requestId]);

        // Complete the segment for the user
        $result = $engine->completeSegment($req['user_id'], $req['segment_id']);
        echo json_encode(['success' => true, 'completed' => $result]);
        exit;
    }

    // ── Get pending pass-offs: GET ?route=passoffs (leader only) ──
    if ($route === 'passoffs' && $method === 'GET') {
        if (!in_array($role, ['leader', 'admin'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Leader access required']);
            exit;
        }
        try {
            $db = Database::getInstance();
            $s = $db->prepare("SELECT pr.*, tu.username, tu.first_name, tu.last_name, s.title AS seg_title, m.title AS mod_title
                FROM passoff_requests pr
                JOIN training_users tu ON pr.user_id = tu.id
                JOIN segments s ON pr.segment_id = s.id
                JOIN modules m ON s.module_id = m.id
                WHERE pr.status = 'pending'
                ORDER BY pr.requested_at DESC");
            $s->execute();
            echo json_encode($s->fetchAll());
        } catch (Exception $e) {
            // Table may not exist yet — return empty array
            echo json_encode([]);
        }
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

    // ── Next stage: GET ?route=next_stage ──
    if ($route === 'next_stage' && $method === 'GET') {
        echo json_encode($engine->resolveNextStage($userId));
        exit;
    }

    // ── Search: GET ?route=search&q=... ──
    if ($route === 'search' && $method === 'GET') {
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            echo json_encode(['results' => [], 'query' => $q]);
            exit;
        }

        $db = Database::getInstance();
        $like = '%' . $q . '%';

        // Search segments: title and content_html
        $s = $db->prepare("
            SELECT s.id AS seg_id, s.title AS seg_title, s.content_html,
                   s.customer_quote, s.rep_response, s.tip,
                   m.id AS mod_id, m.title AS mod_title, m.icon AS mod_icon,
                   m.unlock_rule, m.module_order,
                   f.id AS folder_id, f.title AS folder_title, f.icon AS folder_icon
            FROM segments s
            JOIN modules m ON s.module_id = m.id
            JOIN folders f ON m.folder_id = f.id
            WHERE s.is_active = 1 AND m.is_active = 1
              AND (s.title LIKE ? OR s.content_html LIKE ? OR s.customer_quote LIKE ? OR s.rep_response LIKE ? OR s.tip LIKE ? OR m.title LIKE ?)
            ORDER BY m.module_order, s.segment_order
            LIMIT 50
        ");
        $s->execute([$like, $like, $like, $like, $like, $like]);
        $rows = $s->fetchAll();

        // Get user level for lock status
        $userLevel = (int)$engine->getUserStats($userId)['level'];

        $results = [];
        foreach ($rows as $r) {
            $rule = json_decode($r['unlock_rule'] ?? 'null', true);
            $modLevel = ($rule && ($rule['kind'] ?? '') === 'level') ? (int)$rule['value'] : 0;
            $isOpen = $rule && ($rule['kind'] ?? '') === 'open';
            $locked = !$isOpen && $modLevel > $userLevel;

            // Extract snippet around match
            $plainText = strip_tags($r['content_html'] ?? '');
            $allText = $plainText . ' ' . ($r['customer_quote'] ?? '') . ' ' . ($r['rep_response'] ?? '') . ' ' . ($r['tip'] ?? '');
            $pos = stripos($allText, $q);
            $snippet = '';
            if ($pos !== false) {
                $start = max(0, $pos - 60);
                $end = min(strlen($allText), $pos + strlen($q) + 60);
                $snippet = ($start > 0 ? '...' : '') . substr($allText, $start, $end - $start) . ($end < strlen($allText) ? '...' : '');
            }

            $results[] = [
                'seg_id'       => (int)$r['seg_id'],
                'seg_title'    => $r['seg_title'],
                'mod_id'       => (int)$r['mod_id'],
                'mod_title'    => $r['mod_title'],
                'mod_icon'     => $r['mod_icon'] ?? '📄',
                'folder_title' => $r['folder_title'],
                'folder_icon'  => $r['folder_icon'] ?? '📁',
                'snippet'      => $snippet,
                'locked'       => $locked,
                'lock_level'   => $locked ? $modLevel : null,
            ];
        }

        echo json_encode(['results' => $results, 'query' => $q, 'count' => count($results)]);
        exit;
    }

    http_response_code(404);
    echo json_encode(['error' => 'Unknown route: ' . $route]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
