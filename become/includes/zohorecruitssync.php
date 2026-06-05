<?php
/**
 * become/includes/ZohoRecruitSync.php — Two-way Recruits <-> portal sync
 * Location: public_html/become/includes/ZohoRecruitSync.php
 *
 * One scan of the Recruits module does everything:
 *   - HIRED status, no portal login yet     -> create login (seed Level + Role from Zoho), email temp creds
 *   - HIRED status, login deactivated        -> reactivate
 *   - INACTIVE status ("no longer working")   -> deactivate the portal login
 *   - Any linked record:
 *       Role:  Zoho -> Become   (Zoho is authoritative; Engineer maps to full_access)
 *       Level: Become -> Zoho   (training is authoritative; pushed back to Zoho)
 *
 * Level and Role move in opposite directions, so they never fight.
 * All field/status/role names come from config.php.
 *
 * Level field type: set 'zoho_level_is_number' => true if Level is a Number
 * field (default), or false if it's a picklist of "0".."10" strings.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ZohoClient.php';
require_once __DIR__ . '/mailer.php';

class ZohoRecruitSync {
    private $db, $zoho, $cfg;

    public function __construct() {
        $this->db   = Database::getInstance();
        $this->zoho = new ZohoClient();
        $path = __DIR__ . '/../../config.php';
        $this->cfg = file_exists($path) ? (require $path) : [];
    }

    private function cfg($k, $d = null) { return $this->cfg[$k] ?? $d; }

    private function asList($v) {
        if (is_array($v)) return array_values(array_filter(array_map('trim', $v), 'strlen'));
        if (is_string($v) && trim($v) !== '') return [trim($v)];
        return [];
    }

    /** Format a level for the Zoho field: integer for a Number field, string for a picklist. */
    private function levelValue($lvl) {
        $lvl = (int)$lvl;
        return !empty($this->cfg('zoho_level_is_number', true)) ? $lvl : (string)$lvl;
    }

    private function roleMap() {
        $m = $this->cfg('zoho_role_map', [
            'Admin'    => ['admin', 0],
            'Trainer'  => ['trainer', 0],
            'Rep'      => ['rep', 0],
            'Engineer' => ['rep', 1],
        ]);
        return is_array($m) ? $m : [];
    }

    /** Map a Zoho Role value -> [portal_role, full_access] or null if unknown. */
    private function mapRole($zRole) {
        if ($zRole === null || $zRole === '') return null;
        $map = $this->roleMap();
        if (isset($map[$zRole]) && is_array($map[$zRole])) {
            return [$map[$zRole][0] ?? 'rep', (int)($map[$zRole][1] ?? 0)];
        }
        return null;
    }

    public function run() {
        if (!$this->zoho->isConfigured()) {
            return ['ok' => false, 'error' => 'Zoho credentials are not set in config.php.'];
        }

        $module   = $this->cfg('zoho_recruits_module', 'Recruits');
        $fEmail   = $this->cfg('zoho_field_email', 'Email');
        $fFirst   = $this->cfg('zoho_field_first', 'Name');
        $fLast    = $this->cfg('zoho_field_last', 'Last_Name');
        $fStatus  = $this->cfg('zoho_field_status', 'Status');
        $fLevel   = $this->cfg('zoho_field_level', 'Level');
        $fRole    = $this->cfg('zoho_field_role', 'Role');
        $hired    = $this->asList($this->cfg('zoho_status_hired', ['Hired']));
        $inactive = $this->asList($this->cfg('zoho_status_inactive', ['No Longer Working']));

        $fields = implode(',', array_unique([$fEmail, $fFirst, $fLast, $fStatus, $fLevel, $fRole]));

        $summary = ['ok'=>true,'created'=>0,'reactivated'=>0,'deactivated'=>0,
                    'role_synced'=>0,'level_synced'=>0,'skipped'=>0,'errors'=>[]];

        try {
            $records = $this->zoho->getAllRecords($module, $fields);
        } catch (Exception $e) {
            return ['ok'=>false,'error'=>'Could not read Recruits: '.$e->getMessage()];
        }

        $levelPush = [];   // records to push Become level back to Zoho

        foreach ($records as $r) {
            try {
                $email  = trim($r[$fEmail] ?? '');
                $recId  = (string)($r['id'] ?? '');
                $status = $r[$fStatus] ?? '';
                $zRole  = $r[$fRole] ?? '';
                $zLevel = $r[$fLevel] ?? null;

                $isHired    = in_array($status, $hired, true);
                $isInactive = in_array($status, $inactive, true);

                $existing = $this->findUser($recId, $email);

                if ($existing) {
                    if (empty($existing['zoho_record_id']) && $recId !== '') {
                        $this->db->prepare("UPDATE training_users SET zoho_record_id=? WHERE id=?")->execute([$recId, $existing['id']]);
                    }

                    if ($isInactive) {
                        if ((int)$existing['is_active'] === 1) {
                            $this->db->prepare("UPDATE training_users SET is_active=0 WHERE id=?")->execute([$existing['id']]);
                            $summary['deactivated']++;
                        } else { $summary['skipped']++; }
                        continue;
                    }

                    if ((int)$existing['is_active'] === 0 && $isHired) {
                        $this->db->prepare("UPDATE training_users SET is_active=1 WHERE id=?")->execute([$existing['id']]);
                        $summary['reactivated']++;
                    }

                    // Role: Zoho -> Become
                    $mapped = $this->mapRole($zRole);
                    if ($mapped) {
                        [$newRole, $newFull] = $mapped;
                        if ((int)$existing['full_access'] !== $newFull || $existing['role'] !== $newRole) {
                            $this->db->prepare("UPDATE training_users SET role=?, full_access=? WHERE id=?")
                                     ->execute([$newRole, $newFull, $existing['id']]);
                            $summary['role_synced']++;
                        }
                    }

                    // Level: Become -> Zoho
                    $becomeLevel = (int)$existing['level'];
                    if ($recId !== '' && (int)$zLevel !== $becomeLevel) {
                        $levelPush[] = ['id' => $recId, $fLevel => $this->levelValue($becomeLevel)];
                    }
                    continue;
                }

                // No portal user yet.
                if ($isHired) {
                    $this->provision($r, $fEmail, $fFirst, $fLast, $zRole, $zLevel, $recId, $summary);
                } else {
                    $summary['skipped']++;
                }
            } catch (Exception $e) {
                $summary['errors'][] = $e->getMessage();
            }
        }

        if ($levelPush) {
            try { $summary['level_synced'] = $this->zoho->updateRecords($module, $levelPush); }
            catch (Exception $e) { $summary['errors'][] = 'Level push failed: '.$e->getMessage(); }
        }

        return $summary;
    }

    /** Lookup a portal user by Zoho record id, then by email. Returns row incl. level/role/full_access. */
    private function findUser($recordId, $email) {
        $sql = "SELECT u.id, u.is_active, u.role, u.full_access, u.zoho_record_id, COALESCE(p.level,0) AS level
                FROM training_users u LEFT JOIN user_progress p ON p.user_id = u.id ";
        if ($recordId !== '') {
            $s = $this->db->prepare($sql . "WHERE u.zoho_record_id = ? LIMIT 1");
            $s->execute([$recordId]);
            if ($u = $s->fetch()) return $u;
        }
        if ($email) {
            $s = $this->db->prepare($sql . "WHERE u.email = ? OR u.username = ? LIMIT 1");
            $s->execute([$email, $email]);
            if ($u = $s->fetch()) return $u;
        }
        return null;
    }

    private function provision($r, $fEmail, $fFirst, $fLast, $zRole, $zLevel, $recId, &$summary) {
        $email = trim($r[$fEmail] ?? '');
        if ($email === '') { $summary['skipped']++; return; }

        $first = trim($r[$fFirst] ?? '');
        $last  = trim($r[$fLast] ?? '');

        $mapped = $this->mapRole($zRole);
        $role = $mapped ? $mapped[0] : $this->cfg('zoho_default_role', 'rep');
        $full = $mapped ? $mapped[1] : 0;

        $level = ($zLevel === null || $zLevel === '') ? (int)$this->cfg('zoho_default_level', 0) : (int)$zLevel;

        $parent = $this->cfg('zoho_default_parent_id', null);
        $parent = ($parent === '' || $parent === null) ? null : (int)$parent;

        $tempPw = 'Solar-' . strtoupper(bin2hex(random_bytes(3)));
        $hash   = password_hash($tempPw, PASSWORD_DEFAULT);

        $s = $this->db->prepare("INSERT INTO training_users
            (username, first_name, last_name, email, password_hash, role, parent_id, full_access, zoho_record_id, source)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'zoho')");
        $s->execute([$email, $first, $last, $email, $hash, $role, $parent, $full, $recId ?: null]);
        $newId = (int)$this->db->lastInsertId();
        $this->db->prepare("INSERT INTO user_progress (user_id, xp, level, join_date) VALUES (?, 0, ?, CURDATE())")
                 ->execute([$newId, $level]);

        $this->sendWelcome($email, $first, $tempPw);

        try {
            $this->db->prepare("INSERT INTO activity_log (user_id,action,entity_type,entity_id,xp_change,details) VALUES (?,'zoho_provision','user',?,0,?)")
                     ->execute([$newId, $newId, json_encode(['zoho_id'=>$recId,'role'=>$role,'level'=>$level])]);
        } catch (Exception $e) {}

        $summary['created']++;
    }

    private function sendWelcome($email, $first, $tempPw) {
        $cfg = portal_mail_config();
        $site = rtrim($cfg['site_url'], '/');
        $hi = $first !== '' ? $first : 'there';
        $subject = 'Your Your Energy Best training login';
        $body = "Hi {$hi},\n\n"
              . "Welcome aboard! Your training portal account is ready.\n\n"
              . "Log in here: {$site}/become/login.php\n"
              . "Email: {$email}\n"
              . "Temporary password: {$tempPw}\n\n"
              . "Please log in and change your password. Work through each level and pass off to a leader to advance.\n\n"
              . "- Your Energy Best\n";
        portal_send_mail($email, $subject, $body);
    }

    /**
     * Immediately push one user's current Become level to their Zoho record.
     * Best-effort: silently does nothing if unconfigured / not linked / on error.
     */
    public static function pushLevelForUser($userId) {
        try {
            $self = new self();
            if (!$self->zoho->isConfigured()) return;
            $s = $self->db->prepare("SELECT u.zoho_record_id, COALESCE(p.level,0) AS level
                                     FROM training_users u LEFT JOIN user_progress p ON p.user_id=u.id WHERE u.id=?");
            $s->execute([$userId]);
            $row = $s->fetch();
            if (!$row || empty($row['zoho_record_id'])) return;
            $module = $self->cfg('zoho_recruits_module', 'Recruits');
            $fLevel = $self->cfg('zoho_field_level', 'Level');
            $self->zoho->updateRecords($module, [['id' => $row['zoho_record_id'], $fLevel => $self->levelValue($row['level'])]]);
        } catch (Exception $e) { /* non-fatal */ }
    }
}
