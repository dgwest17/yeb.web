<?php
class AICoach {
    private $db;
    private $apiKey;
    private $model = 'claude-sonnet-4-6';
    private $maxTokens = 1500;

    public function __construct() {
        $this->db = Database::getInstance();
        $cfgPath = __DIR__ . '/../../config.php';
        $this->apiKey = '';
        if (file_exists($cfgPath)) {
            $cfg = require $cfgPath;
            if (isset($cfg['anthropic_api_key'])) $this->apiKey = $cfg['anthropic_api_key'];
            if (!empty($cfg['anthropic_model'])) $this->model = $cfg['anthropic_model'];
            if (isset($cfg['griff_max_tokens'])) $this->maxTokens = intval($cfg['griff_max_tokens']);
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
            $sys = $this->buildSystemPrompt($mode, $message, $userId);
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

    private function getRepContext($userId) {
        try {
            $s = $this->db->prepare("SELECT u.first_name, COALESCE(p.level,0) AS level FROM training_users u LEFT JOIN user_progress p ON p.user_id=u.id WHERE u.id=?");
            $s->execute(array($userId));
            $r = $s->fetch();
            if ($r) {
                $nm = (isset($r['first_name']) && trim($r['first_name']) !== '') ? trim($r['first_name']) : 'this rep';
                return $nm . ', currently Level ' . intval($r['level']);
            }
        } catch (Exception $e) {}
        return 'a Your Energy Best rep';
    }

    private function buildSystemPrompt($mode, $msg, $userId = null) {
        $doctrine = '';
        try {
            $s = $this->db->prepare("SELECT rule_title, rule_text FROM doctrine_rules WHERE is_active=1 ORDER BY priority DESC");
            $s->execute();
            foreach ($s->fetchAll() as $r) { $doctrine .= "- " . $r['rule_title'] . ": " . $r['rule_text'] . "\n"; }
        } catch (Exception $e) {}
        if (trim($doctrine) === '') $doctrine = "(No doctrine set yet. Coach from the training material below and solid solar-sales best practice.)";

        $ctx = $this->retrieveContext($msg);
        if (strpos($ctx, 'No specific training') !== false) {
            $ctx = "(No closely matching training was found for this. Coach from the doctrine and best practice, and let the rep know this is not yet covered in their manual.)";
        }

        $rep = $userId ? $this->getRepContext($userId) : 'a Your Energy Best rep';

        if ($mode === 'roleplay') {
            return "You are running a live sales ROLEPLAY to train a Your Energy Best residential-solar rep ({$rep}).\n\n"
                . "YOUR ROLE: Play a realistic homeowner answering the door or sitting at the kitchen table. At the start, silently pick a believable persona (age, household, mood, and a real reason to be skeptical) and STAY in that character for the whole conversation. React like a real person: a little guarded, maybe busy, not a pushover, but winnable if the rep earns it. Use natural, conversational language. Raise realistic objections such as not having time, price, needing to talk to a spouse, distrust of door knockers, already having quotes, or worries about the roof. Do not fold instantly, and do not be impossible. Reward genuinely good technique by warming up.\n\n"
                . "RULES:\n- Stay fully in character. Do not coach, explain, or break character during the roleplay.\n- One homeowner turn at a time, usually one to four sentences.\n- If the rep talks past an objection, gets pushy, makes something up, or gives up, respond the way a real homeowner actually would.\n\n"
                . "ENDING: When the rep types END ROLEPLAY or FEEDBACK, drop character and give a COACH SCORECARD:\n  Opening and rapport: X/10\n  Discovery (did they ask, listen, and find a real need?): X/10\n  Objection handling: X/10\n  Advancing the sale / close: X/10\n  OVERALL: X/10\nThen a few short bullets: what worked (quote the rep's actual words back to them), what to fix (give the stronger line as a quote), and the single highest-leverage thing to practice next. Judge against the doctrine and training below.\n\n"
                . "DOCTRINE:\n{$doctrine}\n\nTRAINING MATERIAL:\n{$ctx}";
        }

        if ($mode === 'pitch') {
            return "You are Griff, judging a Your Energy Best rep's solar sales pitch ({$rep}). The rep will paste or describe a pitch, opener, rebuttal, or close. Score it and sharpen it.\n\n"
                . "DELIVER, in this order:\n1. SCORECARD, X/10 each: Hook and relevance, Clarity, Discovery and needs, Objection pre-handling, Call to action, and an OVERALL score.\n2. LINE EDITS: quote the rep's weak lines and rewrite each one stronger, as a quoted line they can say.\n3. TIGHTER VERSION: rewrite the whole thing the way a top closer would deliver it, as quoted lines ready to say out loud.\n4. ONE THING: the single highest-impact change to make next.\n\n"
                . "Be specific and practical, and use the doctrine and training wording wherever it applies. Do not invent prices, financing terms, or guarantees, flag those as confirm with your manager.\n\n"
                . "DOCTRINE:\n{$doctrine}\n\nTRAINING MATERIAL:\n{$ctx}";
        }

        // Default: coach
        return "You are Griff, the AI sales coach for Your Energy Best, a residential solar company built on the Griffin Hill sales methodology. Your job is to make this rep dramatically better at door-to-door and in-home solar selling, fast.\n\n"
            . "WHO YOU ARE COACHING: {$rep}.\n\n"
            . "HOW YOU COACH:\n- Be direct, sharp, and practical, like a top closer who has run thousands of doors. Warm, but no fluff.\n- Hand the rep the EXACT words to say, written as a quoted line they can copy. Do not describe a concept when you can give them the line.\n- Keep it tight: lead with the answer, then a few supporting beats. Short paragraphs, not walls of text.\n- If the question is vague, ask one sharp clarifying question before launching in.\n- Anchor every answer in the doctrine and training below. When the training covers it, use ITS framework and wording. When it does not, coach from solid solar-sales best practice and say so in a line.\n- Never invent product specs, prices, financing terms, or guarantees. If only the company can answer, tell the rep to confirm with their manager.\n- Close with one concrete next action or a line to practice when it helps.\n\n"
            . "NON-NEGOTIABLE DOCTRINE (follow exactly):\n{$doctrine}\n\n"
            . "RELEVANT TRAINING MATERIAL (the rep's own manual, prefer this language):\n{$ctx}";
    }

    private function callClaude($system, $messages) {
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, array(
            CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'x-api-key: ' . $this->apiKey, 'anthropic-version: 2023-06-01'),
            CURLOPT_POSTFIELDS => json_encode(array('model' => $this->model, 'max_tokens' => $this->maxTokens, 'system' => $system, 'messages' => $messages)),
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
