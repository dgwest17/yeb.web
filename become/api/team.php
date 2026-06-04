<?php
/**
 * become/api/team.php — Scoped team API for Trainers
 * Location: public_html/become/api/team.php
 *
 * Trainers (and leaders/admins) manage ONLY their own direct reps here.
 * No content editing. A trainer can:
 *   - list their reps (with level + pass-off status)
 *   - add a rep (auto-assigned under them, role 'rep')
 *   - reset a rep's password
 *   - activate/deactivate a rep
 * Every write verifies the target actually reports to the caller.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';          // require_portal_auth + helpers
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ProgressionEngine.php';

if (!has_role('trainer')) {
    http_response_code(403);
    echo json_encode(['error' => 'Trainer access required.']);
    exit;
}

$db     = Database::getInstance();
$engine = new ProgressionEngine();
$me     = (int)$_SESSION['portal_user_id'];
$myRole = portal_role();

/** True if the caller may manage this target user id. */
function team_can_manage($db, $me, $myRole, $targetId) {
    if ($targetId === $me) return false;                 // can't manage self here
    if ($myRole === 'admin' || $myRole === 'leader') return true;
    $s = $db->prepare("SELECT parent_id FROM training_users WHERE id = ?");
    $s->execute([$targetId]);
    $row = $s->fetch();
    return $row && (int)$row['parent_id'] === $me;        // trainer: only own reps
}

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';
        if ($action === 'list') {
            $reps = $engine->getReps($me);
            foreach ($reps as &$r) {
                $rid  = (int)$r['id'];
                $prog = $engine->getUserProgress($rid);
                $st   = $engine->getCurrentLevelState($rid);
                $r['level']           = (int)$prog['level'];
                $r['xp']              = (int)$prog['xp'];
                $r['modules_done']    = $st['modules_done'];
                $r['modules_total']   = $st['modules_total'];
                $r['passoff_status']  = $st['passoff_status'];
                $r['passoff_eligible']= $st['eligible'];
                unset($r['created_at']);
            }
            echo json_encode(['success' => true, 'reps' => $reps, 'me' => $me]);
            exit;
        }
        echo json_encode(['error' => 'Unknown action']);
        exit;
    }

    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'add_rep':
            $email = trim($input['email'] ?? '');
            $pass  = $input['password'] ?? '';
            if ($email === '' || $pass === '') { echo json_encode(['error'=>'Email and password are required.']); break; }

            $chk = $db->prepare("SELECT id FROM training_users WHERE email = ? OR username = ? LIMIT 1");
            $chk->execute([$email, $email]);
            if ($chk->fetch()) { echo json_encode(['error'=>'A user with that email already exists.']); break; }

            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $s = $db->prepare("INSERT INTO training_users (username, first_name, last_name, email, password_hash, role, parent_id, full_access) VALUES (?, ?, ?, ?, ?, 'rep', ?, 0)");
            $s->execute([$email, trim($input['first_name']??''), trim($input['last_name']??''), $email, $hash, $me]);
            $newId = (int)$db->lastInsertId();
            $db->prepare("INSERT INTO user_progress (user_id, xp, level, join_date) VALUES (?, 0, 0, CURDATE())")->execute([$newId]);
            echo json_encode(['success'=>true, 'id'=>$newId]);
            break;

        case 'reset_password':
            $tid = (int)($input['id'] ?? 0);
            if (!team_can_manage($db, $me, $myRole, $tid)) { http_response_code(403); echo json_encode(['error'=>'Not your rep.']); break; }
            $hash = password_hash($input['password'] ?? '', PASSWORD_DEFAULT);
            $db->prepare("UPDATE training_users SET password_hash=? WHERE id=?")->execute([$hash, $tid]);
            echo json_encode(['success'=>true]);
            break;

        case 'set_active':
            $tid = (int)($input['id'] ?? 0);
            if (!team_can_manage($db, $me, $myRole, $tid)) { http_response_code(403); echo json_encode(['error'=>'Not your rep.']); break; }
            $active = !empty($input['is_active']) ? 1 : 0;
            $db->prepare("UPDATE training_users SET is_active=? WHERE id=?")->execute([$active, $tid]);
            echo json_encode(['success'=>true]);
            break;

        default:
            echo json_encode(['error'=>'Unknown action: '.$action]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error'=>$e->getMessage()]);
}
