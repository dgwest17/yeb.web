<?php
/**
 * become/team.php — Trainer console (My Team)
 * Location: public_html/become/team.php
 *
 * For trainers (and leaders/admins). Manage your own reps only.
 * Content editing lives in manage.php and is NOT available here.
 */
session_start();
require_once __DIR__ . '/includes/auth.php';   // sets $current_user, session flags
if (!has_role('trainer')) {
    header('Location: /become/');
    exit;
}
$meName = htmlspecialchars(trim(($current_user['first_name'] ?? '') . ' ' . ($current_user['last_name'] ?? '')) ?: ($current_user['email'] ?? 'Trainer'));
$isLeader = has_role('leader');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Team — Become</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#0a0a0f;--teal:#22A8B3;--orange:#FB9B47;--green:#06D6A0;--red:#EF476F;--gold:#FFB703;
--card:rgba(255,255,255,0.03);--border:rgba(34,168,179,0.15);--text:#fff;--dim:rgba(255,255,255,0.55);--mute:rgba(255,255,255,0.35);--hf:'Playfair Display',serif;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse at 30% 70%,rgba(34,168,179,0.06),transparent 60%),radial-gradient(ellipse at 70% 30%,rgba(251,155,71,0.04),transparent 60%);pointer-events:none;z-index:0;}
header{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:.75rem;}
.logo{font-family:var(--hf);font-size:1.3rem;background:linear-gradient(135deg,var(--teal),var(--orange));-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
header a{color:var(--dim);text-decoration:none;font-size:.85rem;margin-left:1rem;}
header a:hover{color:var(--teal);}
.wrap{position:relative;z-index:1;max-width:980px;margin:0 auto;padding:1.5rem;}
.sec-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;}
.sec-hdr h2{font-family:var(--hf);font-size:1.4rem;}
.card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:1.25rem;margin-bottom:1.25rem;}
.btn{border:none;border-radius:10px;font-family:inherit;font-weight:700;cursor:pointer;padding:.6rem 1.1rem;font-size:.9rem;transition:transform .15s,box-shadow .15s;color:#fff;}
.btn:hover{transform:translateY(-1px);}
.btn-teal{background:linear-gradient(135deg,var(--teal),#1a8a93);}
.btn-green{background:linear-gradient(135deg,var(--green),#04a87d);}
.btn-ghost{background:rgba(255,255,255,.06);color:var(--dim);}
.btn-red{background:rgba(239,71,111,.18);color:#ff6b8a;}
.btn-sm{padding:.4rem .7rem;font-size:.8rem;}
.rep{display:flex;align-items:center;gap:.9rem;padding:.85rem .5rem;border-bottom:1px solid rgba(255,255,255,.06);flex-wrap:wrap;}
.rep:last-child{border-bottom:none;}
.avatar{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--teal),var(--orange));display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;}
.rep-name{flex:1;min-width:160px;}
.rep-name small{display:block;color:var(--mute);font-size:.78rem;}
.pill{font-size:.72rem;font-weight:700;padding:.25rem .6rem;border-radius:20px;white-space:nowrap;}
.pill-lvl{background:rgba(34,168,179,.15);color:var(--teal);}
.pill-pending{background:rgba(255,183,3,.15);color:var(--gold);}
.pill-rejected{background:rgba(239,71,111,.15);color:#ff6b8a;}
.pill-ok{background:rgba(6,214,160,.12);color:var(--green);}
.bar{height:6px;border-radius:6px;background:rgba(255,255,255,.08);width:120px;overflow:hidden;}
.bar > i{display:block;height:100%;background:linear-gradient(90deg,var(--teal),var(--green));}
.field{margin-bottom:.85rem;}
.field label{display:block;font-size:.8rem;font-weight:600;color:var(--dim);margin-bottom:.3rem;}
.input{width:100%;padding:.65rem .9rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;color:var(--text);font-family:inherit;font-size:.95rem;outline:none;}
.input:focus{border-color:var(--teal);}
.row{display:flex;gap:.75rem;flex-wrap:wrap;}
.row .field{flex:1;min-width:160px;}
.toast{position:fixed;bottom:1.5rem;left:50%;transform:translateX(-50%) translateY(100px);background:#16161f;border:1px solid var(--border);padding:.8rem 1.3rem;border-radius:10px;z-index:50;transition:transform .3s;}
.toast.show{transform:translateX(-50%) translateY(0);}
.toast.err{border-color:rgba(239,71,111,.5);color:#ff6b8a;}
.empty{text-align:center;padding:2.5rem;color:var(--mute);}
</style>
</head>
<body>
<header>
  <span class="logo">My Team</span>
  <div>
    <span style="color:var(--dim);font-size:.85rem"><?= $meName ?></span>
    <a href="/become/">← Portal</a>
    <?php if ($isLeader): ?><a href="/become/manage.php" style="color:var(--teal)">Admin</a><?php endif; ?>
    <a href="/become/logout.php">Log Out</a>
  </div>
</header>

<div class="wrap">
  <div class="sec-hdr">
    <h2>👥 Your Reps</h2>
    <button class="btn btn-teal" id="toggleAdd">+ Add Rep</button>
  </div>

  <div class="card" id="addForm" style="display:none">
    <h3 style="font-family:var(--hf);margin-bottom:1rem">New Rep</h3>
    <div class="row">
      <div class="field"><label>Email (used to log in)</label><input class="input" id="r-email" type="email" placeholder="rep@yourenergybest.com"></div>
      <div class="field"><label>Temporary Password</label><input class="input" id="r-pass" type="password"></div>
    </div>
    <div class="row">
      <div class="field"><label>First Name</label><input class="input" id="r-first"></div>
      <div class="field"><label>Last Name</label><input class="input" id="r-last"></div>
    </div>
    <div style="display:flex;gap:.5rem;margin-top:.5rem">
      <button class="btn btn-green" id="createBtn">Create Rep</button>
      <button class="btn btn-ghost" id="cancelBtn">Cancel</button>
    </div>
    <p style="color:var(--mute);font-size:.78rem;margin-top:.75rem">New reps start at Level 0 and report to you. They advance by completing each level and passing off to a leader.</p>
  </div>

  <div class="card" id="repList"><div class="empty">Loading…</div></div>
</div>

<div class="toast" id="toast"></div>

<script>
const API = '/become/api/team.php';
function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
function toast(msg,err){const t=document.getElementById('toast');t.textContent=msg;t.className='toast show'+(err?' err':'');setTimeout(()=>t.className='toast',2600);}

async function load(){
  try{
    const res = await fetch(API+'?action=list');
    const j = await res.json();
    if(j.error){document.getElementById('repList').innerHTML='<div class="empty">'+esc(j.error)+'</div>';return;}
    render(j.reps||[]);
  }catch(e){document.getElementById('repList').innerHTML='<div class="empty">Could not load your team.</div>';}
}

function statusPill(r){
  if(r.passoff_status==='pending')  return '<span class="pill pill-pending">⏳ Pass-off pending</span>';
  if(r.passoff_status==='rejected') return '<span class="pill pill-rejected">Pass-off returned</span>';
  if(r.passoff_eligible)            return '<span class="pill pill-ok">Ready to pass off</span>';
  return '';
}

function render(reps){
  const el = document.getElementById('repList');
  if(!reps.length){el.innerHTML='<div class="empty">No reps yet. Add your first rep above.</div>';return;}
  el.innerHTML = reps.map(r=>{
    const initials = ((r.first_name||'')[0]||'')+((r.last_name||'')[0]||'') || (r.email||'?')[0].toUpperCase();
    const pct = r.modules_total>0 ? Math.round((r.modules_done/r.modules_total)*100) : 0;
    return `<div class="rep">
      <div class="avatar">${esc(initials)}</div>
      <div class="rep-name">${esc(r.first_name||'')} ${esc(r.last_name||'')}<small>${esc(r.email||r.username||'')}</small></div>
      <span class="pill pill-lvl">Level ${r.level}</span>
      <div class="bar" title="${r.modules_done}/${r.modules_total} modules this level"><i style="width:${pct}%"></i></div>
      ${statusPill(r)}
      <div style="display:flex;gap:.3rem;margin-left:auto">
        <button class="btn btn-sm btn-ghost" onclick="resetPw(${r.id},'${esc(r.email||r.username)}')">🔑 Reset</button>
        <button class="btn btn-sm btn-red" onclick="deactivate(${r.id},'${esc(r.email||r.username)}')">Deactivate</button>
      </div>
    </div>`;
  }).join('');
}

document.getElementById('toggleAdd').onclick = ()=>{const f=document.getElementById('addForm');f.style.display=f.style.display==='none'?'block':'none';};
document.getElementById('cancelBtn').onclick = ()=>{document.getElementById('addForm').style.display='none';};

document.getElementById('createBtn').onclick = async ()=>{
  const d={action:'add_rep',
    email:document.getElementById('r-email').value.trim(),
    password:document.getElementById('r-pass').value,
    first_name:document.getElementById('r-first').value.trim(),
    last_name:document.getElementById('r-last').value.trim()};
  if(!d.email||!d.password) return toast('Email and password required',true);
  const res = await fetch(API,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(d)});
  const j = await res.json();
  if(j.error) return toast(j.error,true);
  ['r-email','r-pass','r-first','r-last'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('addForm').style.display='none';
  toast('Rep created'); load();
};

async function resetPw(id,label){
  const pw = prompt('New password for '+label+':');
  if(!pw) return;
  const res = await fetch(API,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'reset_password',id,password:pw})});
  const j = await res.json();
  toast(j.error||'Password reset', !!j.error);
}

async function deactivate(id,label){
  if(!confirm('Deactivate '+label+'? They will no longer be able to log in.')) return;
  const res = await fetch(API,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'set_active',id,is_active:0})});
  const j = await res.json();
  if(j.error) return toast(j.error,true);
  toast('Rep deactivated'); load();
}

load();
</script>
</body>
</html>
