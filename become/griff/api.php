<?php
// become/griff/api.php — Griff API
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Prevent double session_start
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['portal_user_id'])) {
    http_response_code(401);
    die(json_encode(array('error' => 'Not logged in')));
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/AICoach.php';

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

    if ($action === 'status') {
        echo json_encode(array('configured' => $coach->isConfigured(), 'ok' => true));
        exit;
    }

    if ($action === 'chat') {
        $msg = isset($input['message']) ? trim($input['message']) : '';
        if ($msg === '') throw new Exception('Message required');
        $convId = isset($input['conversation_id']) ? $input['conversation_id'] : null;
        $mode = isset($input['mode']) ? $input['mode'] : 'coach';
        echo json_encode($coach->chat($userId, $msg, $convId, $mode));
        exit;
    }

    if ($action === 'conversations') {
        echo json_encode($coach->getConversations($userId));
        exit;
    }

    if ($action === 'conversation') {
        $cid = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $conv = $coach->getConversation($cid, $userId);
        if (!$conv) throw new Exception('Not found');
        $conv['messages'] = json_decode($conv['messages'], true);
        echo json_encode($conv);
        exit;
    }

    if ($action === 'index' && $role === 'admin') {
        $count = $coach->indexAllContent();
        echo json_encode(array('success' => true, 'chunks_indexed' => $count));
        exit;
    }

    if ($action === 'doctrine') {
        if ($role !== 'admin') throw new Exception('Admin only');
        $db = Database::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $s = $db->prepare("SELECT * FROM doctrine_rules ORDER BY priority DESC, id");
            $s->execute();
            echo json_encode($s->fetchAll());
        } else {
            $sub = isset($input['sub']) ? $input['sub'] : '';
            if ($sub === 'add') {
                $s = $db->prepare("INSERT INTO doctrine_rules (category, rule_title, rule_text, priority) VALUES (?, ?, ?, ?)");
                $s->execute(array(isset($input['category']) ? $input['category'] : 'general', isset($input['title']) ? $input['title'] : '', isset($input['text']) ? $input['text'] : '', intval(isset($input['priority']) ? $input['priority'] : 5)));
                echo json_encode(array('success' => true));
            } elseif ($sub === 'delete') {
                $db->prepare("DELETE FROM doctrine_rules WHERE id=?")->execute(array(intval($input['id'])));
                echo json_encode(array('success' => true));
            } else {
                echo json_encode(array('error' => 'Unknown sub-action'));
            }
        }
        exit;
    }

    throw new Exception('Unknown action: ' . $action);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(array('error' => $e->getMessage()));
}
