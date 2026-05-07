<?php
/**
 * become/coach/index.php — Griff — AI Sales Coach
 * Location: public_html/become/griff/index.php
 */
require_once __DIR__ . '/../includes/auth.php';
$name = htmlspecialchars($current_user['first_name'] ?: $current_user['username']);
$isAdmin = ($_SESSION['portal_role'] ?? '') === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Griff — Sales Coach</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#0a0a0f;--card:rgba(255,255,255,0.04);--bdr:rgba(255,255,255,0.08);--txt:#e8e8ef;--dim:#6b7280;--teal:#22A8B3;--gold:#FFB703;--green:#06D6A0;--red:#EF476F}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--txt);height:100vh;display:flex;flex-direction:column;overflow:hidden}

/* Header */
.chat-hdr{display:flex;align-items:center;gap:.5rem;padding:.75rem 1rem;background:rgba(255,255,255,0.02);border-bottom:1px solid var(--bdr);flex-shrink:0}
.chat-hdr h1{font-size:1rem;font-weight:700;flex:1;color:#a78bfa}
.griffin-icon{flex-shrink:0;display:flex;align-items:center}
.chat-hdr a{color:var(--dim);text-decoration:none;font-size:.82rem}
.chat-hdr a:hover{color:var(--teal)}

/* Mode tabs */
.mode-tabs{display:flex;gap:.25rem;padding:.5rem 1rem;flex-shrink:0}
.mode-tab{flex:1;padding:.5rem;border-radius:8px;border:1px solid var(--bdr);background:var(--card);color:var(--dim);font-size:.8rem;font-weight:600;cursor:pointer;text-align:center;transition:all .15s}
.mode-tab.active{background:rgba(34,168,179,0.12);border-color:var(--teal);color:var(--teal)}
.mode-tab:hover{border-color:var(--teal)}

/* Messages area */
.chat-messages{flex:1;overflow-y:auto;padding:1rem;display:flex;flex-direction:column;gap:.75rem}

.msg{max-width:85%;padding:.75rem 1rem;border-radius:12px;font-size:.9rem;line-height:1.6;animation:msgIn .3s ease}
@keyframes msgIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.msg--user{align-self:flex-end;background:var(--teal);color:#fff;border-bottom-right-radius:4px}
.msg--ai{align-self:flex-start;background:var(--card);border:1px solid var(--bdr);border-bottom-left-radius:4px;position:relative;padding-left:2.2rem}
.msg--ai::before{content:'🦅';position:absolute;left:.5rem;top:.5rem;font-size:.85rem}
.msg--ai strong{color:var(--teal)}
.msg--ai em{color:var(--gold)}
.msg--ai code{background:rgba(255,255,255,0.06);padding:2px 5px;border-radius:4px;font-size:.85em}
.msg--system{align-self:center;color:var(--dim);font-size:.78rem;font-style:italic;text-align:center;padding:.5rem}

/* Typing indicator */
.typing{align-self:flex-start;display:none;padding:.5rem 1rem;gap:.3rem}
.typing.show{display:flex}
.typing span{width:6px;height:6px;border-radius:50%;background:var(--dim);animation:typeDot 1.4s infinite}
.typing span:nth-child(2){animation-delay:.2s}
.typing span:nth-child(3){animation-delay:.4s}
@keyframes typeDot{0%,80%{opacity:.3;transform:scale(.8)}40%{opacity:1;transform:scale(1.2)}}

/* Input area */
.chat-input{padding:.75rem 1rem;border-top:1px solid var(--bdr);background:rgba(255,255,255,0.02);flex-shrink:0;display:flex;gap:.5rem;align-items:flex-end}
.chat-input textarea{flex:1;background:rgba(255,255,255,0.05);border:1px solid var(--bdr);border-radius:12px;padding:.6rem .85rem;color:var(--txt);font-family:inherit;font-size:.9rem;resize:none;outline:none;min-height:42px;max-height:120px;line-height:1.4;transition:border-color .2s}
.chat-input textarea:focus{border-color:var(--teal)}
.chat-input textarea::placeholder{color:var(--dim)}
.send-btn{width:42px;height:42px;border-radius:50%;background:var(--teal);color:#fff;border:none;font-size:1.1rem;cursor:pointer;transition:all .15s;flex-shrink:0;display:flex;align-items:center;justify-content:center}
.send-btn:hover{transform:scale(1.05);box-shadow:0 4px 15px rgba(34,168,179,.3)}
.send-btn:disabled{opacity:.4;cursor:not-allowed;transform:none}

/* Welcome */
.welcome{text-align:center;padding:2rem 1.5rem;flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center}
.griffin-welcome-icon{margin-bottom:1rem;animation:griffinFloat 3s ease-in-out infinite}
@keyframes griffinFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
.welcome h2{font-size:1.3rem;margin-bottom:.5rem}
.welcome p{color:var(--dim);font-size:.9rem;max-width:400px;margin-bottom:1.5rem}
.quick-btns{display:flex;flex-wrap:wrap;gap:.4rem;justify-content:center;max-width:500px}
.quick-btn{background:var(--card);border:1px solid var(--bdr);border-radius:10px;padding:.5rem .85rem;font-size:.8rem;color:var(--txt);cursor:pointer;transition:all .15s;font-family:inherit}
.quick-btn:hover{border-color:var(--teal);background:rgba(34,168,179,.06)}

/* Conversations sidebar */
.conv-list{display:none;position:fixed;inset:0;z-index:50;background:rgba(10,10,15,.97)}
.conv-list.open{display:block}
.conv-list-inner{max-width:400px;margin:0 auto;padding:1.5rem;height:100%;overflow-y:auto}
.conv-item{display:block;padding:.6rem .85rem;border-radius:8px;border:1px solid var(--bdr);margin-bottom:.4rem;cursor:pointer;transition:all .15s;text-decoration:none;color:var(--txt)}
.conv-item:hover{border-color:var(--teal);background:rgba(34,168,179,.05)}
.conv-item-mode{font-size:.7rem;color:var(--teal);text-transform:uppercase;font-weight:600}
.conv-item-date{font-size:.72rem;color:var(--dim)}

/* Admin doctrine panel */
.doctrine-panel{display:none;position:fixed;inset:0;z-index:50;background:rgba(10,10,15,.97);overflow-y:auto;padding:1.5rem}
.doctrine-panel.open{display:block}

/* Not configured warning */
.not-configured{background:rgba(255,183,3,.08);border:1px solid rgba(255,183,3,.2);border-radius:10px;padding:1rem;margin:.75rem;text-align:center}
.not-configured a{color:var(--gold)}

@media(max-width:500px){.msg{max-width:92%}.chat-hdr h1{font-size:.9rem}}
</style>
</head>
<body>

<div class="chat-hdr">
    <div class="griffin-icon">
        <svg viewBox="0 0 40 40" width="36" height="36" xmlns="http://www.w3.org/2000/svg">
            <!-- Griff body -->
            <circle cx="20" cy="22" r="12" fill="#a78bfa" opacity=".15"/>
            <!-- Head -->
            <ellipse cx="20" cy="16" rx="8" ry="7" fill="#8b5cf6"/>
            <!-- Beak -->
            <polygon points="28,15 33,17 28,18" fill="#FFB703"/>
            <!-- Eye area / Glasses -->
            <rect x="13" y="13" width="6" height="5" rx="2" fill="none" stroke="#22A8B3" stroke-width="1.2"/>
            <rect x="21" y="13" width="6" height="5" rx="2" fill="none" stroke="#22A8B3" stroke-width="1.2"/>
            <line x1="19" y1="15" x2="21" y2="15" stroke="#22A8B3" stroke-width="1"/>
            <line x1="13" y1="15" x2="10" y2="13" stroke="#22A8B3" stroke-width="1"/>
            <line x1="27" y1="15" x2="30" y2="13" stroke="#22A8B3" stroke-width="1"/>
            <!-- Eyes behind glasses -->
            <circle cx="16" cy="15.5" r="1.2" fill="#fff"/>
            <circle cx="24" cy="15.5" r="1.2" fill="#fff"/>
            <circle cx="16.3" cy="15.5" r=".6" fill="#1a1a2e"/>
            <circle cx="24.3" cy="15.5" r=".6" fill="#1a1a2e"/>
            <!-- Ear tufts -->
            <polygon points="13,10 11,5 15,9" fill="#7c3aed"/>
            <polygon points="27,10 29,5 25,9" fill="#7c3aed"/>
            <!-- Wings -->
            <path d="M8,24 Q4,18 6,12 Q8,16 10,20 Z" fill="#a78bfa" opacity=".6"/>
            <path d="M32,24 Q36,18 34,12 Q32,16 30,20 Z" fill="#a78bfa" opacity=".6"/>
            <!-- Body -->
            <ellipse cx="20" cy="28" rx="7" ry="5" fill="#7c3aed"/>
            <!-- Feet -->
            <path d="M15,32 L13,36 L15,35 L17,36 L15,32" fill="#FFB703"/>
            <path d="M25,32 L23,36 L25,35 L27,36 L25,32" fill="#FFB703"/>
        </svg>
    </div>
    <h1>Griff</h1>
    <a href="#" id="historyBtn">📋 History</a>
    <?php if($isAdmin):?><a href="/become/griff/doctrine.php">📜 Doctrine</a>
    <a href="/become/griff/analytics.php">📊 Analytics</a>
    <a href="#" id="indexBtn" style="color:var(--green)">🔄 Index</a><?php endif;?>
    <a href="/become/" >← Portal</a>
</div>

<div class="mode-tabs">
    <div class="mode-tab active" data-mode="coach">🎓 Ask Griff</div>
    <div class="mode-tab" data-mode="roleplay">🎭 Roleplay</div>
</div>

<div id="notConfigured" class="not-configured" style="display:none">
    ⚠️ Griff not configured yet. Admin needs to add the Anthropic API key in <code>config.php</code>.
    <br><a href="https://console.anthropic.com" target="_blank">Get API key →</a>
</div>

<div class="chat-messages" id="messages">
    <div class="welcome" id="welcome">
        <div class="griffin-welcome-icon">
            <svg viewBox="0 0 80 80" width="80" height="80" xmlns="http://www.w3.org/2000/svg">
                <circle cx="40" cy="44" r="24" fill="#a78bfa" opacity=".1"/>
                <ellipse cx="40" cy="32" rx="16" ry="14" fill="#8b5cf6"/>
                <polygon points="56,30 66,34 56,36" fill="#FFB703"/>
                <rect x="26" y="26" width="12" height="10" rx="4" fill="none" stroke="#22A8B3" stroke-width="2"/>
                <rect x="42" y="26" width="12" height="10" rx="4" fill="none" stroke="#22A8B3" stroke-width="2"/>
                <line x1="38" y1="31" x2="42" y2="31" stroke="#22A8B3" stroke-width="1.5"/>
                <line x1="26" y1="31" x2="20" y2="27" stroke="#22A8B3" stroke-width="1.5"/>
                <line x1="54" y1="31" x2="60" y2="27" stroke="#22A8B3" stroke-width="1.5"/>
                <circle cx="32" cy="31" r="2.5" fill="#fff"/>
                <circle cx="48" cy="31" r="2.5" fill="#fff"/>
                <circle cx="32.5" cy="31" r="1.2" fill="#1a1a2e"/>
                <circle cx="48.5" cy="31" r="1.2" fill="#1a1a2e"/>
                <polygon points="26,20 22,10 30,18" fill="#7c3aed"/>
                <polygon points="54,20 58,10 50,18" fill="#7c3aed"/>
                <path d="M16,48 Q8,36 12,24 Q16,32 20,40 Z" fill="#a78bfa" opacity=".5"/>
                <path d="M64,48 Q72,36 68,24 Q64,32 60,40 Z" fill="#a78bfa" opacity=".5"/>
                <ellipse cx="40" cy="56" rx="14" ry="10" fill="#7c3aed"/>
                <path d="M30,64 L26,72 L30,70 L34,72 L30,64" fill="#FFB703"/>
                <path d="M50,64 L46,72 L50,70 L54,72 L50,64" fill="#FFB703"/>
            </svg>
        </div>
        <h2>Hey <?= $name ?> 👋</h2>
        <p id="welcomeText">I'm Griff, your AI sales coach. Ask me anything about sales technique, objection handling, or our training. I know everything in the manual.</p>
        <div class="quick-btns" id="quickBtns">
            <button class="quick-btn" data-q="How do I handle 'not interested' at the door?">🚪 Not interested</button>
            <button class="quick-btn" data-q="What's the Griffin Hill needs audit process?">📋 Needs audit</button>
            <button class="quick-btn" data-q="How do I transition from the case open to the pitch?">🔄 Case open → pitch</button>
            <button class="quick-btn" data-q="What should I say when they say 'it's too expensive'?">💰 Too expensive</button>
            <button class="quick-btn" data-q="Give me a strong opening line for door knocking">🎯 Opening line</button>
        </div>
    </div>
</div>

<div class="typing" id="typing"><span></span><span></span><span></span></div>

<div class="chat-input">
    <textarea id="chatInput" placeholder="Ask Griff..." rows="1"></textarea>
    <button class="send-btn" id="sendBtn" title="Send">➤</button>
</div>

<!-- History panel -->
<div class="conv-list" id="convList">
    <div class="conv-list-inner">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
            <h2 style="font-size:1.1rem">📋 Past Chats with Griff</h2>
            <button style="background:none;border:none;color:var(--dim);font-size:1.2rem;cursor:pointer" id="convClose">✕</button>
        </div>
        <button class="quick-btn" style="width:100%;margin-bottom:1rem" id="newConvBtn">+ New Conversation</button>
        <div id="convItems"></div>
    </div>
</div>

<?php if($isAdmin):?>
<!-- Doctrine panel -->
<div class="doctrine-panel" id="doctrinePanel">
    <div style="max-width:600px;margin:0 auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
            <h2 style="font-size:1.1rem">⚙️ AI Doctrine Rules</h2>
            <button style="background:none;border:none;color:var(--dim);font-size:1.2rem;cursor:pointer" id="docClose">✕</button>
        </div>
        <p style="color:var(--dim);font-size:.82rem;margin-bottom:1rem">These rules define how the AI coach behaves. Higher priority = more important.</p>
        <div id="doctrineList"></div>
        <div style="margin-top:1rem;padding:1rem;background:var(--card);border:1px solid var(--bdr);border-radius:10px">
            <h3 style="font-size:.9rem;margin-bottom:.5rem">Add New Rule</h3>
            <input id="docCategory" placeholder="Category (e.g. methodology)" style="width:100%;padding:.4rem .6rem;margin-bottom:.4rem;background:rgba(255,255,255,.05);border:1px solid var(--bdr);border-radius:6px;color:var(--txt);font-family:inherit">
            <input id="docTitle" placeholder="Rule title" style="width:100%;padding:.4rem .6rem;margin-bottom:.4rem;background:rgba(255,255,255,.05);border:1px solid var(--bdr);border-radius:6px;color:var(--txt);font-family:inherit">
            <textarea id="docText" placeholder="Rule description — be specific about what the AI should/shouldn't do" rows="3" style="width:100%;padding:.4rem .6rem;margin-bottom:.4rem;background:rgba(255,255,255,.05);border:1px solid var(--bdr);border-radius:6px;color:var(--txt);font-family:inherit;resize:vertical"></textarea>
            <button id="docAddBtn" style="background:var(--teal);color:#fff;border:none;padding:.5rem 1rem;border-radius:8px;cursor:pointer;font-weight:600">Add Rule</button>
        </div>
    </div>
</div>
<?php endif;?>

<script data-cfasync="false">
var currentMode = 'coach';
var conversationId = null;
var sending = false;

// Check status
fetch('/become/griff/api.php?action=status').then(function(r){return r.json()}).then(function(d){
    if (!d.configured) {
        var banner = document.getElementById('notConfigured');
        banner.style.display = 'block';
        banner.innerHTML = '🦅 <strong>Griff Demo Mode</strong> — Using your real training content. Add the Anthropic API key in config.php to unlock full AI coaching. <a href="https://console.anthropic.com" target="_blank">Get key →</a>';
        banner.style.background = 'rgba(139,92,246,.08)';
        banner.style.borderColor = 'rgba(139,92,246,.2)';
        banner.style.color = '#a78bfa';
    }
}).catch(function(){});

// Mode switch
document.querySelectorAll('.mode-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.mode-tab').forEach(function(t){t.classList.remove('active')});
        this.classList.add('active');
        currentMode = this.dataset.mode;
        conversationId = null;
        document.getElementById('messages').innerHTML = '';
        document.getElementById('welcome').style.display = 'flex';
        if (currentMode === 'roleplay') {
            document.getElementById('welcomeText').textContent = 'Practice your pitch! I\'ll act as a homeowner. Start knocking...';
            document.getElementById('quickBtns').innerHTML =
                '<button class="quick-btn" data-q="*knocks on door* Hi there, my name is ' + <?= json_encode($name) ?> + ', I\'m with Your Energy Best...">🚪 Start cold knock</button>' +
                '<button class="quick-btn" data-q="Hi! I noticed you don\'t have solar panels yet. Do you have a couple minutes?">☀️ Solar opener</button>' +
                '<button class="quick-btn" data-q="Hey! We\'re doing a free energy audit in the neighborhood today...">🔋 Energy audit opener</button>';
        } else {
            document.getElementById('welcomeText').textContent = 'I'm Griff, your AI sales coach. Ask me anything about sales technique, objection handling, or our training.';
            document.getElementById('quickBtns').innerHTML =
                '<button class="quick-btn" data-q="How do I handle \'not interested\' at the door?">🚪 Not interested</button>' +
                '<button class="quick-btn" data-q="What\'s the Griffin Hill needs audit process?">📋 Needs audit</button>' +
                '<button class="quick-btn" data-q="How do I transition from the case open to the pitch?">🔄 Case open → pitch</button>' +
                '<button class="quick-btn" data-q="What should I say when they say \'it\'s too expensive\'?">💰 Too expensive</button>';
        }
        showWelcome();
    });
});

function showWelcome() {
    var w = document.getElementById('welcome');
    var m = document.getElementById('messages');
    m.innerHTML = '';
    m.appendChild(w);
    w.style.display = 'flex';
}

// Quick buttons
document.addEventListener('click', function(e) {
    var qb = e.target.closest('[data-q]');
    if (qb) { document.getElementById('chatInput').value = qb.dataset.q; sendMessage(); return; }
    if (e.target.closest('#historyBtn')) { e.preventDefault(); loadHistory(); return; }
    if (e.target.closest('#convClose')) { document.getElementById('convList').classList.remove('open'); return; }
    if (e.target.closest('#newConvBtn')) { conversationId = null; document.getElementById('convList').classList.remove('open'); showWelcome(); return; }
    if (e.target.closest('#doctrineBtn')) { e.preventDefault(); loadDoctrine(); return; }
    if (e.target.closest('#docClose')) { document.getElementById('doctrinePanel').classList.remove('open'); return; }
    if (e.target.closest('#docAddBtn')) { addDoctrine(); return; }
    if (e.target.closest('#indexBtn')) { e.preventDefault(); indexContent(); return; }
    var ci = e.target.closest('.conv-item');
    if (ci) { loadConversation(parseInt(ci.dataset.id)); return; }
    var delDoc = e.target.closest('[data-del-doc]');
    if (delDoc) { deleteDoctrine(parseInt(delDoc.dataset.delDoc)); return; }
});

// Send
document.getElementById('sendBtn').addEventListener('click', sendMessage);
document.getElementById('chatInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});

// Auto-resize textarea
document.getElementById('chatInput').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

async function sendMessage() {
    var input = document.getElementById('chatInput');
    var msg = input.value.trim();
    if (!msg || sending) return;

    // Hide welcome
    document.getElementById('welcome').style.display = 'none';

    // Show user message
    addMessage(msg, 'user');
    input.value = '';
    input.style.height = 'auto';

    // Show typing
    sending = true;
    document.getElementById('sendBtn').disabled = true;
    document.getElementById('typing').classList.add('show');
    scrollBottom();

    try {
        var res = await fetch('/become/griff/api.php?action=chat', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ message: msg, conversation_id: conversationId, mode: currentMode })
        });
        var data = await res.json();
        if (data.error) throw new Error(data.error);

        conversationId = data.conversation_id;
        addMessage(data.response, 'ai');
    } catch(err) {
        addMessage('⚠️ ' + err.message, 'system');
    }

    sending = false;
    document.getElementById('sendBtn').disabled = false;
    document.getElementById('typing').classList.remove('show');
    scrollBottom();
}

function addMessage(text, type) {
    var div = document.createElement('div');
    div.className = 'msg msg--' + type;
    // Simple markdown-like formatting for AI responses
    if (type === 'ai') {
        text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');
        text = text.replace(/`(.+?)`/g, '<code>$1</code>');
        text = text.replace(/\n/g, '<br>');
        div.innerHTML = text;
    } else {
        div.textContent = text;
    }
    var container = document.getElementById('messages');
    container.appendChild(div);
    scrollBottom();
}

function scrollBottom() {
    var c = document.getElementById('messages');
    setTimeout(function() { c.scrollTop = c.scrollHeight; }, 50);
}

// History
async function loadHistory() {
    var panel = document.getElementById('convList');
    panel.classList.add('open');
    try {
        var res = await fetch('/become/griff/api.php?action=conversations');
        var convs = await res.json();
        var html = '';
        convs.forEach(function(c) {
            html += '<div class="conv-item" data-id="'+c.id+'">' +
                '<div class="conv-item-mode">'+(c.mode==='roleplay'?'🎭 Roleplay':'🎓 Coach')+'</div>' +
                '<div class="conv-item-date">'+c.updated_at+' · '+c.token_count+' tokens</div>' +
            '</div>';
        });
        document.getElementById('convItems').innerHTML = html || '<p style="color:var(--dim);text-align:center">No conversations yet</p>';
    } catch(e) {}
}

async function loadConversation(id) {
    document.getElementById('convList').classList.remove('open');
    try {
        var res = await fetch('/become/griff/api.php?action=conversation&id='+id);
        var conv = await res.json();
        if (conv.error) return;
        conversationId = conv.id;
        currentMode = conv.mode || 'coach';
        document.querySelectorAll('.mode-tab').forEach(function(t){ t.classList.remove('active'); if(t.dataset.mode===currentMode) t.classList.add('active'); });
        document.getElementById('welcome').style.display = 'none';
        document.getElementById('messages').innerHTML = '';
        (conv.messages||[]).forEach(function(m) { addMessage(m.content, m.role==='user'?'user':'ai'); });
    } catch(e) {}
}

// Doctrine management
async function loadDoctrine() {
    document.getElementById('doctrinePanel').classList.add('open');
    try {
        var res = await fetch('/become/griff/api.php?action=doctrine');
        var rules = await res.json();
        var html = '';
        rules.forEach(function(r) {
            html += '<div style="padding:.6rem;background:var(--card);border:1px solid var(--bdr);border-radius:8px;margin-bottom:.4rem">' +
                '<div style="display:flex;justify-content:space-between;align-items:center">' +
                    '<span style="font-weight:600;font-size:.85rem">'+(r.is_active?'':'❌ ')+r.rule_title+'</span>' +
                    '<span style="font-size:.65rem;color:var(--dim)">P'+r.priority+' · '+r.category+'</span>' +
                '</div>' +
                '<p style="font-size:.78rem;color:var(--dim);margin-top:.25rem">'+r.rule_text+'</p>' +
                '<button data-del-doc="'+r.id+'" style="background:none;border:none;color:var(--red);font-size:.72rem;cursor:pointer;margin-top:.25rem">Delete</button>' +
            '</div>';
        });
        document.getElementById('doctrineList').innerHTML = html;
    } catch(e) {}
}

async function addDoctrine() {
    var cat = document.getElementById('docCategory').value.trim() || 'general';
    var title = document.getElementById('docTitle').value.trim();
    var text = document.getElementById('docText').value.trim();
    if (!title || !text) return;
    await fetch('/become/griff/api.php?action=doctrine', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({sub:'add', category:cat, title:title, text:text, priority:5})
    });
    document.getElementById('docTitle').value = '';
    document.getElementById('docText').value = '';
    loadDoctrine();
}

async function deleteDoctrine(id) {
    if (!confirm('Delete this rule?')) return;
    await fetch('/become/griff/api.php?action=doctrine', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({sub:'delete', id:id})
    });
    loadDoctrine();
}

// Index content
async function indexContent() {
    if (!confirm('Index all training content into Griffin\\'s knowledge base?\\n\\nThis pulls text from every segment so Griffin can search and reference it.')) return;
    var btn = document.getElementById('indexBtn');
    var oldText = btn.textContent;
    btn.textContent = '⏳ Indexing...';
    try {
        var res = await fetch('/become/griff/api.php?action=index');
        var data = await res.json();
        if (data.error) throw new Error(data.error);
        btn.textContent = '✅ ' + data.chunks_indexed + ' chunks';
        setTimeout(function() { btn.textContent = oldText; }, 3000);
        alert('Indexed ' + data.chunks_indexed + ' content chunks into Griffin\\'s knowledge base!\\n\\nGriff can now search and reference all your training content.');
    } catch(e) { 
        btn.textContent = oldText;
        alert('Error: ' + e.message); 
    }
}
</script>
</body>
</html>
