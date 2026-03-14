<?php
/**
 * become/manage.php — Training Portal Admin Panel
 * Location: public_html/become/manage.php
 * 
 * Visual content editor for folders, modules, segments, and users.
 * Requires leader or admin role.
 */
session_start();
$role = $_SESSION['portal_role'] ?? '';
if (!in_array($role, ['leader', 'admin'])) {
    header('Location: /become/login.php');
    exit;
}
$userName = htmlspecialchars($_SESSION['portal_user'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage — Become</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">
<style>
:root{--bg:#0a0a0f;--card:rgba(255,255,255,0.03);--card-h:rgba(255,255,255,0.06);--bdr:rgba(34,168,179,0.15);--bdr-a:rgba(34,168,179,0.4);--teal:#22A8B3;--teal-d:#1a8a93;--orange:#FB9B47;--green:#06D6A0;--gold:#FFB703;--red:#EF476F;--txt:#fff;--dim:rgba(255,255,255,0.5);--mute:rgba(255,255,255,0.25);--hf:'Playfair Display',serif;--bf:'DM Sans',sans-serif;--r:12px}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:var(--bf);background:var(--bg);color:var(--txt);min-height:100vh}
body::before{content:'';position:fixed;inset:0;pointer-events:none;z-index:0;background:radial-gradient(ellipse at 20% 80%,rgba(34,168,179,0.05) 0%,transparent 60%),radial-gradient(ellipse at 80% 20%,rgba(251,155,71,0.03) 0%,transparent 60%)}

/* Header */
.mgr-hdr{position:sticky;top:0;z-index:100;background:rgba(10,10,15,0.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--bdr);padding:.75rem 1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem}
.mgr-logo{font-family:var(--hf);font-size:1.3rem;background:linear-gradient(135deg,var(--teal),var(--orange));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.mgr-tabs{display:flex;gap:.25rem}
.mgr-tab{padding:.5rem 1rem;background:none;border:1px solid transparent;border-radius:8px;color:var(--dim);font-family:var(--bf);font-size:.85rem;font-weight:600;cursor:pointer;transition:all .2s}
.mgr-tab:hover{color:var(--txt);background:var(--card)}
.mgr-tab.active{color:var(--teal);border-color:var(--bdr-a);background:rgba(34,168,179,0.08)}
.mgr-hdr-right{display:flex;align-items:center;gap:1rem}
.mgr-hdr-right a{color:var(--dim);text-decoration:none;font-size:.85rem}
.mgr-hdr-right a:hover{color:var(--teal)}

/* Layout */
.mgr{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:1.5rem}
.panel{display:none}
.panel.active{display:block}

/* Cards & Buttons */
.card{background:var(--card);border:1px solid var(--bdr);border-radius:var(--r);padding:1.25rem;margin-bottom:.75rem}
.btn{padding:.5rem 1.2rem;border:none;border-radius:8px;font-family:var(--bf);font-weight:700;font-size:.85rem;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:.4rem}
.btn-teal{background:linear-gradient(135deg,var(--teal),var(--teal-d));color:#fff}
.btn-teal:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(34,168,179,0.3)}
.btn-gold{background:linear-gradient(135deg,var(--gold),var(--orange));color:#0a0a0f}
.btn-green{background:var(--green);color:#0a0a0f}
.btn-red{background:var(--red);color:#fff;font-size:.8rem;padding:.35rem .75rem}
.btn-ghost{background:none;border:1px solid var(--bdr);color:var(--dim)}
.btn-ghost:hover{border-color:var(--teal);color:var(--teal)}
.btn-sm{padding:.3rem .7rem;font-size:.78rem}

/* Section Headers */
.sec-hdr{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid rgba(255,255,255,0.05)}
.sec-hdr h2{font-family:var(--hf);font-size:1.4rem}

/* Tree */
.tree-folder{margin-bottom:.75rem}
.tree-folder-hdr{display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;background:var(--card);border:1px solid var(--bdr);border-radius:var(--r);cursor:pointer;transition:border-color .2s}
.tree-folder-hdr:hover{border-color:var(--bdr-a)}
.tree-folder-hdr .icon{font-size:1.2rem}
.tree-folder-hdr .title{flex:1;font-weight:700;font-size:.95rem}
.tree-folder-hdr .meta{color:var(--dim);font-size:.8rem}
.tree-folder-hdr .arrow{color:var(--mute);transition:transform .2s;font-size:.8rem}
.tree-folder.open > .tree-folder-hdr .arrow{transform:rotate(90deg)}
.tree-folder-body{display:none;padding:.5rem 0 .5rem 1.25rem;border-left:2px solid rgba(34,168,179,0.1);margin-left:1rem}
.tree-folder.open > .tree-folder-body{display:block}

.tree-mod{display:flex;align-items:center;gap:.75rem;padding:.6rem .75rem;border-radius:8px;cursor:pointer;transition:background .2s;margin-bottom:.25rem}
.tree-mod:hover{background:var(--card-h)}
.tree-mod.selected{background:rgba(34,168,179,0.1);border:1px solid var(--bdr-a)}
.tree-mod .icon{font-size:1rem}
.tree-mod .title{flex:1;font-weight:600;font-size:.88rem}
.tree-mod .count{color:var(--dim);font-size:.75rem}

/* Editor Panel */
.editor{background:var(--card);border:1px solid var(--bdr);border-radius:var(--r);padding:1.5rem;min-height:400px}
.editor-empty{text-align:center;padding:4rem 2rem;color:var(--mute)}
.editor-empty .icon{font-size:3rem;margin-bottom:1rem}
.ed-field{margin-bottom:1rem}
.ed-field label{display:block;font-size:.8rem;font-weight:600;color:var(--dim);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.35rem}
.ed-input{width:100%;padding:.6rem .85rem;background:rgba(255,255,255,0.04);border:1px solid var(--bdr);border-radius:8px;color:var(--txt);font-family:var(--bf);font-size:.95rem;outline:none;transition:border-color .2s}
.ed-input:focus{border-color:var(--teal)}
.ed-input::placeholder{color:var(--mute)}
select.ed-input{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath d='M3 5l3 3 3-3' fill='none' stroke='%2322A8B3' stroke-width='1.5'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right .75rem center;padding-right:2rem}
.ed-row{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
@media(max-width:600px){.ed-row{grid-template-columns:1fr}}

/* Quill overrides for dark theme */
.quill-wrap{border:1px solid var(--bdr);border-radius:8px;overflow:hidden;margin-bottom:.5rem}
.quill-wrap:focus-within{border-color:var(--teal)}
.quill-wrap .ql-toolbar{background:rgba(255,255,255,0.03);border:none;border-bottom:1px solid var(--bdr);padding:8px 12px;flex-wrap:wrap}
.quill-wrap .ql-toolbar .ql-stroke{stroke:var(--dim)}
.quill-wrap .ql-toolbar .ql-fill{fill:var(--dim)}
.quill-wrap .ql-toolbar .ql-picker-label{color:var(--dim)}
.quill-wrap .ql-toolbar button:hover .ql-stroke,.quill-wrap .ql-toolbar .ql-picker-label:hover{stroke:var(--teal);color:var(--teal)}
.quill-wrap .ql-toolbar button.ql-active .ql-stroke{stroke:var(--teal)}
.quill-wrap .ql-toolbar button.ql-active{color:var(--teal)}
.quill-wrap .ql-toolbar .ql-picker-item:hover{color:var(--teal)}
.quill-wrap .ql-container{border:none;color:var(--txt);font-family:var(--bf);font-size:1rem;min-height:350px}
.quill-wrap .ql-editor{padding:1.25rem;min-height:350px;line-height:1.8}
.quill-wrap .ql-editor.ql-blank::before{color:var(--mute);font-style:italic}
.quill-wrap .ql-editor h1{font-family:var(--hf);font-size:1.6rem;color:var(--teal);margin:.75rem 0 .5rem}
.quill-wrap .ql-editor h2{font-family:var(--hf);font-size:1.3rem;color:var(--teal);margin:.75rem 0 .4rem}
.quill-wrap .ql-editor h3{font-family:var(--hf);font-size:1.1rem;color:var(--orange);margin:.5rem 0 .3rem}
.quill-wrap .ql-editor p{margin-bottom:.6rem}
.quill-wrap .ql-editor a{color:var(--teal)}
.quill-wrap .ql-editor blockquote{border-left:3px solid var(--gold);padding-left:1rem;color:var(--dim);margin:.75rem 0;font-style:italic}
.quill-wrap .ql-editor pre{background:rgba(255,255,255,0.05);border:1px solid var(--bdr);border-radius:6px;padding:.75rem 1rem;font-family:monospace;font-size:.9rem;margin:.75rem 0;overflow-x:auto}
.quill-wrap .ql-editor img{max-width:100%;border-radius:8px;margin:.5rem 0}
.quill-wrap .ql-editor iframe{max-width:100%;border-radius:8px}
.quill-wrap .ql-editor ul,.quill-wrap .ql-editor ol{padding-left:1.5rem;margin:.5rem 0}
.quill-wrap .ql-editor li{margin-bottom:.25rem}
.quill-wrap .ql-editor hr{border:none;border-top:1px solid rgba(255,255,255,0.1);margin:1.5rem 0}
.quill-wrap .ql-snow .ql-picker-options{background:var(--bg);border-color:var(--bdr)}
.quill-wrap .ql-snow .ql-picker-options .ql-picker-item{color:var(--dim)}
.quill-wrap .ql-snow .ql-picker-options .ql-picker-item:hover{color:var(--teal)}
.quill-wrap .ql-snow .ql-tooltip{background:var(--bg);border-color:var(--bdr);color:var(--txt);box-shadow:0 4px 20px rgba(0,0,0,0.5)}
.quill-wrap .ql-snow .ql-tooltip input{background:rgba(255,255,255,0.05);border-color:var(--bdr);color:var(--txt)}
.quill-wrap .ql-snow .ql-tooltip a{color:var(--teal)}

/* Segment list in editor */
.seg-item{display:flex;align-items:center;gap:.6rem;padding:.6rem .75rem;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.05);border-radius:8px;margin-bottom:.4rem;cursor:pointer;transition:all .2s}
.seg-item:hover{background:var(--card-h);border-color:var(--bdr)}
.seg-item.editing{border-color:var(--teal);background:rgba(34,168,179,0.05)}
.seg-item .num{color:var(--mute);font-size:.75rem;font-weight:700;width:24px;text-align:center}
.seg-item .seg-title{flex:1;font-size:.9rem}
.seg-item .seg-actions{display:flex;gap:.25rem}

/* Users table */
.user-row{display:grid;grid-template-columns:auto 1fr 1fr auto auto;gap:1rem;align-items:center;padding:.75rem 1rem;border-bottom:1px solid rgba(255,255,255,0.04)}
.user-row:last-child{border:none}
.user-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--teal),var(--teal-d));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;flex-shrink:0}
.user-name{font-weight:600;font-size:.9rem}
.user-name small{display:block;color:var(--dim);font-weight:400;font-size:.8rem}
.user-role{font-size:.8rem;padding:.2rem .6rem;border-radius:12px;font-weight:600}
.user-role--admin{background:rgba(255,183,3,0.15);color:var(--gold)}
.user-role--leader{background:rgba(251,155,71,0.15);color:var(--orange)}
.user-role--rep{background:rgba(34,168,179,0.15);color:var(--teal)}

/* Toast */
.toast{position:fixed;top:1rem;right:1rem;padding:.6rem 1.2rem;border-radius:8px;font-weight:700;font-size:.9rem;z-index:9999;transform:translateX(120%);transition:transform .3s}
.toast.show{transform:translateX(0)}
.toast-ok{background:var(--green);color:#0a0a0f}
.toast-err{background:var(--red);color:#fff}

/* Responsive */
.content-layout{display:grid;grid-template-columns:320px 1fr;gap:1rem}
@media(max-width:800px){.content-layout{grid-template-columns:1fr}.tree-panel{max-height:300px;overflow-y:auto}}

/* Drag and Drop */
.drag-item{cursor:grab;transition:opacity .2s,transform .2s}
.drag-item:active{cursor:grabbing}
.drag-item.dragging{opacity:.4;transform:scale(.95)}
.drag-over{outline:2px dashed var(--teal);outline-offset:-2px;background:rgba(34,168,179,0.06) !important}
.drop-col{min-height:60px;transition:background .2s}
.drop-col.drag-over{background:rgba(34,168,179,0.08)}
.drag-handle{cursor:grab;color:var(--mute);font-size:.9rem;padding:0 .25rem;user-select:none}
.drag-handle:hover{color:var(--teal)}

/* Glow animation for pending passoffs */
@keyframes glow{0%,100%{box-shadow:0 0 15px rgba(255,183,3,0.3)}50%{box-shadow:0 0 30px rgba(255,183,3,0.6)}}
@keyframes pulse{0%,100%{opacity:.8}50%{opacity:1}}
</style>
</head>
<body>

<header class="mgr-hdr">
  <span class="mgr-logo">Become Admin</span>
  <div class="mgr-tabs">
    <button class="mgr-tab active" onclick="switchPanel('content',this)">📚 Content</button>
    <button class="mgr-tab" onclick="switchPanel('flow',this)">🗺️ Progression</button>
    <button class="mgr-tab" onclick="switchPanel('passoffs',this)" id="passoff-tab">🎯 Pass-offs <span id="passoff-count" style="display:none;background:var(--red);color:#fff;border-radius:50%;font-size:.7rem;padding:1px 6px;margin-left:.25rem;animation:pulse 1.5s ease infinite"></span></button>
    <button class="mgr-tab" onclick="switchPanel('users',this)">👥 Users</button>
  </div>
  <div class="mgr-hdr-right">
    <span style="color:var(--dim);font-size:.85rem"><?= $userName ?></span>
    <a href="/become/">← Portal</a>
    <a href="/become/logout.php">Log Out</a>
  </div>
</header>

<div class="mgr">

  <!-- CONTENT PANEL -->
  <div id="panel-content" class="panel active">
    <div class="sec-hdr">
      <h2>📚 Training Content</h2>
      <button class="btn btn-teal" onclick="addFolder()">+ New Folder</button>
    </div>
    <div class="content-layout">
      <div class="tree-panel" id="tree"></div>
      <div id="editorArea">
        <div class="editor">
          <div class="editor-empty">
            <div class="icon">📝</div>
            <h3>Select a module to edit</h3>
            <p style="color:var(--mute);margin-top:.5rem">Choose a module from the tree on the left, or create a new folder to get started.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- PROGRESSION PLANNER -->
  <div id="panel-flow" class="panel">
    <div class="sec-hdr">
      <h2>🗺️ Progression Planner</h2>
    </div>
    <p style="color:var(--dim);margin-bottom:1.5rem">Drag module cards between columns to change their unlock level. Drop into "Sequential" for linear progression, or into a Level column to unlock at that level.</p>
    <div id="flowBoard" style="overflow-x:auto"></div>
  </div>

  <!-- PASS-OFFS PANEL -->
  <div id="panel-passoffs" class="panel">
    <div class="sec-hdr">
      <h2>🎯 Pending Pass-offs</h2>
      <button class="btn btn-ghost" onclick="loadPassoffs()">↻ Refresh</button>
    </div>
    <p style="color:var(--dim);margin-bottom:1rem">Reps waiting for you to verify their memorization or skill. Click "Pass ✓" to approve and auto-complete the segment for them.</p>
    <div id="passoffsList" class="card">
      <div style="text-align:center;padding:2rem;color:var(--mute)">Loading...</div>
    </div>
  </div>

  <!-- USERS PANEL -->
  <div id="panel-users" class="panel">
    <div class="sec-hdr">
      <h2>👥 Team Members</h2>
      <button class="btn btn-teal" onclick="showAddUser()">+ Add Rep</button>
    </div>
    <div class="card" id="usersList"></div>
    <div class="card" id="addUserForm" style="display:none">
      <h3 style="margin-bottom:1rem;font-family:var(--hf)">New Team Member</h3>
      <div class="ed-row">
        <div class="ed-field"><label>Username</label><input class="ed-input" id="nu-user" placeholder="jsmith"></div>
        <div class="ed-field"><label>Password</label><input class="ed-input" id="nu-pass" type="password" placeholder="Temporary password"></div>
      </div>
      <div class="ed-row">
        <div class="ed-field"><label>First Name</label><input class="ed-input" id="nu-first"></div>
        <div class="ed-field"><label>Last Name</label><input class="ed-input" id="nu-last"></div>
      </div>
      <div class="ed-field"><label>Role</label>
        <select class="ed-input" id="nu-role" onchange="suggestStartLevel(this.value)"><option value="rep">Rep (Rookie)</option><option value="trainer">Trainer</option><option value="leader">Leader</option><option value="admin">Admin</option></select>
      </div>
      <div class="ed-field"><label>Starting Level — Controls what content they can access immediately</label>
        <select class="ed-input" id="nu-level">
          <option value="1">Level 1 — Rookie 🌱 (default for new reps)</option>
          <option value="2">Level 2 — Apprentice ⚡</option>
          <option value="3">Level 3 — Builder 🔨</option>
          <option value="4">Level 4 — Closer 🎯</option>
          <option value="5">Level 5 — Expert 🏆</option>
          <option value="6">Level 6 — Master 👑</option>
          <option value="7">Level 7 — Legend 🌟</option>
        </select>
        <p style="color:var(--mute);font-size:.75rem;margin-top:.3rem">Higher starting level = more folders and modules unlocked from day one. Leaders and closers typically start at level 4+.</p>
      </div>
      <div style="display:flex;gap:.5rem;margin-top:1rem">
        <button class="btn btn-green" onclick="createUser()">Create</button>
        <button class="btn btn-ghost" onclick="document.getElementById('addUserForm').style.display='none'">Cancel</button>
      </div>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script>
const API = '/become/api/admin.php';
let data = { folders:[], modules:[], segments:[], users:[], thresholds:[] };
let selectedModId = null;
let quillEditor = null;

// ─── API Helper ───
async function api(method, params) {
  try {
    const opts = method === 'GET'
      ? { method:'GET' }
      : { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(params) };
    const url = method === 'GET' ? API + '?action=' + params.action : API;
    const res = await fetch(url, opts);
    const json = await res.json();
    if (json.error) throw new Error(json.error);
    return json;
  } catch(e) { toast(e.message, true); throw e; }
}

// ─── Load All Data ───
async function loadAll() {
  const d = await api('GET', {action:'all'});
  data = d;
  renderTree();
  renderUsers();
  renderFlow();
  loadPassoffs();
  if (selectedModId) openModuleEditor(selectedModId);
}

// ─── TREE ───
function renderTree() {
  const tree = document.getElementById('tree');
  const folders = data.folders.filter(f => !f.parent_id);
  tree.innerHTML = folders.map(f => folderHTML(f)).join('') || '<div style="text-align:center;padding:2rem;color:var(--mute)">No folders yet. Click "+ New Folder" to start.</div>';
}

function folderHTML(f) {
  const mods = data.modules.filter(m => m.folder_id == f.id).sort((a,b) => a.module_order - b.module_order);
  const children = data.folders.filter(c => c.parent_id == f.id);
  const rule = f.unlock_rule;
  const lvlReq = rule && rule.kind === 'level' ? rule.value : 0;
  const lvlBadge = lvlReq ? `<span style="font-size:.7rem;padding:2px 6px;border-radius:10px;background:rgba(255,183,3,0.15);color:var(--gold);font-weight:700;margin-left:.25rem">Lvl ${lvlReq}+</span>` : '<span style="font-size:.7rem;padding:2px 6px;border-radius:10px;background:rgba(6,214,160,0.15);color:var(--green);font-weight:700;margin-left:.25rem">Open</span>';
  return `
    <div class="tree-folder open" data-id="${f.id}">
      <div class="tree-folder-hdr" onclick="this.parentElement.classList.toggle('open')">
        <span class="icon">${f.icon||'📁'}</span>
        <span class="title">${esc(f.title)}</span>
        ${lvlBadge}
        <span class="meta">${mods.length} mod</span>
        <span class="arrow">▸</span>
      </div>
      <div class="tree-folder-body">
        <div style="display:flex;gap:.4rem;margin-bottom:.5rem;padding:.25rem 0">
          <button class="btn btn-sm btn-teal" onclick="event.stopPropagation();addModule(${f.id})">+ Module</button>
          <button class="btn btn-sm btn-ghost" onclick="event.stopPropagation();openFolderEditor(${f.id})">✏️ Edit</button>
          <button class="btn btn-sm btn-red" onclick="event.stopPropagation();deleteFolder(${f.id})">✕</button>
        </div>
        ${mods.map((m, mi) => {
          const segCount = data.segments.filter(s => s.module_id == m.id).length;
          const sel = selectedModId == m.id ? ' selected' : '';
          const mRule = m.unlock_rule;
          const mLvl = mRule && mRule.kind === 'level' ? mRule.value : 0;
          const mBadge = mLvl ? `<span style="font-size:.65rem;padding:1px 5px;border-radius:8px;background:rgba(255,183,3,0.12);color:var(--gold);font-weight:600">L${mLvl}</span>` : '';
          return `<div class="tree-mod${sel} drag-item" draggable="true" data-mod-id="${m.id}" data-folder-id="${f.id}" data-order="${mi}"
            ondragstart="dragModStart(event,${m.id})" ondragover="dragModOver(event)" ondrop="dropMod(event,${f.id},${mi})" ondragend="dragEnd(event)">
            <span class="drag-handle" title="Drag to reorder">⠿</span>
            <span class="icon">${m.icon||'📄'}</span>
            <span class="title" onclick="openModuleEditor(${m.id})">${esc(m.title)}</span>
            ${mBadge}
            <span class="count">${segCount} segs</span>
          </div>`;
        }).join('')}
        ${children.map(c => folderHTML(c)).join('')}
      </div>
    </div>`;
}

// ─── MODULE EDITOR ───
function openModuleEditor(modId) {
  selectedModId = modId;
  const mod = data.modules.find(m => m.id == modId);
  if (!mod) return;
  const segs = data.segments.filter(s => s.module_id == modId).sort((a,b) => a.segment_order - b.segment_order);
  
  document.querySelectorAll('.tree-mod').forEach(el => el.classList.remove('selected'));
  const sel = document.querySelector(`.tree-mod[onclick*="openModuleEditor(${modId})"]`);
  if (sel) sel.classList.add('selected');

  const area = document.getElementById('editorArea');
  const curUnlock = mod.unlock_rule;
  const curLvl = curUnlock && curUnlock.kind === 'level' ? curUnlock.value : 0;
  const levelOpts = [0,1,2,3,4,5,6,7].map(l => `<option value="${l}" ${curLvl==l?'selected':''}>${l===0?'Sequential (complete previous first)':'Level '+l+' — '+(data.thresholds.find(t=>t.level==l)||{title:'?'}).title}</option>`).join('');

  area.innerHTML = `
    <div class="editor">
      <div class="ed-field"><label>Module Title</label>
        <input class="ed-input" id="ed-mod-title" value="${esc(mod.title)}" onchange="updateModule(${modId},{title:this.value})">
      </div>
      <div class="ed-row">
        <div class="ed-field"><label>Icon</label><input class="ed-input" id="ed-mod-icon" value="${mod.icon||''}" style="max-width:80px" onchange="updateModule(${modId},{icon:this.value})"></div>
        <div class="ed-field"><label>XP Reward</label><input class="ed-input" type="number" value="${mod.xp_reward||50}" onchange="updateModule(${modId},{xp_reward:parseInt(this.value)})"></div>
      </div>
      <div class="ed-field"><label>🔓 Unlock Requirement</label>
        <select class="ed-input" onchange="setModuleUnlock(${modId}, parseInt(this.value))">${levelOpts}</select>
        <p style="color:var(--mute);font-size:.75rem;margin-top:.3rem">Sequential = rep must complete the previous module first. Level-based = unlocks when rep reaches that level (can skip ahead).</p>
      </div>
      <div class="ed-field"><label>Description</label>
        <input class="ed-input" value="${esc(mod.description||'')}" placeholder="Optional short description" onchange="updateModule(${modId},{description:this.value})">
      </div>

      <div style="display:flex;justify-content:space-between;align-items:center;margin:1.5rem 0 .75rem">
        <label style="font-size:.8rem;font-weight:600;color:var(--dim);text-transform:uppercase;letter-spacing:.05em">Segments</label>
        <button class="btn btn-sm btn-green" onclick="addSegment(${modId})">+ Add Segment</button>
      </div>
      <div id="seg-list">${segs.map((s,i) => segItemHTML(s,i)).join('')}</div>
      
      <div id="seg-editor" style="margin-top:1.5rem"></div>
    </div>`;
}

function segItemHTML(s, i) {
  const isPassoff = s.segment_type === 'passoff';
  return `<div class="seg-item" id="si-${s.id}" onclick="openSegEditor(${s.id})">
    <span class="num">${i+1}</span>
    <span class="seg-title">${esc(s.title)}</span>
    ${isPassoff ? '<span style="font-size:.65rem;padding:2px 6px;border-radius:8px;background:rgba(255,183,3,0.15);color:var(--gold);font-weight:700">🎯 Pass-off</span>' : ''}
    <span style="color:var(--green);font-size:.75rem;font-weight:600">+${s.xp_reward} XP</span>
    <div class="seg-actions">
      <button class="btn btn-sm btn-red" onclick="event.stopPropagation();deleteSegment(${s.id})">✕</button>
    </div>
  </div>`;
}

// ─── SEGMENT EDITOR (with Quill) ───
function openSegEditor(segId) {
  document.querySelectorAll('.seg-item').forEach(el => el.classList.remove('editing'));
  const si = document.getElementById('si-' + segId);
  if (si) si.classList.add('editing');

  const seg = data.segments.find(s => s.id == segId);
  if (!seg) return;

  const ed = document.getElementById('seg-editor');
  ed.innerHTML = `
    <div class="card" style="border-color:var(--bdr-a)">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h3 style="font-family:var(--hf);font-size:1.1rem">✏️ Editing: ${esc(seg.title)}</h3>
        <button class="btn btn-sm btn-ghost" onclick="closeSegEditor()">Close</button>
      </div>
      <div class="ed-row">
        <div class="ed-field"><label>Segment Title</label>
          <input class="ed-input" id="ed-seg-title" value="${esc(seg.title)}" onchange="updateSegment(${segId},{title:this.value})">
        </div>
        <div class="ed-field"><label>XP Reward</label>
          <input class="ed-input" type="number" value="${seg.xp_reward||10}" onchange="updateSegment(${segId},{xp_reward:parseInt(this.value)})">
        </div>
      </div>
      <div class="ed-field"><label>🎯 Segment Type</label>
        <select class="ed-input" onchange="updateSegment(${segId},{segment_type:this.value})">
          <option value="lesson" ${(seg.segment_type||'lesson')==='lesson'?'selected':''}>📄 Lesson — Rep marks complete on their own</option>
          <option value="passoff" ${seg.segment_type==='passoff'?'selected':''}>🎯 Leader Pass-off — Rep must be approved by a leader</option>
        </select>
        <p style="color:var(--mute);font-size:.75rem;margin-top:.3rem">${seg.segment_type==='passoff' ? '⚡ This segment requires leader approval. The rep will see "Request Pass-off" instead of "Mark Complete".' : 'Standard lesson — rep completes it independently.'}</p>
      </div>
      <div class="ed-field"><label>Content</label>
        <div class="quill-wrap"><div id="quill-seg"></div></div>
        <div style="display:flex;gap:.5rem;align-items:center;margin-top:.5rem;flex-wrap:wrap">
          <button class="btn btn-teal" onclick="saveSegContent(${segId})">💾 Save Content</button>
          <button class="btn btn-ghost btn-sm" onclick="insertPDF(${segId})">📎 Attach PDF</button>
          <button class="btn btn-ghost btn-sm" onclick="insertDivider()">— Divider</button>
          <span id="save-status" style="color:var(--green);font-size:.8rem;margin-left:auto"></span>
        </div>
        <p style="color:var(--mute);font-size:.75rem;margin-top:.5rem">Tip: Shift+Enter = new line · Enter = new paragraph · Ctrl+B = bold · Ctrl+I = italic · Ctrl+K = link</p>
      </div>
      <div style="border-top:1px solid rgba(255,255,255,0.05);margin-top:1.25rem;padding-top:1.25rem">
        <label style="font-size:.8rem;font-weight:600;color:var(--dim);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:.75rem">💬 Conversation Bubble (optional — shows as a role-play example)</label>
        <div class="ed-field"><label>🗣️ Customer Quote</label>
          <textarea class="ed-input" id="ed-seg-cq" rows="2" placeholder="What the customer might say..." onchange="updateSegment(${segId},{customer_quote:this.value})" style="resize:vertical;min-height:60px">${esc(seg.customer_quote||'')}</textarea>
        </div>
        <div class="ed-field"><label>💪 Rep Response</label>
          <textarea class="ed-input" id="ed-seg-rr" rows="2" placeholder="How to respond..." onchange="updateSegment(${segId},{rep_response:this.value})" style="resize:vertical;min-height:60px">${esc(seg.rep_response||'')}</textarea>
        </div>
        <div class="ed-field"><label>💡 Pro Tip</label>
          <textarea class="ed-input" id="ed-seg-tip" rows="2" placeholder="Pro tip for the rep..." onchange="updateSegment(${segId},{tip:this.value})" style="resize:vertical;min-height:60px">${esc(seg.tip||'')}</textarea>
        </div>
      </div>
    </div>`;

  quillEditor = new Quill('#quill-seg', {
    theme:'snow',
    placeholder:'Start writing your training content...\n\nTip: Use headers to organize sections, bold for key terms, and lists for steps.',
    modules:{
      toolbar: {
        container: [
          [{ 'header': [1, 2, 3, false] }],
          [{ 'size': ['small', false, 'large', 'huge'] }],
          ['bold', 'italic', 'underline', 'strike'],
          [{ 'color': [] }, { 'background': [] }],
          [{ 'list': 'ordered' }, { 'list': 'bullet' }],
          [{ 'indent': '-1' }, { 'indent': '+1' }],
          [{ 'align': [] }],
          ['blockquote', 'code-block'],
          ['link', 'image', 'video'],
          [{ 'script': 'sub' }, { 'script': 'super' }],
          ['clean']
        ],
        handlers: {
          'image': function() {
            const url = prompt('Image URL (paste a link to an image):');
            if (url) {
              const range = this.quill.getSelection(true);
              this.quill.insertEmbed(range.index, 'image', url);
              this.quill.setSelection(range.index + 1);
            }
          }
        }
      },
      keyboard: {
        bindings: {
          // Shift+Enter inserts a line break (Quill default handles this, but ensure it's explicit)
          linebreak: {
            key: 13,
            shiftKey: true,
            handler: function(range) {
              this.quill.insertText(range.index, '\n');
              this.quill.setSelection(range.index + 1);
              return false;
            }
          }
        }
      }
    }
  });
  quillEditor.root.innerHTML = seg.content_html || '';

  // Auto-save after 2 seconds of inactivity
  let autoSaveTimer = null;
  const statusEl = document.getElementById('save-status');
  quillEditor.on('text-change', function() {
    if (statusEl) statusEl.textContent = '● Unsaved changes';
    if (statusEl) statusEl.style.color = 'var(--gold)';
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(async () => {
      const html = quillEditor.root.innerHTML;
      const content = html === '<p><br></p>' ? '' : html;
      try {
        await api('POST', {action:'update_segment', id: segId, content_html: content});
        const s = data.segments.find(x => x.id == segId);
        if (s) s.content_html = content;
        if (statusEl) { statusEl.textContent = '✓ Auto-saved'; statusEl.style.color = 'var(--green)'; }
      } catch(e) {
        if (statusEl) { statusEl.textContent = '✕ Save failed'; statusEl.style.color = 'var(--red)'; }
      }
    }, 2000);
  });
}

function closeSegEditor() {
  document.getElementById('seg-editor').innerHTML = '';
  document.querySelectorAll('.seg-item').forEach(el => el.classList.remove('editing'));
  quillEditor = null;
}

async function saveSegContent(segId) {
  if (!quillEditor) return;
  const html = quillEditor.root.innerHTML;
  const content = html === '<p><br></p>' ? '' : html;
  await updateSegment(segId, {content_html: content});
  const statusEl = document.getElementById('save-status');
  if (statusEl) { statusEl.textContent = '✓ Saved'; statusEl.style.color = 'var(--green)'; }
}

function insertPDF(segId) {
  const url = prompt('PDF URL (paste a link to the PDF file):');
  if (!url) return;
  const title = prompt('Display title for the PDF:', 'View PDF');
  if (!quillEditor) return;
  const range = quillEditor.getSelection(true);
  // Insert as a styled link
  quillEditor.insertText(range.index, '\n');
  quillEditor.insertText(range.index + 1, '📄 ' + (title || 'View PDF'), {link: url});
  quillEditor.insertText(range.index + 1 + title.length + 3, '\n');
  quillEditor.setSelection(range.index + title.length + 5);
}

function insertDivider() {
  if (!quillEditor) return;
  const range = quillEditor.getSelection(true);
  quillEditor.insertText(range.index, '\n───────────────────\n');
  quillEditor.setSelection(range.index + 22);
}

// ─── PROGRESSION FLOW BOARD ───
function renderFlow() {
  const board = document.getElementById('flowBoard');
  if (!board) return;

  const levels = {};
  const seqModules = [];

  data.modules.forEach(m => {
    const rule = m.unlock_rule;
    const lvl = rule && rule.kind === 'level' ? rule.value : 0;
    if (lvl === 0) seqModules.push(m);
    else { if (!levels[lvl]) levels[lvl] = []; levels[lvl].push(m); }
  });

  const maxLvl = Math.max(7, ...Object.keys(levels).map(Number));
  const thresholds = data.thresholds || [];

  let html = '<div style="display:flex;gap:.75rem;min-width:max-content;padding-bottom:1rem">';

  // Sequential column
  html += `<div style="min-width:190px;flex-shrink:0">
    <div style="text-align:center;padding:.5rem;background:rgba(6,214,160,0.1);border-radius:8px 8px 0 0;border:1px solid rgba(6,214,160,0.2);border-bottom:none">
      <div style="font-weight:700;font-size:.85rem;color:var(--green)">Sequential</div>
      <div style="font-size:.7rem;color:var(--dim)">Complete in order</div>
    </div>
    <div class="drop-col" data-drop-level="0" ondragover="dragFlowOver(event)" ondragleave="dragFlowLeave(event)" ondrop="dropFlow(event,0)"
      style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.05);border-radius:0 0 8px 8px;padding:.5rem;min-height:100px">
      ${seqModules.length ? seqModules.map(m => flowModCard(m)).join('') : '<p style="color:var(--mute);font-size:.8rem;text-align:center;padding:1rem">Drop here</p>'}
    </div>
  </div>`;

  for (let lvl = 1; lvl <= maxLvl; lvl++) {
    const th = thresholds.find(t => t.level == lvl);
    const mods = levels[lvl] || [];
    const hasContent = mods.length > 0;
    html += `<div style="min-width:190px;flex-shrink:0;${hasContent?'':'opacity:.5'}">
      <div style="text-align:center;padding:.5rem;background:rgba(255,183,3,0.08);border-radius:8px 8px 0 0;border:1px solid rgba(255,183,3,0.15);border-bottom:none">
        <div style="font-weight:700;font-size:.85rem;color:var(--gold)">${th?th.badge_icon:''} Level ${lvl}</div>
        <div style="font-size:.7rem;color:var(--dim)">${th?th.title:'—'} · ${th?th.xp_required:0} XP</div>
      </div>
      <div class="drop-col" data-drop-level="${lvl}" ondragover="dragFlowOver(event)" ondragleave="dragFlowLeave(event)" ondrop="dropFlow(event,${lvl})"
        style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.05);border-radius:0 0 8px 8px;padding:.5rem;min-height:100px">
        ${mods.length ? mods.map(m => flowModCard(m)).join('') : '<p style="color:var(--mute);font-size:.8rem;text-align:center;padding:1rem">Drop here</p>'}
      </div>
    </div>`;
  }

  html += '</div>';
  board.innerHTML = html;
}

function flowModCard(m) {
  const folder = data.folders.find(f => f.id == m.folder_id);
  const folderName = folder ? folder.title : '?';
  const segCount = data.segments.filter(s => s.module_id == m.id).length;
  return `<div class="drag-item" draggable="true" data-flow-mod-id="${m.id}"
    ondragstart="dragFlowStart(event,${m.id})" ondragend="dragEnd(event)"
    style="padding:.5rem .6rem;background:var(--card);border:1px solid var(--bdr);border-radius:6px;margin-bottom:.35rem;cursor:grab">
    <div style="font-weight:600;font-size:.82rem">${m.icon||'📄'} ${esc(m.title)}</div>
    <div style="font-size:.7rem;color:var(--dim)">${esc(folderName)} · ${segCount} segs · ${m.xp_reward||50} XP</div>
  </div>`;
}

// ─── CRUD OPERATIONS ───
async function addFolder() {
  const title = prompt('Folder name:');
  if (!title) return;
  await api('POST', {action:'add_folder', title});
  await loadAll();
  toast('Folder created');
}

async function editFolder(id) {
  // Legacy — redirects to new editor
  openFolderEditor(id);
}

function openFolderEditor(id) {
  const f = data.folders.find(x => x.id == id);
  if (!f) return;
  selectedModId = null;
  const rule = f.unlock_rule;
  const curLvl = rule && rule.kind === 'level' ? rule.value : 0;
  const levelOpts = [0,1,2,3,4,5,6,7].map(l => `<option value="${l}" ${curLvl==l?'selected':''}>${l===0?'Open to all levels':'Level '+l+' — '+(data.thresholds.find(t=>t.level==l)||{title:'?'}).title}</option>`).join('');
  const typeOpts = ['core','sidequest'].map(t => `<option value="${t}" ${(f.folder_type||'core')===t?'selected':''}>${t==='core'?'📚 Core Training':'🗺️ Side Quest'}</option>`).join('');

  const area = document.getElementById('editorArea');
  area.innerHTML = `
    <div class="editor">
      <h3 style="font-family:var(--hf);font-size:1.2rem;margin-bottom:1.25rem">📁 Folder Settings: ${esc(f.title)}</h3>
      <div class="ed-field"><label>Folder Title</label>
        <input class="ed-input" value="${esc(f.title)}" onchange="updateFolder(${id},{title:this.value})">
      </div>
      <div class="ed-row">
        <div class="ed-field"><label>Icon</label><input class="ed-input" value="${f.icon||'📁'}" style="max-width:80px" onchange="updateFolder(${id},{icon:this.value})"></div>
        <div class="ed-field"><label>Folder Type</label>
          <select class="ed-input" onchange="updateFolder(${id},{folder_type:this.value})">${typeOpts}</select>
        </div>
      </div>
      <div class="ed-field"><label>🔓 Access Level — Who can see this folder?</label>
        <select class="ed-input" onchange="setFolderUnlock(${id}, parseInt(this.value))">${levelOpts}</select>
        <p style="color:var(--mute);font-size:.75rem;margin-top:.3rem">"Open to all" = visible from day one. Level-based = folder is locked until the rep reaches that level. Reps can still see the folder name (greyed out) but can't access content inside.</p>
      </div>
      <div class="ed-field"><label>XP Reward (for completing all modules in this folder)</label>
        <input class="ed-input" type="number" value="${f.xp_reward||200}" onchange="updateFolder(${id},{xp_reward:parseInt(this.value)})">
      </div>
      <div class="ed-field"><label>Description</label>
        <input class="ed-input" value="${esc(f.description||'')}" placeholder="What's in this folder?" onchange="updateFolder(${id},{description:this.value})">
      </div>

      <div style="border-top:1px solid rgba(255,255,255,0.05);margin-top:1.5rem;padding-top:1.5rem">
        <h4 style="font-size:.85rem;color:var(--dim);text-transform:uppercase;letter-spacing:.05em;margin-bottom:1rem">📋 Module Unlock Flow</h4>
        <p style="color:var(--mute);font-size:.8rem;margin-bottom:1rem">Set which level each module requires. Modules set to "Sequential" require the previous module to be completed first.</p>
        <div id="folder-flow-${id}">${renderFolderFlow(id)}</div>
      </div>
    </div>`;
}

function renderFolderFlow(folderId) {
  const mods = data.modules.filter(m => m.folder_id == folderId).sort((a,b) => a.module_order - b.module_order);
  if (!mods.length) return '<p style="color:var(--mute);font-size:.85rem">No modules yet. Add one from the tree.</p>';
  return mods.map((m, i) => {
    const rule = m.unlock_rule;
    const lvl = rule && rule.kind === 'level' ? rule.value : 0;
    const levelOpts = [0,1,2,3,4,5,6,7].map(l => `<option value="${l}" ${lvl==l?'selected':''}>${l===0?'Sequential':(data.thresholds.find(t=>t.level==l)||{title:'L'+l}).title+' (L'+l+')'}</option>`).join('');
    return `<div style="display:flex;align-items:center;gap:.75rem;padding:.5rem .75rem;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.04);border-radius:8px;margin-bottom:.35rem">
      <span style="color:var(--mute);font-size:.8rem;width:24px;text-align:center;font-weight:700">${i+1}</span>
      <span style="font-size:1rem">${m.icon||'📄'}</span>
      <span style="flex:1;font-weight:600;font-size:.88rem">${esc(m.title)}</span>
      <select class="ed-input" style="width:auto;font-size:.8rem;padding:.3rem .5rem" onchange="setModuleUnlock(${m.id},parseInt(this.value))">${levelOpts}</select>
    </div>`;
  }).join('');
}

async function setModuleUnlock(modId, level) {
  const unlock = level > 0 ? {kind:'level', value:level} : null;
  await api('POST', {action:'update_module', id:modId, unlock_rule: unlock});
  // Update local data
  const m = data.modules.find(x => x.id == modId);
  if (m) m.unlock_rule = unlock;
  renderTree();
  toast('Unlock rule updated');
}

async function setFolderUnlock(folderId, level) {
  const unlock = level > 0 ? {kind:'level', value:level} : null;
  await api('POST', {action:'update_folder', id:folderId, unlock_rule: unlock});
  const f = data.folders.find(x => x.id == folderId);
  if (f) f.unlock_rule = unlock;
  renderTree();
  toast('Folder access updated');
}

async function updateFolder(id, updates) {
  await api('POST', {action:'update_folder', id, ...updates});
  await loadAll();
  toast('Saved');
}

async function deleteFolder(id) {
  if (!confirm('Delete this folder and all its modules/segments?')) return;
  await api('POST', {action:'delete_folder', id});
  selectedModId = null;
  await loadAll();
  document.getElementById('editorArea').innerHTML = '<div class="editor"><div class="editor-empty"><div class="icon">📝</div><h3>Select a module to edit</h3></div></div>';
  toast('Folder deleted');
}

async function addModule(folderId) {
  const title = prompt('Module name:');
  if (!title) return;
  await api('POST', {action:'add_module', folder_id:folderId, title});
  await loadAll();
  toast('Module created');
}

async function updateModule(id, updates) {
  await api('POST', {action:'update_module', id, ...updates});
  await loadAll();
  toast('Saved');
}

async function addSegment(modId) {
  const title = prompt('Segment title:');
  if (!title) return;
  await api('POST', {action:'add_segment', module_id:modId, title});
  await loadAll();
  toast('Segment created');
}

async function updateSegment(id, updates) {
  await api('POST', {action:'update_segment', id, ...updates});
  // Update local data without full reload
  const seg = data.segments.find(s => s.id == id);
  if (seg) Object.assign(seg, updates);
  // Refresh seg list if title changed
  if (updates.title) await loadAll();
  toast('Saved');
}

async function deleteSegment(id) {
  if (!confirm('Delete this segment?')) return;
  await api('POST', {action:'delete_segment', id});
  closeSegEditor();
  await loadAll();
  toast('Segment deleted');
}

// ─── USERS ───
function renderUsers() {
  const el = document.getElementById('usersList');
  if (!data.users || data.users.length === 0) {
    el.innerHTML = '<div style="padding:2rem;text-align:center;color:var(--mute)">No users yet</div>';
    return;
  }
  el.innerHTML = data.users.map(u => {
    const initials = ((u.first_name||'')[0]||'') + ((u.last_name||'')[0]||'') || u.username[0].toUpperCase();
    const roleCls = u.role === 'admin' ? 'admin' : u.role === 'leader' ? 'leader' : 'rep';
    return `<div class="user-row">
      <div class="user-avatar">${esc(initials)}</div>
      <div class="user-name">${esc(u.first_name||'')} ${esc(u.last_name||'')}<small>@${esc(u.username)}</small></div>
      <span class="user-role user-role--${roleCls}">${u.role}</span>
      <select class="ed-input" style="width:auto;font-size:.8rem;padding:.3rem .5rem" onchange="updateUser(${u.id},{role:this.value})" ${u.role==='admin'?'disabled':''}>
        <option value="rep" ${u.role==='rep'?'selected':''}>Rep</option>
        <option value="trainer" ${u.role==='trainer'?'selected':''}>Trainer</option>
        <option value="leader" ${u.role==='leader'?'selected':''}>Leader</option>
        <option value="admin" ${u.role==='admin'?'selected':''}>Admin</option>
      </select>
      <select class="ed-input" style="width:auto;font-size:.8rem;padding:.3rem .5rem" onchange="setUserLevel(${u.id},parseInt(this.value))" title="Set access level">
        ${[1,2,3,4,5,6,7].map(l => `<option value="${l}">Lvl ${l}</option>`).join('')}
      </select>
      <div style="display:flex;gap:.25rem">
        <button class="btn btn-sm btn-ghost" onclick="resetPw(${u.id},'${esc(u.username)}')">🔑</button>
        ${u.role!=='admin'?`<button class="btn btn-sm btn-red" onclick="deleteUser(${u.id},'${esc(u.username)}')">✕</button>`:''}
      </div>
    </div>`;
  }).join('');
}

function showAddUser() { document.getElementById('addUserForm').style.display = 'block'; }

async function createUser() {
  const startLevel = parseInt(document.getElementById('nu-level').value) || 1;
  const d = {
    action:'add_user',
    username: document.getElementById('nu-user').value.trim(),
    password: document.getElementById('nu-pass').value,
    first_name: document.getElementById('nu-first').value.trim(),
    last_name: document.getElementById('nu-last').value.trim(),
    role: document.getElementById('nu-role').value
  };
  if (!d.username || !d.password) return toast('Username and password required', true);
  const result = await api('POST', d);

  // Set starting level if higher than 1
  if (startLevel > 1 && result.id) {
    // Find the XP needed for that level from thresholds
    const th = data.thresholds.find(t => t.level == startLevel);
    const xp = th ? th.xp_required : 0;
    // Update user_progress directly via a custom API call
    await fetch(API, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({action:'set_user_level', user_id: result.id, level: startLevel, xp: xp})
    });
  }

  document.getElementById('addUserForm').style.display = 'none';
  ['nu-user','nu-pass','nu-first','nu-last'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('nu-level').value = '1';
  await loadAll();
  toast('User created' + (startLevel > 1 ? ' at Level ' + startLevel : ''));
}

async function updateUser(id, updates) {
  await api('POST', {action:'update_user', id, ...updates});
  await loadAll();
  toast('Updated');
}

async function resetPw(id, username) {
  const pw = prompt(`New password for @${username}:`);
  if (!pw) return;
  await api('POST', {action:'reset_password', id, password:pw});
  toast('Password reset');
}

async function deleteUser(id, username) {
  if (!confirm(`Delete @${username}? This cannot be undone.`)) return;
  await api('POST', {action:'delete_user', id});
  await loadAll();
  toast('User deleted');
}

async function setUserLevel(userId, level) {
  const th = data.thresholds.find(t => t.level == level);
  const xp = th ? th.xp_required : 0;
  await api('POST', {action:'set_user_level', user_id: userId, level, xp});
  toast('Level set to ' + level);
}

// ─── DRAG AND DROP: Module reorder in tree ───
let dragModId = null;

function dragModStart(e, modId) {
  dragModId = modId;
  e.dataTransfer.effectAllowed = 'move';
  e.dataTransfer.setData('text/plain', modId);
  setTimeout(() => e.target.classList.add('dragging'), 0);
}

function dragModOver(e) {
  e.preventDefault();
  e.dataTransfer.dropEffect = 'move';
  const target = e.target.closest('.tree-mod');
  if (target) target.classList.add('drag-over');
}

async function dropMod(e, folderId, targetOrder) {
  e.preventDefault();
  document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
  if (!dragModId) return;
  // Update the module order
  await api('POST', {action:'update_module', id: dragModId, module_order: targetOrder});
  dragModId = null;
  await loadAll();
  toast('Reordered');
}

function dragEnd(e) {
  e.target.classList.remove('dragging');
  document.querySelectorAll('.drag-over,.dragging').forEach(el => el.classList.remove('drag-over','dragging'));
}

// ─── DRAG AND DROP: Flow board (change unlock level) ───
let dragFlowModId = null;

function dragFlowStart(e, modId) {
  dragFlowModId = modId;
  e.dataTransfer.effectAllowed = 'move';
  e.dataTransfer.setData('text/plain', modId);
  setTimeout(() => e.target.classList.add('dragging'), 0);
}

function dragFlowOver(e) {
  e.preventDefault();
  e.dataTransfer.dropEffect = 'move';
  e.currentTarget.classList.add('drag-over');
}

function dragFlowLeave(e) {
  e.currentTarget.classList.remove('drag-over');
}

async function dropFlow(e, level) {
  e.preventDefault();
  e.currentTarget.classList.remove('drag-over');
  if (!dragFlowModId) return;
  await setModuleUnlock(dragFlowModId, level);
  dragFlowModId = null;
  renderFlow();
}

// ─── STARTING LEVEL: Auto-suggest based on role ───
function suggestStartLevel(role) {
  const levelSelect = document.getElementById('nu-level');
  if (!levelSelect) return;
  const suggestions = { rep: '1', trainer: '3', leader: '4', admin: '7' };
  levelSelect.value = suggestions[role] || '1';
}

// ─── PASS-OFFS ───
async function loadPassoffs() {
  try {
    const res = await fetch('/become/api/index.php?route=passoffs');
    const items = await res.json();
    const el = document.getElementById('passoffsList');
    const badge = document.getElementById('passoff-count');

    if (!items || !Array.isArray(items) || items.length === 0) {
      if (el) el.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--mute)">🎉 No pending pass-offs</div>';
      if (badge) badge.style.display = 'none';
      return;
    }

    if (badge) { badge.style.display = 'inline'; badge.textContent = items.length; }

    if (el) el.innerHTML = items.map(p => `
      <div style="display:flex;align-items:center;gap:1rem;padding:1rem;border-bottom:1px solid rgba(255,255,255,0.04)">
        <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--orange));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0;box-shadow:0 0 20px rgba(255,183,3,0.4);animation:glow 2s ease infinite">
          ${esc((p.first_name||'')[0]||'')}${esc((p.last_name||'')[0]||'')}
        </div>
        <div style="flex:1">
          <div style="font-weight:700;font-size:.95rem">${esc(p.first_name||'')} ${esc(p.last_name||'')} <span style="color:var(--dim);font-weight:400;font-size:.85rem">@${esc(p.username)}</span></div>
          <div style="font-size:.88rem;color:var(--dim);margin-top:.15rem">${esc(p.mod_title)} → <strong style="color:var(--gold)">${esc(p.seg_title)}</strong></div>
          <div style="font-size:.75rem;color:var(--mute);margin-top:.15rem">Requested ${timeAgo(p.requested_at)}</div>
        </div>
        <button class="btn btn-green" onclick="approvePassoff(${p.id},this)" style="font-size:1.05rem;padding:.7rem 1.8rem;box-shadow:0 0 20px rgba(6,214,160,0.3)">✓ Pass</button>
      </div>
    `).join('');
  } catch(e) { console.error('Passoff load error:', e); }
}

async function approvePassoff(requestId, btn) {
  btn.disabled = true;
  btn.textContent = '⏳ Approving...';
  try {
    const res = await fetch('/become/api/index.php?route=passoff/' + requestId + '/approve', {
      method:'POST', headers:{'Content-Type':'application/json'}
    });
    const data = await res.json();
    if (data.success) {
      btn.parentElement.style.opacity = '.3';
      btn.textContent = '✓ Passed!';
      btn.style.background = 'var(--teal)';
      toast('Pass-off approved! Segment completed for rep.');
      setTimeout(() => loadPassoffs(), 1500);
    } else {
      btn.textContent = '✕ Error';
      btn.disabled = false;
      toast(data.error || 'Approval failed', true);
    }
  } catch(e) {
    btn.textContent = '✕ Error';
    btn.disabled = false;
  }
}

function timeAgo(dateStr) {
  const now = new Date();
  const then = new Date(dateStr);
  const mins = Math.floor((now - then) / 60000);
  if (mins < 1) return 'just now';
  if (mins < 60) return mins + ' min ago';
  const hrs = Math.floor(mins / 60);
  if (hrs < 24) return hrs + 'h ago';
  const days = Math.floor(hrs / 24);
  return days + 'd ago';
}

// Auto-refresh passoffs every 30 seconds
setInterval(loadPassoffs, 30000);

// ─── UI HELPERS ───
function switchPanel(name, btn) {
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.mgr-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('panel-' + name).classList.add('active');
  btn.classList.add('active');
}

function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function toast(msg, isErr) {
  const t = document.getElementById('toast');
  t.textContent = (isErr ? '✕ ' : '✓ ') + msg;
  t.className = 'toast show ' + (isErr ? 'toast-err' : 'toast-ok');
  clearTimeout(t._to);
  t._to = setTimeout(() => t.classList.remove('show'), 2500);
}

// ─── INIT ───
loadAll();
</script>
</body>
</html>
