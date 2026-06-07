<?php
/**
 * become/includes/ProgressionEngine.php
 *
 * Core engine: unlock checks, XP, LEVEL-GATED pass-offs, next action,
 * completion, markdown import, org hierarchy.
 * Location: public_html/become/includes/ProgressionEngine.php
 *
 * MODEL (migration 002):
 *  - Within a level, ALL content unlocks at once (no per-stage sequential locks).
 *  - Levels DO NOT auto-advance. A rep completes every module in their level,
 *    becomes eligible, requests a LEVEL pass-off, and a leader/admin approves
 *    it to unlock the next level.
 *  - full_access users (Engineers) bypass every lock and pass-off.
 *  - unlock_rule {"kind":"engineer"} = visible only to full_access users.
 */

require_once __DIR__ . '/db.php';

class ProgressionEngine {
    private $db;
    private $thresholdsCache = null;
    private $fullAccessCache = [];

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ================================================================
    // LEVELS / THRESHOLDS
    // ================================================================

    public function getLevelThresholds() {
        return $this->thresholds();
    }

    private function thresholds() {
        if ($this->thresholdsCache === null) {
            $s = $this->db->prepare("SELECT * FROM level_thresholds ORDER BY level");
            $s->execute();
            $this->thresholdsCache = $s->fetchAll();
        }
        return $this->thresholdsCache;
    }

    public function computeLevel($xp) {
        $level = 0;
        foreach ($this->thresholds() as $t) {
            if ($xp >= (int)$t['xp_required']) $level = (int)$t['level'];
        }
        return $level;
    }

    /** Highest level number that has any non-open content or a threshold. */
    private function maxLevel() {
        $max = 0;
        foreach ($this->thresholds() as $t) $max = max($max, (int)$t['level']);
        $s = $this->db->prepare("SELECT unlock_rule FROM modules WHERE is_active=1");
        $s->execute();
        foreach ($s->fetchAll() as $m) {
            $r = json_decode($m['unlock_rule'] ?? 'null', true);
            if ($r && ($r['kind'] ?? '') === 'level') $max = max($max, (int)($r['value'] ?? 0));
        }
        return $max;
    }

    // ================================================================
    // USER / ACCESS
    // ================================================================

    public function isFullAccess($userId) {
        if (!isset($this->fullAccessCache[$userId])) {
            try {
                $s = $this->db->prepare("SELECT full_access FROM training_users WHERE id=?");
                $s->execute([$userId]);
                $row = $s->fetch();
                $this->fullAccessCache[$userId] = $row ? (int)$row['full_access'] === 1 : false;
            } catch (Exception $e) {
                $this->fullAccessCache[$userId] = false;
            }
        }
        return $this->fullAccessCache[$userId];
    }

    public function getUserProgress($userId) {
        $s = $this->db->prepare("SELECT * FROM user_progress WHERE user_id = ?");
        $s->execute([$userId]);
        $p = $s->fetch();
        if (!$p) {
            $s = $this->db->prepare("INSERT INTO user_progress (user_id, xp, level, join_date) VALUES (?, 0, 0, CURDATE())");
            $s->execute([$userId]);
            return ['user_id' => $userId, 'xp' => 0, 'level' => 0, 'join_date' => date('Y-m-d')];
        }
        return $p;
    }

    public function getCompletedSegmentIds($userId) {
        $s = $this->db->prepare("SELECT segment_id FROM completed_segments WHERE user_id = ?");
        $s->execute([$userId]);
        return array_column($s->fetchAll(), 'segment_id');
    }

    public function getCompletedModuleIds($userId) {
        $s = $this->db->prepare("SELECT module_id FROM completed_modules WHERE user_id = ?");
        $s->execute([$userId]);
        return array_column($s->fetchAll(), 'module_id');
    }

    public function getCompletedFolderIds($userId) {
        $s = $this->db->prepare("SELECT folder_id FROM completed_folders WHERE user_id = ?");
        $s->execute([$userId]);
        return array_column($s->fetchAll(), 'folder_id');
    }

    private function getManualUnlocks($userId) {
        $s = $this->db->prepare("SELECT entity_type, entity_id FROM manual_unlocks WHERE user_id = ?");
        $s->execute([$userId]);
        $unlocks = ['folder' => [], 'module' => [], 'segment' => []];
        foreach ($s->fetchAll() as $r) {
            $unlocks[$r['entity_type']][] = (int)$r['entity_id'];
        }
        return $unlocks;
    }

    // ================================================================
    // UNLOCK ENGINE  (level-gated: whole level opens at once)
    // ================================================================

    public function getAccessibleContent($userId) {
        $progress      = $this->getUserProgress($userId);
        $compSegs      = $this->getCompletedSegmentIds($userId);
        $compMods      = $this->getCompletedModuleIds($userId);
        $compFolders   = $this->getCompletedFolderIds($userId);
        $manualUnlocks = $this->getManualUnlocks($userId);
        $daysSince     = max(0, (int)((time() - strtotime($progress['join_date'])) / 86400));
        $lvl           = (int)$progress['level'];
        $full          = $this->isFullAccess($userId);

        $s = $this->db->prepare("SELECT * FROM folders WHERE is_active=1 ORDER BY folder_order");
        $s->execute();
        $allFolders = $s->fetchAll();

        $s = $this->db->prepare("SELECT * FROM modules WHERE is_active=1 ORDER BY module_order");
        $s->execute();
        $modsByFolder = [];
        foreach ($s->fetchAll() as $m) $modsByFolder[$m['folder_id']][] = $m;

        $s = $this->db->prepare("SELECT * FROM segments WHERE is_active=1 ORDER BY segment_order");
        $s->execute();
        $segsByMod = [];
        foreach ($s->fetchAll() as $seg) $segsByMod[$seg['module_id']][] = $seg;

        $result = [];
        foreach ($allFolders as $folder) {
            $fid = (int)$folder['id'];
            $folder['locked']    = !$this->checkFolderUnlock($folder, $lvl, $daysSince, $full, $manualUnlocks);
            $folder['completed'] = in_array($fid, $compFolders);
            $folder['modules']   = [];

            foreach ($modsByFolder[$fid] ?? [] as $mod) {
                $mid = (int)$mod['id'];
                $mod['locked']    = $folder['locked'] || !$this->checkModuleUnlock($mod, $lvl, $daysSince, $full, $manualUnlocks);
                $mod['completed'] = in_array($mid, $compMods);
                $mod['segments']  = [];

                foreach ($segsByMod[$mid] ?? [] as $seg) {
                    $sid = (int)$seg['id'];
                    // Segments inherit their module's lock state (whole level is open).
                    $seg['locked']    = $mod['locked'] || !$this->checkSegmentUnlock($seg, $lvl, $daysSince, $full, $manualUnlocks);
                    $seg['completed'] = in_array($sid, $compSegs);
                    $mod['segments'][] = $seg;
                }

                $total = count($mod['segments']);
                $done  = count(array_filter($mod['segments'], fn($s) => $s['completed']));
                $mod['progress']           = $total > 0 ? round(($done/$total)*100) : 0;
                $mod['segments_total']     = $total;
                $mod['segments_completed'] = $done;
                $folder['modules'][] = $mod;
            }

            $mt = count($folder['modules']);
            $md = count(array_filter($folder['modules'], fn($m) => $m['completed']));
            $folder['progress']          = $mt > 0 ? round(($md/$mt)*100) : 0;
            $folder['modules_total']     = $mt;
            $folder['modules_completed'] = $md;
            $result[] = $folder;
        }

        return $this->nestFolders($result);
    }

    private function nestFolders($flat) {
        $byId = [];
        foreach ($flat as &$f) { $f['children'] = []; $byId[$f['id']] = &$f; }
        unset($f);
        $tree = [];
        foreach ($byId as &$f) {
            if ($f['parent_id'] && isset($byId[$f['parent_id']])) {
                $byId[$f['parent_id']]['children'][] = &$f;
            } else {
                $tree[] = &$f;
            }
        }
        return $tree;
    }

    // ─── Unlock check helpers ───
    // Rules: open = always; engineer = full_access only; level N = userLevel >= N
    // (the WHOLE level is unlocked, no sequential gating); daysSinceJoin = drip.

    private function checkFolderUnlock($f, $lvl, $days, $full, $mu) {
        if ($full) return true;
        if (in_array((int)$f['id'], $mu['folder'])) return true;
        $r = json_decode($f['unlock_rule'] ?? 'null', true);
        if ($r && ($r['kind'] ?? '') === 'engineer') return false;
        return !$r || $this->evalRule($r, $lvl, $days);
    }

    private function checkModuleUnlock($m, $lvl, $days, $full, $mu) {
        if ($full) return true;
        if (in_array((int)$m['id'], $mu['module'])) return true;

        $drip = json_decode($m['drip_rule'] ?? 'null', true);
        if ($drip && $days < ($drip['startAfterDays'] ?? 0)) return false;

        $r = json_decode($m['unlock_rule'] ?? 'null', true);
        $kind = $r['kind'] ?? '';
        if ($kind === 'open')     return true;
        if ($kind === 'engineer') return false;

        $modLevel = ($kind === 'level') ? (int)($r['value'] ?? 0) : 0;
        return $modLevel <= $lvl;   // entire level unlocks together
    }

    private function checkSegmentUnlock($seg, $lvl, $days, $full, $mu) {
        if ($full) return true;
        if (in_array((int)$seg['id'], $mu['segment'])) return true;
        $r = json_decode($seg['unlock_rule'] ?? 'null', true);
        if (!$r) return true;                              // inherits module
        $kind = $r['kind'] ?? '';
        if ($kind === 'engineer')        return false;
        if ($kind === 'previousSegment') return true;      // no sequential gating now
        return $this->evalRule($r, $lvl, $days);
    }

    private function evalRule($r, $lvl, $days) {
        $v = (int)($r['value'] ?? 0);
        switch ($r['kind'] ?? '') {
            case 'level':         return $lvl >= $v;
            case 'daysSinceJoin': return $days >= $v;
            case 'open':          return true;
            case 'engineer':      return false;            // full_access handled upstream
            case 'manual':        return false;
            default:              return true;
        }
    }

    // ================================================================
    // LEVEL PASS-OFFS  (the gate between levels)
    // ================================================================

    /**
     * State of the current level for a rep: how many modules done, whether
     * they're eligible to request a pass-off, and any open request.
     */
    public function getCurrentLevelState($userId) {
        $p   = $this->getUserProgress($userId);
        $lvl = (int)$p['level'];
        $full = $this->isFullAccess($userId);

        $s = $this->db->prepare("SELECT id, unlock_rule FROM modules WHERE is_active=1");
        $s->execute();
        $levelModIds = [];
        $hasHigher = false;
        foreach ($s->fetchAll() as $m) {
            $r = json_decode($m['unlock_rule'] ?? 'null', true);
            $kind = $r['kind'] ?? '';
            if ($kind === 'open' || $kind === 'engineer') continue;
            $ml = ($kind === 'level') ? (int)($r['value'] ?? 0) : 0;
            if ($ml === $lvl) $levelModIds[] = (int)$m['id'];
            if ($ml > $lvl)   $hasHigher = true;
        }

        $comp  = $this->getCompletedModuleIds($userId);
        $total = count($levelModIds);
        $done  = count(array_filter($levelModIds, fn($id) => in_array($id, $comp)));

        $request = $this->getLatestLevelPassoff($userId, $lvl);
        $status  = $request ? $request['status'] : 'none';

        $isMax = !$hasHigher && $lvl >= $this->maxLevel();
        $allComplete = ($total === 0) ? true : ($done >= $total);

        return [
            'level'           => $lvl,
            'full_access'     => $full,
            'modules_total'   => $total,
            'modules_done'    => $done,
            'all_complete'    => $allComplete,
            'eligible'        => (!$full && !$isMax && $status !== 'pending' && $allComplete),
            'passoff_status'  => $status,            // none | pending | passed | rejected
            'request_id'      => $request ? (int)$request['id'] : null,
            'is_max_level'    => $isMax,
        ];
    }

    private function getLatestLevelPassoff($userId, $level) {
        try {
            $s = $this->db->prepare("SELECT * FROM passoff_requests WHERE user_id=? AND kind='level' AND level=? ORDER BY id DESC LIMIT 1");
            $s->execute([$userId, $level]);
            return $s->fetch() ?: null;
        } catch (Exception $e) { return null; }
    }

    /** Rep requests a pass-off to clear their current level. Returns request info (for emailing). */
    public function requestLevelPassoff($userId) {
        $state = $this->getCurrentLevelState($userId);
        if ($state['full_access'])         throw new Exception('Engineers have full access — no pass-off needed.');
        if ($state['is_max_level'])        throw new Exception('You have reached the top level.');
        if (!$state['all_complete'])       throw new Exception('Finish every module in this level first.');
        if ($state['passoff_status'] === 'pending') {
            return ['request_id' => $state['request_id'], 'level' => $state['level'], 'already_pending' => true];
        }

        $lvl = $state['level'];
        $s = $this->db->prepare("INSERT INTO passoff_requests (user_id, segment_id, kind, level, status, requested_at) VALUES (?, NULL, 'level', ?, 'pending', NOW())");
        $s->execute([$userId, $lvl]);
        $rid = (int)$this->db->lastInsertId();

        try {
            $s = $this->db->prepare("INSERT INTO activity_log (user_id,action,entity_type,entity_id,xp_change,details) VALUES (?,'request_level_passoff','level',?,0,?)");
            $s->execute([$userId, $lvl, json_encode(['request_id' => $rid])]);
        } catch (Exception $e) {}

        return ['request_id' => $rid, 'level' => $lvl, 'already_pending' => false];
    }

    /** Leader/admin approves → user advances one level. */
    public function approveLevelPassoff($requestId, $reviewerId) {
        $this->db->beginTransaction();
        try {
            $s = $this->db->prepare("SELECT * FROM passoff_requests WHERE id=? AND kind='level'");
            $s->execute([$requestId]);
            $req = $s->fetch();
            if (!$req)                        throw new Exception('Request not found.');
            if ($req['status'] !== 'pending') throw new Exception('Already reviewed.');

            $uid = (int)$req['user_id'];
            $reqLevel = (int)$req['level'];

            $s = $this->db->prepare("UPDATE passoff_requests SET status='passed', reviewed_by=?, reviewed_at=NOW() WHERE id=?");
            $s->execute([$reviewerId, $requestId]);

            // Advance only if they're still sitting at the level they requested.
            $p = $this->getUserProgress($uid);
            $newLevel = (int)$p['level'];
            if ((int)$p['level'] === $reqLevel) {
                $newLevel = $reqLevel + 1;
                $s = $this->db->prepare("UPDATE user_progress SET level=?, last_activity=NOW() WHERE user_id=?");
                $s->execute([$newLevel, $uid]);
            }

            try {
                $s = $this->db->prepare("INSERT INTO activity_log (user_id,action,entity_type,entity_id,xp_change,details) VALUES (?,'approve_level_passoff','level',?,0,?)");
                $s->execute([$uid, $reqLevel, json_encode(['by' => $reviewerId, 'new_level' => $newLevel])]);
            } catch (Exception $e) {}

            $this->db->commit();
            return ['success' => true, 'user_id' => $uid, 'new_level' => $newLevel];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function rejectLevelPassoff($requestId, $reviewerId, $notes = '') {
        $s = $this->db->prepare("SELECT * FROM passoff_requests WHERE id=? AND kind='level' AND status='pending'");
        $s->execute([$requestId]);
        if (!$s->fetch()) throw new Exception('Request not found or already reviewed.');
        $s = $this->db->prepare("UPDATE passoff_requests SET status='rejected', reviewed_by=?, reviewed_at=NOW(), reviewer_notes=? WHERE id=?");
        $s->execute([$reviewerId, $notes, $requestId]);
        return ['success' => true];
    }

    /**
     * Pending pass-offs for the management view. Any leader/admin sees all.
     * Returns both level and (legacy) segment requests with rep + context.
     */
    public function getPendingPassoffs($limit = 200) {
        try {
            $s = $this->db->prepare(
                "SELECT pr.*, tu.first_name, tu.last_name, tu.email, tu.username,
                        seg.title AS segment_title
                 FROM passoff_requests pr
                 JOIN training_users tu ON pr.user_id = tu.id
                 LEFT JOIN segments seg ON pr.segment_id = seg.id
                 WHERE pr.status = 'pending'
                 ORDER BY pr.requested_at ASC
                 LIMIT " . (int)$limit);
            $s->execute();
            return $s->fetchAll();
        } catch (Exception $e) { return []; }
    }

    public function markNotified($requestId) {
        try {
            $this->db->prepare("UPDATE passoff_requests SET notified_at=NOW() WHERE id=?")->execute([$requestId]);
        } catch (Exception $e) {}
    }

    // ================================================================
    // ORG HIERARCHY
    // ================================================================

    /** Direct reports of a trainer/leader. */
    public function getReps($trainerId) {
        $s = $this->db->prepare("SELECT id, username, first_name, last_name, email, role, full_access, created_at FROM training_users WHERE parent_id=? AND is_active=1 ORDER BY first_name, last_name");
        $s->execute([$trainerId]);
        return $s->fetchAll();
    }

    /** All user IDs at or beneath a given user (self + descendants). */
    public function getSubtreeUserIds($rootId) {
        $ids = [(int)$rootId];
        $frontier = [(int)$rootId];
        $guard = 0;
        while ($frontier && $guard++ < 50) {
            $in = implode(',', array_fill(0, count($frontier), '?'));
            $s = $this->db->prepare("SELECT id FROM training_users WHERE parent_id IN ($in) AND is_active=1");
            $s->execute($frontier);
            $next = array_map('intval', array_column($s->fetchAll(), 'id'));
            $next = array_values(array_diff($next, $ids));
            $ids = array_merge($ids, $next);
            $frontier = $next;
        }
        return $ids;
    }

    // ================================================================
    // NEXT ACTION RESOLVER  (suggests order; never locks)
    // ================================================================

    public function resolveNextAction($userId) {
        $progress  = $this->getUserProgress($userId);
        $userLevel = (int)$progress['level'];
        $compSegs  = $this->getCompletedSegmentIds($userId);
        $compMods  = $this->getCompletedModuleIds($userId);

        $s = $this->db->prepare("SELECT m.id, m.title, m.icon, m.unlock_rule, m.module_order, m.folder_id,
            f.title AS folder_title
            FROM modules m JOIN folders f ON m.folder_id = f.id
            WHERE m.is_active=1 ORDER BY m.module_order");
        $s->execute();
        $allMods = $s->fetchAll();

        $byLevel = [];
        foreach ($allMods as $m) {
            $rule = json_decode($m['unlock_rule'] ?? 'null', true);
            $kind = $rule['kind'] ?? '';
            if ($kind === 'open' || $kind === 'engineer') continue;
            $lvl = ($kind === 'level') ? (int)$rule['value'] : 0;
            $stage = (int)($m['module_order'] ?? 1);
            $byLevel[$lvl][$stage][] = $m;
        }
        ksort($byLevel);

        foreach ($byLevel as $lvl => $stages) {
            if ($lvl > $userLevel) break;
            ksort($stages);
            foreach ($stages as $mods) {
                foreach ($mods as $m) {
                    if (in_array((int)$m['id'], $compMods)) continue;
                    $s = $this->db->prepare("SELECT id, title FROM segments WHERE module_id=? AND is_active=1 ORDER BY segment_order");
                    $s->execute([$m['id']]);
                    foreach ($s->fetchAll() as $seg) {
                        if (!in_array((int)$seg['id'], $compSegs)) {
                            return [
                                'type'          => 'segment',
                                'module_id'     => (int)$m['id'],
                                'module_title'  => $m['title'],
                                'folder_title'  => $m['folder_title'],
                                'segment_id'    => (int)$seg['id'],
                                'segment_title' => $seg['title'],
                                'label'         => $m['folder_title'] . ' → ' . $m['title'],
                            ];
                        }
                    }
                }
            }
        }

        // Level finished → point at the pass-off gate.
        $state = $this->getCurrentLevelState($userId);
        if ($state['all_complete'] && !$state['is_max_level'] && !$state['full_access']) {
            return [
                'type'  => 'level_passoff',
                'level' => $userLevel,
                'label' => $state['passoff_status'] === 'pending'
                    ? 'Pass-off requested — waiting for a leader to approve.'
                    : 'Level complete! Request your pass-off to unlock the next level.',
                'passoff_status' => $state['passoff_status'],
                'request_id'     => $state['request_id'],
            ];
        }

        return ['type' => 'all_complete', 'label' => 'All training complete! 🌟'];
    }

    public function resolveNextStage($userId) {
        $progress  = $this->getUserProgress($userId);
        $userLevel = (int)$progress['level'];
        $compMods  = $this->getCompletedModuleIds($userId);
        $compSegs  = $this->getCompletedSegmentIds($userId);

        $s = $this->db->prepare("SELECT m.id, m.title, m.icon, m.unlock_rule, m.module_order, m.folder_id,
            f.title AS folder_title, f.icon AS folder_icon
            FROM modules m JOIN folders f ON m.folder_id = f.id
            WHERE m.is_active=1 ORDER BY m.module_order");
        $s->execute();
        $allMods = $s->fetchAll();

        $byLevel = [];
        foreach ($allMods as $m) {
            $rule = json_decode($m['unlock_rule'] ?? 'null', true);
            $kind = $rule['kind'] ?? '';
            if ($kind === 'open' || $kind === 'engineer') continue;
            $lvl = ($kind === 'level') ? (int)$rule['value'] : 0;
            $stage = (int)($m['module_order'] ?? 1);
            $byLevel[$lvl][$stage][] = $m;
        }
        ksort($byLevel);

        foreach ($byLevel as $lvl => $stages) {
            if ($lvl > $userLevel) break;
            ksort($stages);
            foreach ($stages as $stageNum => $mods) {
                $incompleteMods = [];
                foreach ($mods as $m) {
                    if (!in_array((int)$m['id'], $compMods)) {
                        $s = $this->db->prepare("SELECT id, title FROM segments WHERE module_id=? AND is_active=1 ORDER BY segment_order");
                        $s->execute([$m['id']]);
                        $segs = $s->fetchAll();
                        $firstSeg = null;
                        foreach ($segs as $seg) {
                            if (!in_array((int)$seg['id'], $compSegs)) { $firstSeg = $seg; break; }
                        }
                        $incompleteMods[] = [
                            'module_id'     => (int)$m['id'],
                            'module_title'  => $m['title'],
                            'module_icon'   => $m['icon'] ?? '📄',
                            'folder_title'  => $m['folder_title'],
                            'folder_icon'   => $m['folder_icon'] ?? '📁',
                            'segment_id'    => $firstSeg ? (int)$firstSeg['id'] : null,
                            'segment_title' => $firstSeg ? $firstSeg['title'] : null,
                            'level'         => $lvl,
                            'stage'         => $stageNum,
                        ];
                    }
                }
                if (count($incompleteMods) > 0) {
                    return [
                        'type'    => 'next_stage',
                        'level'   => $lvl,
                        'stage'   => $stageNum,
                        'count'   => count($incompleteMods),
                        'modules' => $incompleteMods,
                    ];
                }
            }
        }

        $state = $this->getCurrentLevelState($userId);
        if ($state['all_complete'] && !$state['is_max_level'] && !$state['full_access']) {
            return ['type' => 'level_passoff', 'level' => $userLevel, 'passoff_status' => $state['passoff_status']];
        }
        return ['type' => 'all_complete', 'label' => 'All training complete! 🌟'];
    }

    // ================================================================
    // COMPLETE SEGMENT  (awards XP; does NOT auto-advance level)
    // ================================================================

    public function calculateSegmentXp($moduleId) {
        $s = $this->db->prepare("SELECT unlock_rule FROM modules WHERE id=?");
        $s->execute([$moduleId]);
        $mod = $s->fetch();
        if (!$mod) return 10;

        $rule = json_decode($mod['unlock_rule'] ?? 'null', true);
        $kind = $rule['kind'] ?? '';
        if ($kind === 'open' || $kind === 'engineer') return 10;

        $modLevel = ($kind === 'level') ? (int)$rule['value'] : 0;

        $thresholds = $this->thresholds();
        $currentXp = 0; $nextXp = null;
        foreach ($thresholds as $t) {
            if ((int)$t['level'] === $modLevel)     $currentXp = (int)$t['xp_required'];
            if ((int)$t['level'] === $modLevel + 1) $nextXp     = (int)$t['xp_required'];
        }
        if ($nextXp === null) $nextXp = $currentXp + 500;
        $levelRange = $nextXp - $currentXp;
        if ($levelRange <= 0) $levelRange = 150;

        $s = $this->db->prepare("SELECT id, unlock_rule FROM modules WHERE is_active=1");
        $s->execute();
        $totalSegs = 0;
        foreach ($s->fetchAll() as $m) {
            $r = json_decode($m['unlock_rule'] ?? 'null', true);
            $k = $r['kind'] ?? '';
            if ($k === 'open' || $k === 'engineer') continue;
            $ml = ($k === 'level') ? (int)$r['value'] : 0;
            if ($ml === $modLevel) {
                $s2 = $this->db->prepare("SELECT COUNT(*) c FROM segments WHERE module_id=? AND is_active=1");
                $s2->execute([$m['id']]);
                $totalSegs += (int)$s2->fetch()['c'];
            }
        }
        if ($totalSegs <= 0) return 10;
        return max(1, (int)floor($levelRange / $totalSegs));
    }

    public function completeSegment($userId, $segmentId) {
        $this->db->beginTransaction();
        try {
            $s = $this->db->prepare("SELECT id FROM completed_segments WHERE user_id=? AND segment_id=?");
            $s->execute([$userId, $segmentId]);
            if ($s->fetch()) { $this->db->commit(); return ['already_completed' => true]; }

            $s = $this->db->prepare("SELECT s.*, m.id AS mid, m.folder_id FROM segments s JOIN modules m ON s.module_id=m.id WHERE s.id=?");
            $s->execute([$segmentId]);
            $seg = $s->fetch();
            if (!$seg) throw new Exception("Segment not found");

            $xp = 0; $events = [];

            $segXp = 0; // XP system removed — progression is completion + pass-off only
            $s = $this->db->prepare("INSERT INTO completed_segments (user_id,segment_id,xp_awarded) VALUES (?,?,?)");
            $s->execute([$userId, $segmentId, $segXp]);
            $events[] = ['type'=>'segment_complete','id'=>$segmentId];

            // Module completion?
            $mid = (int)$seg['mid'];
            $s = $this->db->prepare("SELECT COUNT(*) c FROM segments WHERE module_id=? AND is_active=1");
            $s->execute([$mid]);
            $total = (int)$s->fetch()['c'];
            $s = $this->db->prepare("SELECT COUNT(*) c FROM completed_segments cs JOIN segments sg ON cs.segment_id=sg.id WHERE cs.user_id=? AND sg.module_id=?");
            $s->execute([$userId, $mid]);
            $done = (int)$s->fetch()['c'];

            if ($done >= $total && $total > 0) {
                $s = $this->db->prepare("INSERT IGNORE INTO completed_modules (user_id,module_id,xp_awarded) VALUES (?,?,?)");
                $s->execute([$userId, $mid, 0]);
                if ($s->rowCount()) $events[] = ['type'=>'module_complete','id'=>$mid,'xp'=>0];

                $fid = (int)$seg['folder_id'];
                $s = $this->db->prepare("SELECT COUNT(*) c FROM modules WHERE folder_id=? AND is_active=1");
                $s->execute([$fid]);
                $mTotal = (int)$s->fetch()['c'];
                $s = $this->db->prepare("SELECT COUNT(*) c FROM completed_modules cm JOIN modules mo ON cm.module_id=mo.id WHERE cm.user_id=? AND mo.folder_id=?");
                $s->execute([$userId, $fid]);
                $mDone = (int)$s->fetch()['c'];
                if ($mDone >= $mTotal && $mTotal > 0) {
                    $s = $this->db->prepare("INSERT IGNORE INTO completed_folders (user_id,folder_id,xp_awarded) VALUES (?,?,?)");
                    $s->execute([$userId, $fid, 0]);
                    if ($s->rowCount()) $events[] = ['type'=>'folder_complete','id'=>$fid,'xp'=>0];
                }
            }

            // Award XP (cosmetic / leaderboard). LEVEL IS NOT AUTO-ADVANCED.
            $s = $this->db->prepare("UPDATE user_progress SET xp=xp+?, last_activity=NOW() WHERE user_id=?");
            $s->execute([$xp, $userId]);

            // If this completion finished the level, surface eligibility as an event.
            $state = $this->getCurrentLevelState($userId);
            if ($state['eligible']) {
                $events[] = ['type'=>'level_passoff_ready','level'=>$state['level']];
            }

            $s = $this->db->prepare("INSERT INTO activity_log (user_id,action,entity_type,entity_id,xp_change,details) VALUES (?,'complete_segment','segment',?,?,?)");
            $s->execute([$userId, $segmentId, $xp, json_encode($events)]);

            $this->db->commit();
            return ['success'=>true, 'xp_awarded'=>$xp, 'events'=>$events,
                    'level_state'=>$state, 'next_action'=>$this->resolveNextAction($userId)];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ================================================================
    // MARKDOWN IMPORT (admin)
    // ================================================================

    public function importMarkdown($folderId, $title, $markdown, $unlockRule = null) {
        $this->db->beginTransaction();
        try {
            $s = $this->db->prepare("SELECT COALESCE(MAX(module_order),0)+1 n FROM modules WHERE folder_id=?");
            $s->execute([$folderId]);
            $order = (int)$s->fetch()['n'];

            $s = $this->db->prepare("INSERT INTO modules (folder_id,title,module_order,unlock_rule) VALUES (?,?,?,?)");
            $s->execute([$folderId, $title, $order, $unlockRule ? json_encode($unlockRule) : null]);
            $mid = (int)$this->db->lastInsertId();

            $parts = preg_split('/\n---\n|\n---$|^---\n/', $markdown);
            $parts = array_values(array_filter($parts, fn($p) => trim($p) !== ''));

            $segs = [];
            foreach ($parts as $i => $raw) {
                $raw = trim($raw);
                $segTitle = 'Segment ' . ($i+1);
                if (preg_match('/^##\s+(.+)$/m', $raw, $m)) $segTitle = trim($m[1]);
                $html = $this->md2html($raw);
                $s = $this->db->prepare("INSERT INTO segments (module_id,title,segment_order,content_html) VALUES (?,?,?,?)");
                $s->execute([$mid, $segTitle, $i+1, $html]);
                $segs[] = ['id'=>(int)$this->db->lastInsertId(), 'title'=>$segTitle];
            }

            $this->db->commit();
            return ['module_id'=>$mid, 'title'=>$title, 'segments'=>$segs];
        } catch (Exception $e) { $this->db->rollBack(); throw $e; }
    }

    private function md2html($md) {
        $h = htmlspecialchars($md, ENT_QUOTES, 'UTF-8');
        $h = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $h);
        $h = preg_replace('/^## (.+)$/m',  '<h2>$1</h2>', $h);
        $h = preg_replace('/^# (.+)$/m',   '<h1>$1</h1>', $h);
        $h = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $h);
        $h = preg_replace('/\*(.+?)\*/',     '<em>$1</em>', $h);
        return nl2br($h);
    }

    // ================================================================
    // CONTENT EDITING (leaders)
    // ================================================================

    public function updateSegment($segId, $html) {
        $s = $this->db->prepare("UPDATE segments SET content_html=?, updated_at=NOW() WHERE id=?");
        $s->execute([$html, $segId]);
    }

    public function updateModule($modId, $data) {
        $ok = ['title','description','next_step_text','icon'];
        $sets = []; $vals = [];
        foreach ($ok as $k) { if (isset($data[$k])) { $sets[] = "$k=?"; $vals[] = $data[$k]; } }
        if (!$sets) return;
        $vals[] = $modId;
        $this->db->prepare("UPDATE modules SET ".implode(',',$sets).",updated_at=NOW() WHERE id=?")->execute($vals);
    }

    public function manualUnlock($userId, $type, $entityId, $by = null) {
        $s = $this->db->prepare("INSERT IGNORE INTO manual_unlocks (user_id,entity_type,entity_id,unlocked_by) VALUES (?,?,?,?)");
        $s->execute([$userId, $type, $entityId, $by]);
    }

    // ================================================================
    // STATS (dashboard)
    // ================================================================

    public function getUserStats($userId) {
        $p  = $this->getUserProgress($userId);
        $th = $this->thresholds();
        $full = $this->isFullAccess($userId);

        $curTh = 0; $nextTh = null; $curInfo = null;
        foreach ($th as $t) {
            if ((int)$t['level'] === (int)$p['level'])     { $curTh = (int)$t['xp_required']; $curInfo = $t; }
            if ((int)$t['level'] === (int)$p['level'] + 1) $nextTh = $t;
        }

        // Progress through the current level is now measured by module completion,
        // not raw XP, since levels advance via pass-off.
        $levelState = $this->getCurrentLevelState($userId);
        $lvlPct = $levelState['modules_total'] > 0
            ? round(($levelState['modules_done'] / $levelState['modules_total']) * 100)
            : ($full ? 100 : 0);

        return [
            'xp'                  => (int)$p['xp'],
            'level'               => (int)$p['level'],
            'level_title'         => $curInfo ? ($curInfo['title'] ?? 'Newbie') : 'Newbie',
            'level_icon'          => $curInfo ? ($curInfo['badge_icon'] ?? '👶') : '👶',
            'full_access'         => $full,
            'level_progress'      => $lvlPct,
            'level_modules_done'  => $levelState['modules_done'],
            'level_modules_total' => $levelState['modules_total'],
            'passoff_eligible'    => $levelState['eligible'],
            'passoff_status'      => $levelState['passoff_status'],
            'passoff_request_id'  => $levelState['request_id'],
            'is_max_level'        => $levelState['is_max_level'],
            'next_level_title'    => $nextTh ? ($nextTh['title'] ?? null) : null,
            'next_level_icon'     => $nextTh ? ($nextTh['badge_icon'] ?? null) : null,
            'join_date'           => $p['join_date'],
            'days_active'         => max(1, (int)((time()-strtotime($p['join_date']))/86400)),
            'completed_segments'  => count($this->getCompletedSegmentIds($userId)),
            'completed_modules'   => count($this->getCompletedModuleIds($userId)),
            'completed_folders'   => count($this->getCompletedFolderIds($userId)),
        ];
    }
}
