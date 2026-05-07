<?php
/**
 * become/griff/api.php — Griff AI Coach API
 * Location: public_html/become/griff/api.php
 */
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();

if (empty($_SESSION['portal_user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated. Please log in.']);
    exit;
}

try {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/AICoach.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server config error: ' . $e->getMessage()]);
    exit;
}

$userId = (int)$_SESSION['portal_user_id'];
$role = $_SESSION['portal_role'] ?? 'rep';
$coach = new AICoach();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input = $method === 'POST' ? json_decode(file_get_contents('php://input'), true) : [];

try {
    switch ($action) {

        // ─── Chat ───
        case 'chat':
            if ($method !== 'POST') throw new Exception('POST required');
            $message = trim($input['message'] ?? '');
            if (!$message) throw new Exception('Message required');
            if (strlen($message) > 2000) throw new Exception('Message too long (2000 char max)');
            $convId = $input['conversation_id'] ?? null;
            $mode = $input['mode'] ?? 'coach';
            $result = $coach->chat($userId, $message, $convId, $mode);
            echo json_encode($result);
            break;

        // ─── Get conversations list ───
        case 'conversations':
            echo json_encode($coach->getConversations($userId));
            break;

        // ─── Get single conversation ───
        case 'conversation':
            $convId = (int)($_GET['id'] ?? 0);
            $conv = $coach->getConversation($convId, $userId);
            if (!$conv) throw new Exception('Not found');
            $conv['messages'] = json_decode($conv['messages'], true);
            echo json_encode($conv);
            break;

        // ─── Check if configured ───
        case 'status':
            echo json_encode([
                'configured' => $coach->isConfigured(),
                'rate_ok' => $coach->checkRateLimit($userId),
            ]);
            break;

        // ─── Index all content (admin only) ───
        case 'index':
            if ($role !== 'admin') throw new Exception('Admin only');
            $count = $coach->indexAllContent();
            echo json_encode(['success' => true, 'chunks_indexed' => $count]);
            break;

        // ─── Doctrine CRUD (admin only) ───
        case 'doctrine':
            if ($role !== 'admin') throw new Exception('Admin only');
            $db = Database::getInstance();
            if ($method === 'GET') {
                $s = $db->prepare("SELECT * FROM doctrine_rules ORDER BY priority DESC, id");
                $s->execute();
                echo json_encode($s->fetchAll());
            } else {
                $subAction = $input['sub'] ?? '';
                if ($subAction === 'add') {
                    $s = $db->prepare("INSERT INTO doctrine_rules (category, rule_title, rule_text, priority) VALUES (?, ?, ?, ?)");
                    $s->execute([$input['category'] ?? 'general', $input['title'] ?? '', $input['text'] ?? '', (int)($input['priority'] ?? 5)]);
                    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
                } elseif ($subAction === 'update') {
                    $s = $db->prepare("UPDATE doctrine_rules SET category=?, rule_title=?, rule_text=?, priority=?, is_active=? WHERE id=?");
                    $s->execute([$input['category'] ?? 'general', $input['title'] ?? '', $input['text'] ?? '', (int)($input['priority'] ?? 5), (int)($input['is_active'] ?? 1), (int)$input['id']]);
                    echo json_encode(['success' => true]);
                } elseif ($subAction === 'delete') {
                    $db->prepare("DELETE FROM doctrine_rules WHERE id=?")->execute([(int)$input['id']]);
                    echo json_encode(['success' => true]);
                }
            }
            break;

        default:
            throw new Exception('Unknown action: ' . $action);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
