<?php
// become/griff/api.php — Griff API
ob_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Catch any PHP errors/warnings as JSON
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['portal_user_id'])) {
    ob_end_clean();
    http_response_code(401);
    die(json_encode(array('error' => 'Not logged in')));
}

require_once __DIR__ . '/../includes/db.php';

try {
    require_once __DIR__ . '/../includes/AICoach.php';
} catch (Exception $e) {
    ob_end_clean();
    die(json_encode(array('error' => 'Load error: ' . $e->getMessage())));
}

$userId = intval($_SESSION['portal_user_id']);
$role = isset($_SESSION['portal_role']) ? $_SESSION['portal_role'] : 'rep';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$input = array();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) $input = array();
}

try {
    $coach = new AICoach();

    if ($action === 'ping') {
        ob_end_clean(); echo json_encode(array('pong' => true, 'user' => $userId, 'role' => $role));
        exit;
    }

    if ($action === 'status') {
        ob_end_clean(); echo json_encode(array('configured' => $coach->isConfigured(), 'ok' => true));
        exit;
    }

    if ($action === 'chat') {
        $msg = isset($input['message']) ? trim($input['message']) : '';
        if ($msg === '') throw new Exception('Message required');
        $convId = isset($input['conversation_id']) ? $input['conversation_id'] : null;
        $mode = isset($input['mode']) ? $input['mode'] : 'coach';
        ob_end_clean(); echo json_encode($coach->chat($userId, $msg, $convId, $mode));
        exit;
    }

    if ($action === 'conversations') {
        ob_end_clean(); echo json_encode($coach->getConversations($userId));
        exit;
    }

    if ($action === 'conversation') {
        $cid = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $conv = $coach->getConversation($cid, $userId);
        if (!$conv) throw new Exception('Not found');
        $conv['messages'] = json_decode($conv['messages'], true);
        ob_end_clean(); echo json_encode($conv);
        exit;
    }

    if ($action === 'index' && $role === 'admin') {
        $count = $coach->indexAllContent();
        ob_end_clean(); echo json_encode(array('success' => true, 'chunks_indexed' => $count));
        exit;
    }

    if ($action === 'doctrine') {
        if ($role !== 'admin') throw new Exception('Admin only');
        $db = Database::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $s = $db->prepare("SELECT * FROM doctrine_rules ORDER BY priority DESC, id");
            $s->execute();
            ob_end_clean(); echo json_encode($s->fetchAll());
        } else {
            $sub = isset($input['sub']) ? $input['sub'] : '';
            if ($sub === 'add') {
                $s = $db->prepare("INSERT INTO doctrine_rules (category, rule_title, rule_text, priority) VALUES (?, ?, ?, ?)");
                $s->execute(array(isset($input['category']) ? $input['category'] : 'general', isset($input['title']) ? $input['title'] : '', isset($input['text']) ? $input['text'] : '', intval(isset($input['priority']) ? $input['priority'] : 5)));
                ob_end_clean(); echo json_encode(array('success' => true));
            } elseif ($sub === 'delete') {
                $db->prepare("DELETE FROM doctrine_rules WHERE id=?")->execute(array(intval($input['id'])));
                ob_end_clean(); echo json_encode(array('success' => true));
            } else {
                ob_end_clean(); echo json_encode(array('error' => 'Unknown sub-action'));
            }
        }
        exit;
    }

    throw new Exception('Unknown action: ' . $action);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(400);
    ob_end_clean(); echo json_encode(array('error' => $e->getMessage()));
}
