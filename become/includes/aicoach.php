<?php
/**
 * AICoach.php — Core AI Coach Engine
 * Location: public_html/become/includes/AICoach.php
 */
class AICoach {
    private $db;
    private $apiKey;
    private $model = 'claude-sonnet-4-6-20250514';
    private $maxTokens = 1024;

    public function __construct() {
        $this->db = Database::getInstance();
        $config = require __DIR__ . '/../../config.php';
        $this->apiKey = $config['anthropic_api_key'] ?? '';
    }

    public function isConfigured() {
        return !empty($this->apiKey);
    }

    /**
     * Send a message and get AI response
     */
    public function chat($userId, $message, $conversationId = null, $mode = 'coach') {
        if (!$this->apiKey) throw new Exception('API key not configured. Add it in config.php');
        if (!$this->checkRateLimit($userId)) throw new Exception('Rate limit: max 30 messages/hour');

        // Load or create conversation
        $history = [];
        if ($conversationId) {
            $s = $this->db->prepare("SELECT messages, mode FROM ai_conversations WHERE id=? AND user_id=?");
            $s->execute([$conversationId, $userId]);
            $row = $s->fetch();
            if ($row) {
                $history = json_decode($row['messages'], true) ?: [];
                $mode = $row['mode'];
            }
        }

        // Build system prompt with relevant content
        $systemPrompt = $this->buildSystemPrompt($mode, $message);

        // Add user message
        $history[] = ['role' => 'user', 'content' => $message];

        // Keep last 20 messages
        if (count($history) > 20) $history = array_slice($history, -20);

        // Call Claude
        $response = $this->callClaude($systemPrompt, $history);

        // Save
        $history[] = ['role' => 'assistant', 'content' => $response['text']];
        $tokens = ($response['input_tokens'] ?? 0) + ($response['output_tokens'] ?? 0);

        if ($conversationId) {
            $s = $this->db->prepare("UPDATE ai_conversations SET messages=?, token_count=token_count+?, updated_at=NOW() WHERE id=?");
            $s->execute([json_encode($history), $tokens, $conversationId]);
        } else {
            $s = $this->db->prepare("INSERT INTO ai_conversations (user_id, mode, messages, token_count) VALUES (?, ?, ?, ?)");
            $s->execute([$userId, $mode, json_encode($history), $tokens]);
            $conversationId = $this->db->lastInsertId();
        }

        return [
            'response'        => $response['text'],
            'conversation_id' => (int)$conversationId,
            'tokens_used'     => $tokens,
        ];
    }

    private function buildSystemPrompt($mode, $userMessage) {
        $s = $this->db->prepare("SELECT rule_title, rule_text FROM doctrine_rules WHERE is_active=1 ORDER BY priority DESC");
        $s->execute();
        $rules = $s->fetchAll();
        $doctrine = "";
        foreach ($rules as $r) $doctrine .= "- {$r['rule_title']}: {$r['rule_text']}\n";

        $context = $this->retrieveContext($userMessage);

        if ($mode === 'roleplay') return $this->roleplayPrompt($doctrine, $context);
        return $this->coachPrompt($doctrine, $context);
    }

    private function coachPrompt($doctrine, $context) {
        return "You are Griffin — the AI Sales Coach for Your Energy Best solar and HVAC sales team. You are named after the Griffin Hill sales methodology that forms your core doctrine.

DOCTRINE (always follow):
{$doctrine}

RULES:
- Answer using ONLY the training content below and doctrine above
- If not covered in training, say \"That's not in our current training — check with your leader\"
- Be specific: give exact words to say, not just concepts
- Coaching tone: direct, encouraging, practical
- Reference specific techniques from the training
- Never contradict the doctrine
- Keep responses concise and actionable

RELEVANT TRAINING CONTENT:
{$context}";
    }

    private function roleplayPrompt($doctrine, $context) {
        return "You are a HOMEOWNER in a door-to-door solar/HVAC sales roleplay.

YOUR CHARACTER:
- Realistic homeowner approached at their door
- Skeptical but not hostile
- Use natural objections: \"not interested\", \"need to talk to spouse\", \"too expensive\", \"I'm busy\"
- Respond well when the rep uses good technique, push back when they don't
- Stay in character completely

WHAT GOOD SELLING LOOKS LIKE (don't share this):
{$doctrine}

TRAINING (techniques the rep should use):
{$context}

When rep says \"END ROLEPLAY\" or asks for feedback:
1. Break character
2. Score 1-10
3. What they did well (quote their words)
4. What to improve (reference specific training techniques)
5. Exact words they should have said at key moments";
    }

    private function retrieveContext($query, $limit = 8) {
        try {
            $s = $this->db->prepare("SELECT source_title, chunk_text, source_type,
                MATCH(chunk_text, source_title) AGAINST(? IN NATURAL LANGUAGE MODE) AS relevance
                FROM knowledge_base
                WHERE MATCH(chunk_text, source_title) AGAINST(? IN NATURAL LANGUAGE MODE)
                ORDER BY relevance DESC LIMIT ?");
            $s->execute([$query, $query, $limit]);
            $results = $s->fetchAll();
        } catch (Exception $e) {
            $results = [];
        }

        // Fallback: keyword search
        if (empty($results)) {
            $words = array_filter(explode(' ', $query), fn($w) => strlen($w) > 3);
            if ($words) {
                $conds = array_map(fn() => "chunk_text LIKE ?", array_slice($words, 0, 3));
                $params = array_map(fn($w) => "%{$w}%", array_slice($words, 0, 3));
                $params[] = $limit;
                try {
                    $s = $this->db->prepare("SELECT source_title, chunk_text, source_type FROM knowledge_base WHERE " . implode(' OR ', $conds) . " LIMIT ?");
                    $s->execute($params);
                    $results = $s->fetchAll();
                } catch (Exception $e) { $results = []; }
            }
        }

        if (empty($results)) return "(No specific training content found for this topic)";

        $out = "";
        foreach ($results as $r) {
            $out .= "--- [{$r['source_type']}] {$r['source_title']} ---\n{$r['chunk_text']}\n\n";
        }
        return $out;
    }

    private function callClaude($system, $messages) {
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $this->model,
                'max_tokens' => $this->maxTokens,
                'system' => $system,
                'messages' => $messages,
            ]),
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200) {
            $err = json_decode($resp, true);
            throw new Exception('AI error: ' . ($err['error']['message'] ?? "HTTP {$code}"));
        }
        $data = json_decode($resp, true);
        $text = '';
        foreach ($data['content'] ?? [] as $b) { if ($b['type'] === 'text') $text .= $b['text']; }
        return ['text' => $text, 'input_tokens' => $data['usage']['input_tokens'] ?? 0, 'output_tokens' => $data['usage']['output_tokens'] ?? 0];
    }

    // ─── Content Indexing ───

    public function indexAllContent() {
        $this->db->prepare("DELETE FROM knowledge_base WHERE source_type='segment'")->execute();
        $s = $this->db->prepare("SELECT s.id, s.title, s.content_html, s.customer_quote, s.rep_response, s.tip,
            m.title AS mod_title, f.title AS folder_title
            FROM segments s JOIN modules m ON s.module_id=m.id JOIN folders f ON m.folder_id=f.id
            WHERE s.is_active=1 AND m.is_active=1");
        $s->execute();
        $count = 0;
        foreach ($s->fetchAll() as $seg) {
            $title = "{$seg['folder_title']} → {$seg['mod_title']} → {$seg['title']}";
            $text = strip_tags($seg['content_html'] ?? '');
            if (strlen(trim($text)) > 10) { $this->indexChunk('segment', $seg['id'], $title, $text); $count++; }
            $convo = '';
            if ($seg['customer_quote']) $convo .= "Customer: {$seg['customer_quote']}\n";
            if ($seg['rep_response']) $convo .= "Rep response: {$seg['rep_response']}\n";
            if ($seg['tip']) $convo .= "Tip: {$seg['tip']}\n";
            if (strlen(trim($convo)) > 10) { $this->indexChunk('segment', $seg['id'], $title.' (dialogue)', $convo); $count++; }
        }
        return $count;
    }

    public function indexSegment($segId) {
        $this->db->prepare("DELETE FROM knowledge_base WHERE source_type='segment' AND source_id=?")->execute([$segId]);
        $s = $this->db->prepare("SELECT s.*, m.title AS mod_title, f.title AS folder_title
            FROM segments s JOIN modules m ON s.module_id=m.id JOIN folders f ON m.folder_id=f.id WHERE s.id=?");
        $s->execute([$segId]);
        $seg = $s->fetch();
        if (!$seg) return;
        $title = "{$seg['folder_title']} → {$seg['mod_title']} → {$seg['title']}";
        $text = strip_tags($seg['content_html'] ?? '');
        if (strlen(trim($text)) > 10) $this->indexChunk('segment', $segId, $title, $text);
    }

    private function indexChunk($type, $id, $title, $text) {
        $text = trim($text);
        if (strlen($text) <= 1200) {
            $this->db->prepare("INSERT INTO knowledge_base (source_type,source_id,source_title,chunk_text) VALUES (?,?,?,?)")
                ->execute([$type, $id, $title, $text]);
        } else {
            $parts = preg_split('/\n{2,}/', $text);
            $chunk = ''; $ci = 0;
            foreach ($parts as $p) {
                if (strlen($chunk) + strlen($p) > 1000 && $chunk) {
                    $this->db->prepare("INSERT INTO knowledge_base (source_type,source_id,source_title,chunk_text,chunk_order) VALUES (?,?,?,?,?)")
                        ->execute([$type, $id, $title, trim($chunk), $ci++]);
                    $chunk = '';
                }
                $chunk .= $p . "\n\n";
            }
            if (trim($chunk)) {
                $this->db->prepare("INSERT INTO knowledge_base (source_type,source_id,source_title,chunk_text,chunk_order) VALUES (?,?,?,?,?)")
                    ->execute([$type, $id, $title, trim($chunk), $ci]);
            }
        }
    }

    public function getConversations($userId, $limit = 20) {
        $s = $this->db->prepare("SELECT id, mode, created_at, updated_at, token_count FROM ai_conversations WHERE user_id=? ORDER BY updated_at DESC LIMIT ?");
        $s->execute([$userId, $limit]);
        return $s->fetchAll();
    }

    public function getConversation($convId, $userId) {
        $s = $this->db->prepare("SELECT * FROM ai_conversations WHERE id=? AND user_id=?");
        $s->execute([$convId, $userId]);
        return $s->fetch();
    }

    public function checkRateLimit($userId) {
        $s = $this->db->prepare("SELECT SUM(JSON_LENGTH(messages)) c FROM ai_conversations WHERE user_id=? AND updated_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $s->execute([$userId]);
        return ((int)($s->fetch()['c'] ?? 0)) < 60;
    }
}
