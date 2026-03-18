<?php
/**
 * become/includes/ProgressionEngine.php
 * 
 * Core engine: unlock checks, XP, levels, next action, completion, markdown import.
 * Location: public_html/become/includes/ProgressionEngine.php
 */

require_once __DIR__ . '/db.php';

class ProgressionEngine {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ================================================================
    // LEVELS
    // ================================================================

    public function getLevelThresholds() {
        return $this->db->prepare("SELECT * FROM level_thresholds ORDER BY level")->execute() 
            ? $this->db->prepare("SELECT * FROM level_thresholds ORDER BY level")->fetchAll() 
            : [];
    }

    private $thresholdsCache = null;
    private function thresholds() {
        if (!$this->thresholdsCache) {
            $s = $this->db->prepare("SELECT * FROM level_thresholds ORDER BY level");
            $s->execute();
            $this->thresholdsCache = $s->fetchAll();
        }
        return $this->thresholdsCache;
    }

    public function computeLevel($xp) {
        $level = 1;
        foreach ($this->thresholds() as $t) {
            if ($xp >= (int)$t['xp_required']) $level = (int)$t['level'];
        }
        return $level;
    }

    // ================================================================
    // USER PROGRESS
    // ================================================================

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
    // UNLOCK ENGINE
    // ================================================================

    public function getAccessibleContent($userId) {
        $progress      = $this->getUserProgress($userId);
        $compSegs      = $this->getCompletedSegmentIds($userId);
        $compMods      = $this->getCompletedModuleIds($userId);
        $compFolders   = $this->getCompletedFolderIds($userId);
        $manualUnlocks = $this->getManualUnlocks($userId);
        $daysSince     = max(0, (int)((time() - strtotime($progress['join_date'])) / 86400));
        $lvl           = (int)$progress['level'];

        // Load everything in 3 queries
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

        // Build tree
        $result = [];
        foreach ($allFolders as $folder) {
            $fid = (int)$folder['id'];
            $folder['locked']    = !$this->checkFolderUnlock($folder, $lvl, $daysSince, $manualUnlocks);
            $folder['completed'] = in_array($fid, $compFolders);
            $folder['modules']   = [];

            $prevModDone = true;
            foreach ($modsByFolder[$fid] ?? [] as $mod) {
                $mid = (int)$mod['id'];
                $mod['locked']    = $folder['locked'] || !$this->checkModuleUnlock($mod, $lvl, $daysSince, $prevModDone, $manualUnlocks);
                $mod['completed'] = in_array($mid, $compMods);
                $mod['segments']  = [];

                $prevSegDone = true;
                foreach ($segsByMod[$mid] ?? [] as $seg) {
                    $sid = (int)$seg['id'];
                    $seg['locked']    = $mod['locked'] || !$this->checkSegmentUnlock($seg, $lvl, $daysSince, $prevSegDone, $manualUnlocks);
                    $seg['completed'] = in_array($sid, $compSegs);
                    $prevSegDone      = $seg['completed'];
                    $mod['segments'][] = $seg;
                }

                $total = count($mod['segments']);
                $done  = count(array_filter($mod['segments'], fn($s) => $s['completed']));
                $mod['progress']           = $total > 0 ? round(($done/$total)*100) : 0;
                $mod['segments_total']     = $total;
                $mod['segments_completed'] = $done;

                $prevModDone = $mod['completed'];
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

    private function checkFolderUnlock($f, $lvl, $days, $mu) {
        if (in_array((int)$f['id'], $mu['folder'])) return true;
        $r = json_decode($f['unlock_rule'] ?? 'null', true);
        return !$r || $this->evalRule($r, $lvl, $days);
    }

    private function checkModuleUnlock($m, $lvl, $days, $prevDone, $mu) {
        if (in_array((int)$m['id'], $mu['module'])) return true;
        $drip = json_decode($m['drip_rule'] ?? 'null', true);
        if ($drip && $days < ($drip['startAfterDays'] ?? 0)) return false;
        $r = json_decode($m['unlock_rule'] ?? 'null', true);
        if (!$r) return $prevDone; // sequential default
        if (($r['kind'] ?? '') === 'previousModule') return $prevDone;
        return $this->evalRule($r, $lvl, $days);
    }

    private function checkSegmentUnlock($seg, $lvl, $days, $prevDone, $mu) {
        if (in_array((int)$seg['id'], $mu['segment'])) return true;
        $r = json_decode($seg['unlock_rule'] ?? 'null', true);
        if (!$r) return $prevDone;
        if (($r['kind'] ?? '') === 'previousSegment') return $prevDone;
        return $this->evalRule($r, $lvl, $days);
    }

    private function evalRule($r, $lvl, $days) {
        $v = (int)($r['value'] ?? 0);
        switch ($r['kind'] ?? '') {
            case 'level':         return $lvl >= $v;
            case 'daysSinceJoin': return $days >= $v;
            case 'manual':        return false;
            default:              return true;
        }
    }

    // ================================================================
    // NEXT ACTION RESOLVER
    // ================================================================

    public function resolveNextAction($userId) {
        $content = $this->getAccessibleContent($userId);

        foreach ($content as $folder) {
            $r = $this->scanFolder($folder);
            if ($r) return $r;
            foreach ($folder['children'] ?? [] as $child) {
                $r = $this->scanFolder($child);
                if ($r) return $r;
            }
        }

        // All done — suggest level up
        $progress = $this->getUserProgress($userId);
        foreach ($this->thresholds() as $t) {
            if ((int)$t['level'] > (int)$progress['level']) {
                return [
                    'type' => 'level_up',
                    'label' => "Keep going to reach {$t['badge_icon']} {$t['title']}!",
                    'xp_needed' => (int)$t['xp_required'] - (int)$progress['xp'],
                ];
            }
        }
        return ['type' => 'all_complete', 'label' => 'All training complete! 🌟'];
    }

    private function scanFolder($folder) {
        if (!empty($folder['locked'])) return null;
        foreach ($folder['modules'] ?? [] as $mod) {
            if (!empty($mod['locked']) || !empty($mod['completed'])) continue;
            foreach ($mod['segments'] ?? [] as $seg) {
                if (!empty($seg['locked']) || !empty($seg['completed'])) continue;
                return [
                    'type'          => 'segment',
                    'folder_id'     => (int)$folder['id'],
                    'folder_title'  => $folder['title'],
                    'module_id'     => (int)$mod['id'],
                    'module_title'  => $mod['title'],
                    'segment_id'    => (int)$seg['id'],
                    'segment_title' => $seg['title'],
                    'label'         => $folder['title'] . ' → ' . $mod['title'],
                ];
            }
        }
        return null;
    }

    // ================================================================
    // COMPLETE SEGMENT
    // ================================================================

    /**
     * Calculate XP per segment dynamically based on level thresholds.
     * If a module is at Level 2, and Level 2 needs 400 XP and Level 3 needs 800 XP,
     * then the level range is 400 XP. If there are 8 segments across all Level 2 modules,
     * each segment is worth 50 XP. Module/folder bonuses are removed — all XP comes from segments.
     */
    public function calculateSegmentXp($moduleId) {
        // Get the module's unlock rule to determine its level
        $s = $this->db->prepare("SELECT unlock_rule FROM modules WHERE id=?");
        $s->execute([$moduleId]);
        $mod = $s->fetch();
        if (!$mod) return 10;

        $rule = json_decode($mod['unlock_rule'] ?? 'null', true);
        
        // "Open to all" modules don't contribute to leveling — fixed 10 XP
        if ($rule && ($rule['kind'] ?? '') === 'open') return 10;
        
        $modLevel = ($rule && ($rule['kind'] ?? '') === 'level') ? (int)$rule['value'] : 0;

        // Get thresholds for this level and next level
        $thresholds = $this->thresholds();
        $currentXp = 0;
        $nextXp = null;
        foreach ($thresholds as $t) {
            if ((int)$t['level'] === $modLevel) $currentXp = (int)$t['xp_required'];
            if ((int)$t['level'] === $modLevel + 1) $nextXp = (int)$t['xp_required'];
        }

        // If no next level, use a default range
        if ($nextXp === null) $nextXp = $currentXp + 500;
        $levelRange = $nextXp - $currentXp;
        if ($levelRange <= 0) $levelRange = 150;

        // Count total segments in all modules at this level
        $s = $this->db->prepare("SELECT m.id FROM modules m WHERE m.is_active=1");
        $s->execute();
        $allMods = $s->fetchAll();

        $totalSegs = 0;
        foreach ($allMods as $m) {
            $s = $this->db->prepare("SELECT unlock_rule FROM modules WHERE id=?");
            $s->execute([$m['id']]);
            $mr = $s->fetch();
            $r = json_decode($mr['unlock_rule'] ?? 'null', true);
            $ml = ($r && ($r['kind'] ?? '') === 'level') ? (int)$r['value'] : 0;
            if ($r && ($r['kind'] ?? '') === 'open') continue; // skip open modules
            if ($ml === $modLevel) {
                $s = $this->db->prepare("SELECT COUNT(*) c FROM segments WHERE module_id=? AND is_active=1");
                $s->execute([$m['id']]);
                $totalSegs += (int)$s->fetch()['c'];
            }
        }

        if ($totalSegs <= 0) return 10;
        return max(1, (int)round($levelRange / $totalSegs));
    }

    public function completeSegment($userId, $segmentId) {
        $this->db->beginTransaction();
        try {
            // Already done?
            $s = $this->db->prepare("SELECT id FROM completed_segments WHERE user_id=? AND segment_id=?");
            $s->execute([$userId, $segmentId]);
            if ($s->fetch()) { $this->db->commit(); return ['already_completed' => true]; }

            // Get segment + module info
            $s = $this->db->prepare("SELECT s.*, m.id AS mid, m.folder_id, m.xp_reward AS mod_xp, f.xp_reward AS folder_xp FROM segments s JOIN modules m ON s.module_id=m.id JOIN folders f ON m.folder_id=f.id WHERE s.id=?");
            $s->execute([$segmentId]);
            $seg = $s->fetch();
            if (!$seg) throw new Exception("Segment not found");

            $xp = 0;
            $events = [];

            // 1) Complete segment — XP calculated dynamically based on level
            $segXp = $this->calculateSegmentXp((int)$seg['mid']);
            $s = $this->db->prepare("INSERT INTO completed_segments (user_id,segment_id,xp_awarded) VALUES (?,?,?)");
            $s->execute([$userId, $segmentId, $segXp]);
            $xp += $segXp;
            $events[] = ['type'=>'segment_complete','id'=>$segmentId,'xp'=>$segXp];

            // 2) Check module completion
            $mid = (int)$seg['mid'];
            $s = $this->db->prepare("SELECT COUNT(*) c FROM segments WHERE module_id=? AND is_active=1");
            $s->execute([$mid]);
            $total = (int)$s->fetch()['c'];
            $s = $this->db->prepare("SELECT COUNT(*) c FROM completed_segments cs JOIN segments sg ON cs.segment_id=sg.id WHERE cs.user_id=? AND sg.module_id=?");
            $s->execute([$userId, $mid]);
            $done = (int)$s->fetch()['c'];

            if ($done >= $total && $total > 0) {
                // Module complete — no bonus XP (all XP from segments)
                $s = $this->db->prepare("INSERT IGNORE INTO completed_modules (user_id,module_id,xp_awarded) VALUES (?,?,?)");
                $s->execute([$userId, $mid, 0]);
                if ($s->rowCount()) { $events[] = ['type'=>'module_complete','id'=>$mid,'xp'=>0]; }

                // 3) Check folder completion
                $fid = (int)$seg['folder_id'];
                $s = $this->db->prepare("SELECT COUNT(*) c FROM modules WHERE folder_id=? AND is_active=1");
                $s->execute([$fid]);
                $mTotal = (int)$s->fetch()['c'];
                $s = $this->db->prepare("SELECT COUNT(*) c FROM completed_modules cm JOIN modules mo ON cm.module_id=mo.id WHERE cm.user_id=? AND mo.folder_id=?");
                $s->execute([$userId, $fid]);
                $mDone = (int)$s->fetch()['c'];

                if ($mDone >= $mTotal && $mTotal > 0) {
                    // Folder complete — no bonus XP
                    $s = $this->db->prepare("INSERT IGNORE INTO completed_folders (user_id,folder_id,xp_awarded) VALUES (?,?,?)");
                    $s->execute([$userId, $fid, 0]);
                    if ($s->rowCount()) { $events[] = ['type'=>'folder_complete','id'=>$fid,'xp'=>0]; }
                }
            }

            // 4) Award XP + recompute level
            $s = $this->db->prepare("UPDATE user_progress SET xp=xp+?, last_activity=NOW() WHERE user_id=?");
            $s->execute([$xp, $userId]);

            $progress = $this->getUserProgress($userId);
            $newLvl = $this->computeLevel((int)$progress['xp']);
            $oldLvl = (int)$progress['level'];
            if ($newLvl > $oldLvl) {
                $s = $this->db->prepare("UPDATE user_progress SET level=? WHERE user_id=?");
                $s->execute([$newLvl, $userId]);
                $events[] = ['type'=>'level_up','from'=>$oldLvl,'to'=>$newLvl];
            }

            // 5) Log
            $s = $this->db->prepare("INSERT INTO activity_log (user_id,action,entity_type,entity_id,xp_change,details) VALUES (?,'complete_segment','segment',?,?,?)");
            $s->execute([$userId, $segmentId, $xp, json_encode($events)]);

            $this->db->commit();
            return ['success'=>true, 'xp_awarded'=>$xp, 'events'=>$events, 'next_action'=>$this->resolveNextAction($userId)];
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
    // STATS (for dashboard)
    // ================================================================

    public function getUserStats($userId) {
        $p = $this->getUserProgress($userId);
        $th = $this->thresholds();

        $curTh = 0; $nextTh = null; $curInfo = null;
        foreach ($th as $t) {
            if ((int)$t['level'] === (int)$p['level']) { $curTh = (int)$t['xp_required']; $curInfo = $t; }
            if ((int)$t['level'] === (int)$p['level']+1) $nextTh = $t;
        }

        $xpIn  = (int)$p['xp'] - $curTh;
        $xpFor = $nextTh ? (int)$nextTh['xp_required'] - $curTh : 0;

        return [
            'xp'                 => (int)$p['xp'],
            'level'              => (int)$p['level'],
            'level_title'        => $curInfo['title'] ?? 'Rookie',
            'level_icon'         => $curInfo['badge_icon'] ?? '🌱',
            'xp_in_level'        => $xpIn,
            'xp_for_next_level'  => $xpFor,
            'level_progress'     => $xpFor > 0 ? round(($xpIn/$xpFor)*100) : 100,
            'next_level_title'   => $nextTh['title'] ?? null,
            'next_level_icon'    => $nextTh['badge_icon'] ?? null,
            'join_date'          => $p['join_date'],
            'days_active'        => max(1, (int)((time()-strtotime($p['join_date']))/86400)),
            'completed_segments' => count($this->getCompletedSegmentIds($userId)),
            'completed_modules'  => count($this->getCompletedModuleIds($userId)),
            'completed_folders'  => count($this->getCompletedFolderIds($userId)),
        ];
    }
}
