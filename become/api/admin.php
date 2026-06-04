<?php
/**
 * become/api/admin.php — Admin API for Training Manager
 * Location: public_html/become/api/admin.php
 * 
 * Handles all admin CRUD for the progression engine.
 * Requires admin/leader session.
 */
session_start();
header('Content-Type: application/json');

// Auth check — must be leader or admin
$role = $_SESSION['portal_role'] ?? '';
if (!in_array($role, ['leader', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required. Log into the portal first.']);
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ProgressionEngine.php';

$db = Database::getInstance();
$engine = new ProgressionEngine();
$userId = (int)($_SESSION['portal_user_id'] ?? 0);

// ── Route ──
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';

        if ($action === 'all') {
            // Load everything for the admin panel
            $folders  = $db->prepare("SELECT * FROM folders WHERE is_active=1 ORDER BY folder_order")->execute() ? [] : [];
            $s = $db->prepare("SELECT * FROM folders WHERE is_active=1 ORDER BY folder_order"); $s->execute(); $folders = $s->fetchAll();
            $s = $db->prepare("SELECT * FROM modules WHERE is_active=1 ORDER BY module_order"); $s->execute(); $modules = $s->fetchAll();
            $s = $db->prepare("SELECT * FROM segments WHERE is_active=1 ORDER BY segment_order"); $s->execute(); $segments = $s->fetchAll();
            $s = $db->prepare("SELECT id, username, first_name, last_name, email, role, parent_id, full_access, is_active, created_at FROM training_users ORDER BY created_at"); $s->execute(); $users = $s->fetchAll();
            $s = $db->prepare("SELECT * FROM level_thresholds ORDER BY level"); $s->execute(); $thresholds = $s->fetchAll();

            // Parse JSON fields
            foreach ($folders as &$f) { $f['unlock_rule'] = json_decode($f['unlock_rule'] ?? 'null', true); }
            foreach ($modules as &$m) { $m['unlock_rule'] = json_decode($m['unlock_rule'] ?? 'null', true); $m['drip_rule'] = json_decode($m['drip_rule'] ?? 'null', true); $m['prerequisites'] = json_decode($m['prerequisites'] ?? 'null', true); }

            echo json_encode(['folders'=>$folders, 'modules'=>$modules, 'segments'=>$segments, 'users'=>$users, 'thresholds'=>$thresholds]);
            exit;
        }

        echo json_encode(['error' => 'Unknown GET action']);
        exit;
    }

    // POST actions
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';

    switch ($action) {
        // ── FOLDERS ──
        case 'add_level':
            $lvl = (int)($input['level'] ?? 0);
            $title = $input['title'] ?? 'New Level';
            $xp = (int)($input['xp_required'] ?? 0);
            $icon = $input['badge_icon'] ?? '⭐';
            $s = $db->prepare("INSERT INTO level_thresholds (level, xp_required, title, badge_icon) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE title=VALUES(title), xp_required=VALUES(xp_required), badge_icon=VALUES(badge_icon)");
            $s->execute([$lvl, $xp, $title, $icon]);
            echo json_encode(['success'=>true, 'level'=>$lvl]);
            break;

        case 'add_folder':
            $s = $db->prepare("SELECT COALESCE(MAX(folder_order),0)+1 n FROM folders");
            $s->execute(); $order = (int)$s->fetch()['n'];
            $s = $db->prepare("INSERT INTO folders (title, parent_id, folder_order) VALUES (?, ?, ?)");
            $s->execute([$input['title'] ?? 'New Folder', $input['parent_id'] ?? null, $order]);
            echo json_encode(['success'=>true, 'id'=>(int)$db->lastInsertId()]);
            break;

        case 'update_folder':
            $id = (int)($input['id'] ?? 0);
            $allowed = ['title','icon','description','folder_order','folder_type','xp_reward','unlock_rule','is_active'];
            $sets = []; $vals = [];
            foreach ($allowed as $k) {
                if (array_key_exists($k, $input)) {
                    $v = $input[$k];
                    if ($k === 'unlock_rule' && is_array($v)) $v = json_encode($v);
                    if ($k === 'unlock_rule' && is_string($v)) { json_decode($v); if (json_last_error() !== JSON_ERROR_NONE) $v = null; }
                    $sets[] = "$k = ?"; $vals[] = $v;
                }
            }
            if ($sets) { $vals[] = $id; $db->prepare("UPDATE folders SET ".implode(',',$sets)." WHERE id=?")->execute($vals); }
            echo json_encode(['success'=>true]);
            break;

        case 'delete_folder':
            $db->prepare("DELETE FROM folders WHERE id=?")->execute([(int)$input['id']]);
            echo json_encode(['success'=>true]);
            break;

        // ── MODULES ──
        case 'add_module':
            $fid = (int)($input['folder_id'] ?? 0);
            $s = $db->prepare("SELECT COALESCE(MAX(module_order),0)+1 n FROM modules WHERE folder_id=?");
            $s->execute([$fid]); $order = (int)$s->fetch()['n'];
            $s = $db->prepare("INSERT INTO modules (folder_id, title, module_order) VALUES (?, ?, ?)");
            $s->execute([$fid, $input['title'] ?? 'New Module', $order]);
            echo json_encode(['success'=>true, 'id'=>(int)$db->lastInsertId()]);
            break;

        case 'update_module':
            $id = (int)($input['id'] ?? 0);
            $allowed = ['title','icon','description','module_order','module_type','xp_reward','unlock_rule','drip_rule','next_step_text','is_active','prerequisites','tree_x','tree_y'];
            $sets = []; $vals = [];
            foreach ($allowed as $k) {
                if (array_key_exists($k, $input)) {
                    $v = $input[$k];
                    if (in_array($k, ['unlock_rule','drip_rule','prerequisites']) && is_array($v)) $v = json_encode($v);
                    if (in_array($k, ['unlock_rule','drip_rule']) && is_string($v)) { json_decode($v); if (json_last_error() !== JSON_ERROR_NONE) $v = null; }
                    $sets[] = "$k = ?"; $vals[] = $v;
                }
            }
            if ($sets) { $vals[] = $id; $db->prepare("UPDATE modules SET ".implode(',',$sets)." WHERE id=?")->execute($vals); }
            echo json_encode(['success'=>true]);
            break;

        case 'delete_module':
            $db->prepare("DELETE FROM modules WHERE id=?")->execute([(int)$input['id']]);
            echo json_encode(['success'=>true]);
            break;

        // ── SEGMENTS ──
        case 'add_segment':
            $mid = (int)($input['module_id'] ?? 0);
            $s = $db->prepare("SELECT COALESCE(MAX(segment_order),0)+1 n FROM segments WHERE module_id=?");
            $s->execute([$mid]); $order = (int)$s->fetch()['n'];
            $s = $db->prepare("INSERT INTO segments (module_id, title, segment_order) VALUES (?, ?, ?)");
            $s->execute([$mid, $input['title'] ?? 'New Segment', $order]);
            echo json_encode(['success'=>true, 'id'=>(int)$db->lastInsertId()]);
            break;

        case 'update_segment':
            $id = (int)($input['id'] ?? 0);
            $allowed = ['title','segment_order','segment_type','content_html','customer_quote','rep_response','tip','xp_reward','unlock_rule','is_active'];
            $sets = []; $vals = [];
            foreach ($allowed as $k) {
                if (array_key_exists($k, $input)) {
                    $v = $input[$k];
                    if ($k === 'unlock_rule' && is_array($v)) $v = json_encode($v);
                    $sets[] = "$k = ?"; $vals[] = $v;
                }
            }
            if ($sets) { $vals[] = $id; $db->prepare("UPDATE segments SET ".implode(',',$sets)." WHERE id=?")->execute($vals); }
            echo json_encode(['success'=>true]);
            break;

        case 'delete_segment':
            $db->prepare("DELETE FROM segments WHERE id=?")->execute([(int)$input['id']]);
            echo json_encode(['success'=>true]);
            break;

        // ── USERS ──
        case 'add_user':
            $email = trim($input['email'] ?? '');
            $uname = $email !== '' ? $email : trim($input['username'] ?? '');
            if ($uname === '') { echo json_encode(['error'=>'Email is required.']); break; }
            // Block duplicate email/username up front for a clean message.
            $chk = $db->prepare("SELECT id FROM training_users WHERE email = ? OR username = ? LIMIT 1");
            $chk->execute([$email !== '' ? $email : $uname, $uname]);
            if ($chk->fetch()) { echo json_encode(['error'=>'A user with that email already exists.']); break; }

            $hash    = password_hash($input['password'] ?? '', PASSWORD_DEFAULT);
            $parent  = isset($input['parent_id']) && $input['parent_id'] !== '' ? (int)$input['parent_id'] : null;
            $full    = !empty($input['full_access']) ? 1 : 0;
            $s = $db->prepare("INSERT INTO training_users (username, first_name, last_name, email, password_hash, role, parent_id, full_access) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $s->execute([$uname, $input['first_name']??'', $input['last_name']??'', ($email !== '' ? $email : null), $hash, $input['role']??'rep', $parent, $full]);
            $newId = (int)$db->lastInsertId();
            $db->prepare("INSERT INTO user_progress (user_id, xp, level, join_date) VALUES (?, 0, 0, CURDATE())")->execute([$newId]);
            echo json_encode(['success'=>true, 'id'=>$newId]);
            break;

        case 'update_user':
            $id = (int)($input['id'] ?? 0);
            $allowed = ['first_name','last_name','email','role','parent_id','full_access','is_active'];
            $sets = []; $vals = [];
            foreach ($allowed as $k) {
                if (array_key_exists($k, $input)) {
                    $v = $input[$k];
                    if ($k === 'parent_id')   $v = ($v === '' || $v === null) ? null : (int)$v;
                    if ($k === 'full_access') $v = !empty($v) ? 1 : 0;
                    $sets[] = "$k = ?"; $vals[] = $v;
                }
            }
            // Keep the login username in step with the email when email changes.
            if (array_key_exists('email', $input) && trim($input['email']) !== '') {
                $sets[] = "username = ?"; $vals[] = trim($input['email']);
            }
            if ($sets) { $vals[] = $id; $db->prepare("UPDATE training_users SET ".implode(',',$sets)." WHERE id=?")->execute($vals); }
            echo json_encode(['success'=>true]);
            break;

        case 'reset_password':
            $hash = password_hash($input['password'] ?? '', PASSWORD_DEFAULT);
            $db->prepare("UPDATE training_users SET password_hash=? WHERE id=?")->execute([$hash, (int)$input['id']]);
            echo json_encode(['success'=>true]);
            break;

        case 'delete_user':
            $db->prepare("DELETE FROM training_users WHERE id=?")->execute([(int)$input['id']]);
            echo json_encode(['success'=>true]);
            break;

        // ── MARKDOWN IMPORT ──
        case 'import_markdown':
            $result = $engine->importMarkdown(
                (int)($input['folder_id'] ?? 0),
                $input['module_title'] ?? 'Imported Module',
                $input['markdown_text'] ?? '',
                $input['default_unlock_rule'] ?? null
            );
            echo json_encode(['success'=>true, 'result'=>$result]);
            break;

        // ── MANUAL UNLOCK ──
        case 'manual_unlock':
            $engine->manualUnlock(
                (int)($input['user_id'] ?? 0),
                $input['entity_type'] ?? 'folder',
                (int)($input['entity_id'] ?? 0),
                $userId
            );
            echo json_encode(['success'=>true]);
            break;

        case 'set_user_level':
            $uid = (int)($input['user_id'] ?? 0);
            $lvl = (int)($input['level'] ?? 1);
            $xp = (int)($input['xp'] ?? 0);
            $db->prepare("UPDATE user_progress SET level=?, xp=? WHERE user_id=?")->execute([$lvl, $xp, $uid]);
            // Mirror the new level into Zoho (best-effort).
            require_once __DIR__ . '/../includes/ZohoRecruitSync.php';
            ZohoRecruitSync::pushLevelForUser($uid);
            echo json_encode(['success'=>true]);
            break;

        case 'zoho_sync':
            require_once __DIR__ . '/../includes/ZohoRecruitSync.php';
            $sync = new ZohoRecruitSync();
            echo json_encode($sync->run());
            break;

        default:
            echo json_encode(['error'=>'Unknown action: '.$action]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error'=>$e->getMessage()]);
}
