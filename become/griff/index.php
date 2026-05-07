<?php
// become/griff/index.php — Griff AI Sales Coach
error_reporting(E_ALL); ini_set('display_errors', 0);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$name = htmlspecialchars($current_user['first_name'] ? $current_user['first_name'] : $current_user['username']);
$isAdmin = (isset($_SESSION['portal_role']) && $_SESSION['portal_role'] === 'admin');
$isLeader = (isset($_SESSION['portal_role']) && ($_SESSION['portal_role'] === 'leader' || $_SESSION['portal_role'] === 'admin'));
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>Griff</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0b0b12;--card:rgba(255,255,255,.04);--bdr:rgba(255,255,255,.08);--txt:#e8e8ef;--dim:#6b7280;--teal:#22A8B3;--gold:#FFB703;--purple:#a78bfa}
body{font-family:-apple-system,system-ui,sans-serif;background:var(--bg);color:var(--txt);height:100dvh;display:flex;flex-direction:column;overflow:hidden}

.hdr{display:flex;align-items:center;gap:.4rem;padding:.5rem .75rem;border-bottom:1px solid var(--bdr);background:rgba(255,255,255,.015);flex-shrink:0;min-height:44px}
.hdr-title{font-size:.95rem;font-weight:700;color:var(--purple);flex:1}
.hdr a{color:var(--dim);text-decoration:none;font-size:.72rem;padding:.25rem .4rem;border:1px solid var(--bdr);border-radius:6px}
.hdr a:active{background:var(--card)}

.tabs{display:flex;gap:.25rem;padding:.35rem .5rem;flex-shrink:0}
.tab{flex:1;padding:.45rem;border-radius:8px;border:1px solid var(--bdr);background:var(--card);color:var(--dim);font-size:.78rem;font-weight:600;cursor:pointer;text-align:center}
.tab.on{background:rgba(34,168,179,.12);border-color:var(--teal);color:var(--teal)}

.banner{background:rgba(139,92,246,.08);border:1px solid rgba(139,92,246,.2);border-radius:8px;padding:.5rem .75rem;margin:.35rem .5rem;font-size:.72rem;color:var(--purple);text-align:center;display:none;flex-shrink:0}
.banner a{color:var(--gold)}

.msgs{flex:1;overflow-y:auto;padding:.75rem;display:flex;flex-direction:column;gap:.5rem;-webkit-overflow-scrolling:touch}

.msg{max-width:88%;padding:.6rem .8rem;border-radius:12px;font-size:.85rem;line-height:1.55;animation:fadeIn .25s ease;word-wrap:break-word}
@keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
.msg-u{align-self:flex-end;background:var(--teal);color:#fff;border-bottom-right-radius:4px}
.msg-a{align-self:flex-start;background:var(--card);border:1px solid var(--bdr);border-bottom-left-radius:4px}
.msg-s{align-self:center;color:var(--dim);font-size:.75rem;text-align:center;background:rgba(239,71,111,.08);border:1px solid rgba(239,71,111,.15);border-radius:8px;padding:.4rem .6rem}

.dots{align-self:flex-start;padding:.4rem .8rem;display:none;gap:.25rem}
.dots.on{display:flex}
.dots span{width:5px;height:5px;border-radius:50%;background:var(--dim);animation:dot 1.2s infinite}
.dots span:nth-child(2){animation-delay:.15s}
.dots span:nth-child(3){animation-delay:.3s}
@keyframes dot{0%,80%{opacity:.3;transform:scale(.8)}40%{opacity:1;transform:scale(1.1)}}

.welcome{text-align:center;padding:2rem 1rem;display:flex;flex-direction:column;align-items:center;justify-content:center;flex:1}
.welcome h2{font-size:1.15rem;margin-bottom:.35rem}
.welcome p{color:var(--dim);font-size:.82rem;max-width:360px;margin-bottom:1.25rem;line-height:1.5}
.qbtns{display:flex;flex-wrap:wrap;gap:.3rem;justify-content:center;max-width:420px}
.qb{background:var(--card);border:1px solid var(--bdr);border-radius:8px;padding:.45rem .7rem;font-size:.78rem;color:var(--txt);cursor:pointer;font-family:inherit;transition:border-color .12s}
.qb:active{border-color:var(--teal);background:rgba(34,168,179,.08)}

.inp{padding:.5rem .6rem;border-top:1px solid var(--bdr);background:rgba(255,255,255,.02);flex-shrink:0;display:flex;gap:.4rem;align-items:flex-end}
.inp textarea{flex:1;background:rgba(255,255,255,.05);border:1px solid var(--bdr);border-radius:10px;padding:.5rem .7rem;color:var(--txt);font-family:inherit;font-size:.88rem;resize:none;outline:none;min-height:40px;max-height:100px;line-height:1.4}
.inp textarea:focus{border-color:var(--teal)}
.inp textarea::placeholder{color:var(--dim)}
.sbtn{width:40px;height:40px;border-radius:50%;background:var(--teal);color:#fff;border:none;font-size:1rem;cursor:pointer;flex-shrink:0;display:flex;align-items:center;justify-content:center}
.sbtn:disabled{opacity:.3}
</style>
</head>
<body>

<div class="hdr">
    <div class="hdr-title">🦅 Griff</div>
    <a href="/become/griff/doctrine.php">📜</a>
    <?php if($isLeader):?><a href="/become/griff/analytics.php">📊</a><?php endif;?>
    <?php if($isAdmin):?><a href="#" id="idxBtn">🔄</a><?php endif;?>
    <a href="/become/">← Portal</a>
</div>

<div class="tabs">
    <div class="tab on" id="tabCoach">🎓 Ask Griff</div>
    <div class="tab" id="tabRole">🎭 Roleplay</div>
</div>

<div class="banner" id="banner"></div>

<div class="msgs" id="msgs">
    <div class="welcome" id="welcome">
        <div style="font-size:2.5rem;margin-bottom:.5rem">🦅</div>
        <h2>Hey <?=$name?></h2>
        <p id="wtxt">Ask me anything about sales technique, objection handling, or the training manual.</p>
        <div class="qbtns" id="qbtns"></div>
    </div>
    <div class="dots" id="dots"><span></span><span></span><span></span></div>
</div>

<div class="inp">
    <textarea id="ti" placeholder="Ask Griff..." rows="1"></textarea>
    <button class="sbtn" id="sb">&#10148;</button>
</div>

<script>
var MODE='coach', CID=null, BUSY=false;
var API='/become/griff/api.php';

var coachQs = [
    {t:'🚪 Not interested', q:'How do I handle when someone says not interested at the door'},
    {t:'📋 Needs audit', q:'What is the Griffin Hill needs audit process'},
    {t:'🔄 Case open', q:'How do I transition from case open to the pitch'},
    {t:'💰 Too expensive', q:'What do I say when they say it is too expensive'},
    {t:'🎯 Opening line', q:'Give me a strong opening line for door knocking'}
];
var roleQs = [
    {t:'🚪 Cold knock', q:'Hi there, my name is '+<?=json_encode($name)?>.replace(/"/g,'')+' with Your Energy Best. How are you doing today?'},
    {t:'☀️ Solar opener', q:'Hi! I noticed you do not have solar panels yet. Do you have a couple minutes?'},
    {t:'🔋 Energy audit', q:'Hey! We are doing free energy audits in the neighborhood today.'}
];

function renderQs() {
    var qs = MODE==='roleplay' ? roleQs : coachQs;
    var h='';
    for(var i=0;i<qs.length;i++) h+='<button class="qb" data-qi="'+i+'">'+qs[i].t+'</button>';
    document.getElementById('qbtns').innerHTML=h;
    document.getElementById('wtxt').textContent = MODE==='roleplay'
        ? 'Practice your pitch! I will act as a homeowner.'
        : 'Ask me anything about sales technique, objection handling, or the training manual.';
}
renderQs();

// Tab switching
document.getElementById('tabCoach').onclick=function(){MODE='coach';CID=null;this.className='tab on';document.getElementById('tabRole').className='tab';clearMsgs();renderQs()};
document.getElementById('tabRole').onclick=function(){MODE='roleplay';CID=null;this.className='tab on';document.getElementById('tabCoach').className='tab';clearMsgs();renderQs()};

function clearMsgs(){
    var c=document.getElementById('msgs');
    var els=c.querySelectorAll('.msg');
    for(var i=0;i<els.length;i++) els[i].remove();
    document.getElementById('welcome').style.display='flex';
}

// Quick button clicks
document.getElementById('qbtns').addEventListener('click',function(e){
    var b=e.target.closest('.qb');
    if(!b) return;
    var idx=parseInt(b.getAttribute('data-qi'));
    var qs=MODE==='roleplay'?roleQs:coachQs;
    if(qs[idx]) send(qs[idx].q);
});

// Send button
document.getElementById('sb').onclick=function(){
    var v=document.getElementById('ti').value.trim();
    if(v) send(v);
};

// Enter key
document.getElementById('ti').onkeydown=function(e){
    if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();var v=this.value.trim();if(v)send(v);}
};

// Auto-resize
document.getElementById('ti').oninput=function(){this.style.height='auto';this.style.height=Math.min(this.scrollHeight,100)+'px';};

function send(text){
    if(BUSY) return;
    document.getElementById('welcome').style.display='none';
    addMsg(text,'msg-u');
    document.getElementById('ti').value='';
    document.getElementById('ti').style.height='auto';
    BUSY=true;
    document.getElementById('sb').disabled=true;
    document.getElementById('dots').className='dots on';
    scroll();

    var body=JSON.stringify({message:text,conversation_id:CID,mode:MODE});
    fetch(API+'?action=chat',{method:'POST',headers:{'Content-Type':'application/json'},body:body})
    .then(function(r){return r.json()})
    .then(function(d){
        document.getElementById('dots').className='dots';
        if(d.error){addMsg('Error: '+d.error,'msg-s');}
        else{CID=d.conversation_id;addMsg(d.response,'msg-a');}
        BUSY=false;document.getElementById('sb').disabled=false;scroll();
    })
    .catch(function(err){
        document.getElementById('dots').className='dots';
        addMsg('Connection error: '+err.message,'msg-s');
        BUSY=false;document.getElementById('sb').disabled=false;
    });
}

function addMsg(text,cls){
    var d=document.createElement('div');
    d.className='msg '+cls;
    if(cls==='msg-a'){
        text=text.replace(/\*\*(.+?)\*\*/g,'<b>$1</b>');
        text=text.replace(/\n/g,'<br>');
        d.innerHTML=text;
    } else {
        d.textContent=text;
    }
    var c=document.getElementById('msgs');
    c.insertBefore(d,document.getElementById('dots'));
    scroll();
}

function scroll(){var c=document.getElementById('msgs');setTimeout(function(){c.scrollTop=c.scrollHeight;},60);}

// Check status
fetch(API+'?action=status').then(function(r){return r.json()}).then(function(d){
    if(!d.configured){var b=document.getElementById('banner');b.style.display='block';b.innerHTML='🦅 <b>Griff Demo Mode</b> — Using your training content. <a href="https://console.anthropic.com" target="_blank">Get API key</a> for full AI.';}
}).catch(function(){});

// Index button
var ib=document.getElementById('idxBtn');
if(ib) ib.onclick=function(e){
    e.preventDefault();
    if(!confirm('Index all training content for Griff?')) return;
    this.textContent='...';
    var self=this;
    fetch(API+'?action=index').then(function(r){return r.json()}).then(function(d){
        self.textContent='🔄';
        alert('Indexed '+d.chunks_indexed+' chunks!');
    }).catch(function(err){self.textContent='🔄';alert('Error: '+err.message);});
};
</script>
</body>
</html>
