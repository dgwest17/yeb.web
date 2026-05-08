<?php
class AICoach {
    private $db;
    private $apiKey;
    private $model = 'claude-sonnet-4-6-20250514';

    public function __construct() {
        $this->db = Database::getInstance();
        $cfgPath = __DIR__ . '/../../config.php';
        $this->apiKey = '';
        if (file_exists($cfgPath)) {
            $cfg = require $cfgPath;
            if (isset($cfg['anthropic_api_key'])) $this->apiKey = $cfg['anthropic_api_key'];
        }
    }

    public function isConfigured() { return $this->apiKey !== ''; }

    public function chat($userId, $message, $conversationId = null, $mode = 'coach') {
        $history = [];
        if ($conversationId) {
            try {
                $s = $this->db->prepare("SELECT messages, mode FROM ai_conversations WHERE id=? AND user_id=?");
                $s->execute(array($conversationId, $userId));
                $row = $s->fetch();
                if ($row) {
                    $h = json_decode($row['messages'], true);
                    if (is_array($h)) $history = $h;
                    $mode = $row['mode'];
                }
            } catch (Exception $e) {}
        }
        $history[] = array('role' => 'user', 'content' => $message);
        if (count($history) > 20) $history = array_slice($history, -20);

        if ($this->apiKey !== '') {
            $sys = $this->buildSystemPrompt($mode, $message);
            $resp = $this->callClaude($sys, $history);
        } else {
            $resp = $this->demoResponse($mode, $message);
        }

        $history[] = array('role' => 'assistant', 'content' => $resp['text']);
        $tok = intval($resp['input_tokens']) + intval($resp['output_tokens']);

        try {
            if ($conversationId) {
                $s = $this->db->prepare("UPDATE ai_conversations SET messages=?, token_count=token_count+?, updated_at=NOW() WHERE id=?");
                $s->execute(array(json_encode($history), $tok, $conversationId));
            } else {
                $s = $this->db->prepare("INSERT INTO ai_conversations (user_id, mode, messages, token_count) VALUES (?, ?, ?, ?)");
                $s->execute(array($userId, $mode, json_encode($history), $tok));
                $conversationId = $this->db->lastInsertId();
            }
        } catch (Exception $e) {}

        return array('response' => $resp['text'], 'conversation_id' => intval($conversationId), 'tokens_used' => $tok, 'demo_mode' => ($this->apiKey === ''));
    }

    private function demoResponse($mode, $message) {
        $ctx = $this->retrieveContext($message);
        $hasCtx = (strpos($ctx, 'No specific training') === false);

        if ($mode === 'roleplay') {
            $lines = array(
                "Yeah? What do you need? I am kind of busy right now.\n\n---\nRoleplay: I am a homeowner. Respond like you would at the door. Say END ROLEPLAY for feedback.",
                "We already have solar quotes from three companies. Why should I go with you?\n\n---\nRoleplay: How do you differentiate?",
                "My wife handles this stuff. Come back when she is here.\n\n---\nRoleplay: Spouse concern. What do you do?",
                "Solar? No thanks, I heard those ruin your roof.\n\n---\nRoleplay: Misinformation. Educate without being pushy.",
                "That sounds expensive. How much is this going to cost me?\n\n---\nRoleplay: Price question at the door. Do you answer or redirect?"
            );
            return array('text' => $lines[array_rand($lines)], 'input_tokens' => 0, 'output_tokens' => 0);
        }

        if ($hasCtx) {
            $text = "Here is what I found in your training:\n\n" . $ctx . "\n---\nDemo mode: showing real training content. Add API key in config.php for full AI coaching.";
        } else {
            $text = "I could not find training content matching that. Try more specific keywords, or click the Index button to sync your content.\n\n---\nDemo mode: Add API key in config.php for full AI coaching.";
        }
        return array('text' => $text, 'input_tokens' => 0, 'output_tokens' => 0);
    }

    private function retrieveContext($query, $limit = 6) {
        $results = array();
        try {
            $s = $this->db->prepare("SELECT source_title, chunk_text FROM knowledge_base WHERE MATCH(chunk_text, source_title) AGAINST(? IN NATURAL LANGUAGE MODE) ORDER BY MATCH(chunk_text, source_title) AGAINST(? IN NATURAL LANGUAGE MODE) DESC LIMIT ?");
            $s->execute(array($query, $query, $limit));
            $results = $s->fetchAll();
        } catch (Exception $e) {}

        if (empty($results)) {
            $words = array();
            foreach (explode(' ', $query) as $w) { if (strlen($w) > 3) $words[] = $w; }
            $words = array_slice($words, 0, 3);
            if (!empty($words)) {
                $conds = array(); $params = array();
                foreach ($words as $w) { $conds[] = "chunk_text LIKE ?"; $params[] = '%' . $w . '%'; }
                $params[] = $limit;
                try {
                    $s = $this->db->prepare("SELECT source_title, chunk_text FROM knowledge_base WHERE " . implode(' OR ', $conds) . " LIMIT ?");
                    $s->execute($params);
                    $results = $s->fetchAll();
                } catch (Exception $e) {}
            }
        }
        if (empty($results)) return "(No specific training content found for this topic)";

        $out = '';
        foreach ($results as $r) { $out .= "**" . $r['source_title'] . "**\n" . $r['chunk_text'] . "\n\n"; }
        return $out;
    }

    private function buildSystemPrompt($mode, $msg) {
        $doctrine = '';
        try {
            $s = $this->db->prepare("SELECT rule_title, rule_text FROM doctrine_rules WHERE is_active=1 ORDER BY priority DESC");
            $s->execute();
            foreach ($s->fetchAll() as $r) { $doctrine .= "- " . $r['rule_title'] . ": " . $r['rule_text'] . "\n"; }
        } catch (Exception $e) {}
        $ctx = $this->retrieveContext($msg);
        if ($mode === 'roleplay') return "You are a HOMEOWNER in a solar sales roleplay. Be realistic and skeptical. When the rep says END ROLEPLAY, score them 1-10.\n\nDOCTRINE:\n{$doctrine}\n\nTRAINING:\n{$ctx}";
        return "You are Griff, the AI Sales Coach for Your Energy Best. Named after Griffin Hill methodology.\n\nDOCTRINE:\n{$doctrine}\n\nRULES:\n- Answer using ONLY training content and doctrine\n- Give exact words to say, not concepts\n- Coaching tone: direct and practical\n\nTRAINING:\n{$ctx}";
    }

    private function callClaude($system, $messages) {
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, array(
            CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'x-api-key: ' . $this->apiKey, 'anthropic-version: 2023-06-01'),
            CURLOPT_POSTFIELDS => json_encode(array('model' => $this->model, 'max_tokens' => 1024, 'system' => $system, 'messages' => $messages)),
        ));
        $resp = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if ($code !== 200) { $e = json_decode($resp, true); throw new Exception(isset($e['error']['message']) ? $e['error']['message'] : 'HTTP ' . $code); }
        $data = json_decode($resp, true); $text = '';
        if (isset($data['content'])) { foreach ($data['content'] as $b) { if (isset($b['text'])) $text .= $b['text']; } }
        return array('text' => $text, 'input_tokens' => isset($data['usage']['input_tokens']) ? $data['usage']['input_tokens'] : 0, 'output_tokens' => isset($data['usage']['output_tokens']) ? $data['usage']['output_tokens'] : 0);
    }

    public function indexAllContent() {
        try { $this->db->prepare("DELETE FROM knowledge_base WHERE source_type='segment'")->execute(); } catch (Exception $e) { return 0; }
        try {
            $s = $this->db->prepare("SELECT s.id, s.title, s.content_html, s.customer_quote, s.rep_response, s.tip, m.title AS mt, f.title AS ft FROM segments s JOIN modules m ON s.module_id=m.id JOIN folders f ON m.folder_id=f.id WHERE s.is_active=1 AND m.is_active=1");
            $s->execute(); $segs = $s->fetchAll();
        } catch (Exception $e) { return 0; }
        $count = 0;
        foreach ($segs as $seg) {
            $t = $seg['ft'] . ' > ' . $seg['mt'] . ' > ' . $seg['title'];
            $txt = trim(strip_tags($seg['content_html']));
            if (strlen($txt) > 10) { $this->ins('segment', $seg['id'], $t, $txt); $count++; }
        }
        return $count;
    }

    public function indexSegment($segId) {
        try { $this->db->prepare("DELETE FROM knowledge_base WHERE source_type='segment' AND source_id=?")->execute(array($segId)); } catch (Exception $e) {}
        try {
            $s = $this->db->prepare("SELECT s.*, m.title AS mt, f.title AS ft FROM segments s JOIN modules m ON s.module_id=m.id JOIN folders f ON m.folder_id=f.id WHERE s.id=?");
            $s->execute(array($segId)); $seg = $s->fetch();
        } catch (Exception $e) { return; }
        if (!$seg) return;
        $txt = trim(strip_tags($seg['content_html']));
        if (strlen($txt) > 10) $this->ins('segment', $segId, $seg['ft'] . ' > ' . $seg['mt'] . ' > ' . $seg['title'], $txt);
    }

    private function ins($type, $id, $title, $text) {
        try { $this->db->prepare("INSERT INTO knowledge_base (source_type, source_id, source_title, chunk_text) VALUES (?,?,?,?)")->execute(array($type, $id, $title, $text)); } catch (Exception $e) {}
    }

    public function getConversations($uid, $lim = 20) {
        try { $s = $this->db->prepare("SELECT id, mode, created_at, updated_at, token_count FROM ai_conversations WHERE user_id=? ORDER BY updated_at DESC LIMIT ?"); $s->execute(array($uid, $lim)); return $s->fetchAll(); } catch (Exception $e) { return array(); }
    }

    public function getConversation($cid, $uid) {
        try { $s = $this->db->prepare("SELECT * FROM ai_conversations WHERE id=? AND user_id=?"); $s->execute(array($cid, $uid)); return $s->fetch(); } catch (Exception $e) { return null; }
    }
}
