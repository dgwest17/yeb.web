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
.quill-wrap .ql-toolbar{background:rgba(255,255,255,0.03);border:none;border-bottom:1px solid var(--bdr)}
.quill-wrap .ql-toolbar .ql-stroke{stroke:var(--dim)}
.quill-wrap .ql-toolbar .ql-fill{fill:var(--dim)}
.quill-wrap .ql-toolbar .ql-picker-label{color:var(--dim)}
.quill-wrap .ql-toolbar button:hover .ql-stroke{stroke:var(--teal)}
.quill-wrap .ql-toolbar button.ql-active .ql-stroke{stroke:var(--teal)}
.quill-wrap .ql-container{border:none;color:var(--txt);font-family:var(--bf);font-size:.95rem;min-height:200px}
.quill-wrap .ql-editor{padding:1rem;min-height:200px;line-height:1.7}
.quill-wrap .ql-editor.ql-blank::before{color:var(--mute);font-style:italic}
.quill-wrap .ql-editor h1,.quill-wrap .ql-editor h2,.quill-wrap .ql-editor h3{font-family:var(--hf);color:var(--teal)}
.quill-wrap .ql-editor a{color:var(--teal)}
.quill-wrap .ql-snow .ql-picker-options{background:var(--bg);border-color:var(--bdr)}

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
</style>
</head>
<body>

<header class="mgr-hdr">
  <span class="mgr-logo">Become Admin</span>
  <div class="mgr-tabs">
    <button class="mgr-tab active" onclick="switchPanel('content',this)">📚 Content</button>
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
        <select class="ed-input" id="nu-role"><option value="rep">Rep</option><option value="trainer">Trainer</option><option value="leader">Leader</option><option value="admin">Admin</option></select>
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
  if (selectedModId) openModuleEditor(selectedModId);
}

// ─── TREE ───
function renderTree() {
  const tree = document.getElementById('tree');
  const folders = data.folders.filter(f => !f.parent_id);
  tree.innerHTML = folders.map(f => folderHTML(f)).join('') || '<div style="text-align:center;padding:2rem;color:var(--mute)">No folders yet. Click "+ New Folder" to start.</div>';
}

function folderHTML(f) {
  const mods = data.modules.filter(m => m.folder_id == f.id);
  const children = data.folders.filter(c => c.parent_id == f.id);
  return `
    <div class="tree-folder open" data-id="${f.id}">
      <div class="tree-folder-hdr" onclick="this.parentElement.classList.toggle('open')">
        <span class="icon">${f.icon||'📁'}</span>
        <span class="title">${esc(f.title)}</span>
        <span class="meta">${mods.length} modules</span>
        <span class="arrow">▸</span>
      </div>
      <div class="tree-folder-body">
        <div style="display:flex;gap:.4rem;margin-bottom:.5rem;padding:.25rem 0">
          <button class="btn btn-sm btn-teal" onclick="event.stopPropagation();addModule(${f.id})">+ Module</button>
          <button class="btn btn-sm btn-ghost" onclick="event.stopPropagation();editFolder(${f.id})">✏️</button>
          <button class="btn btn-sm btn-red" onclick="event.stopPropagation();deleteFolder(${f.id})">✕</button>
        </div>
        ${mods.map(m => {
          const segCount = data.segments.filter(s => s.module_id == m.id).length;
          const sel = selectedModId == m.id ? ' selected' : '';
          return `<div class="tree-mod${sel}" onclick="openModuleEditor(${m.id})">
            <span class="icon">${m.icon||'📄'}</span>
            <span class="title">${esc(m.title)}</span>
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
  area.innerHTML = `
    <div class="editor">
      <div class="ed-field"><label>Module Title</label>
        <input class="ed-input" id="ed-mod-title" value="${esc(mod.title)}" onchange="updateModule(${modId},{title:this.value})">
      </div>
      <div class="ed-row">
        <div class="ed-field"><label>Icon</label><input class="ed-input" id="ed-mod-icon" value="${mod.icon||''}" style="max-width:80px" onchange="updateModule(${modId},{icon:this.value})"></div>
        <div class="ed-field"><label>XP Reward</label><input class="ed-input" type="number" value="${mod.xp_reward||50}" onchange="updateModule(${modId},{xp_reward:parseInt(this.value)})"></div>
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
  return `<div class="seg-item" id="si-${s.id}" onclick="openSegEditor(${s.id})">
    <span class="num">${i+1}</span>
    <span class="seg-title">${esc(s.title)}</span>
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
      <div class="ed-field"><label>Content</label>
        <div class="quill-wrap"><div id="quill-seg"></div></div>
        <button class="btn btn-teal" onclick="saveSegContent(${segId})" style="margin-top:.5rem">💾 Save Content</button>
      </div>
      <div style="border-top:1px solid rgba(255,255,255,0.05);margin-top:1.25rem;padding-top:1.25rem">
        <label style="font-size:.8rem;font-weight:600;color:var(--dim);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:.75rem">💬 Conversation Bubble (optional)</label>
        <div class="ed-field"><label>Customer Quote</label>
          <input class="ed-input" id="ed-seg-cq" value="${esc(seg.customer_quote||'')}" placeholder="What the customer says..." onchange="updateSegment(${segId},{customer_quote:this.value})">
        </div>
        <div class="ed-field"><label>Rep Response</label>
          <input class="ed-input" id="ed-seg-rr" value="${esc(seg.rep_response||'')}" placeholder="How to respond..." onchange="updateSegment(${segId},{rep_response:this.value})">
        </div>
        <div class="ed-field"><label>💡 Tip</label>
          <input class="ed-input" id="ed-seg-tip" value="${esc(seg.tip||'')}" placeholder="Pro tip for the rep..." onchange="updateSegment(${segId},{tip:this.value})">
        </div>
      </div>
    </div>`;

  quillEditor = new Quill('#quill-seg', {
    theme:'snow',
    placeholder:'Write your training content here...',
    modules:{toolbar:[
      ['bold','italic','underline','strike'],
      [{header:[1,2,3,false]}],
      [{list:'ordered'},{list:'bullet'}],
      [{color:[]},{background:[]}],
      ['link','image','video'],
      ['clean']
    ]}
  });
  quillEditor.root.innerHTML = seg.content_html || '';
}

function closeSegEditor() {
  document.getElementById('seg-editor').innerHTML = '';
  document.querySelectorAll('.seg-item').forEach(el => el.classList.remove('editing'));
  quillEditor = null;
}

async function saveSegContent(segId) {
  if (!quillEditor) return;
  const html = quillEditor.root.innerHTML;
  if (html === '<p><br></p>') return updateSegment(segId, {content_html:''});
  await updateSegment(segId, {content_html: html});
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
  const f = data.folders.find(x => x.id == id);
  if (!f) return;
  const title = prompt('Folder title:', f.title);
  if (title === null) return;
  await api('POST', {action:'update_folder', id, title});
  await loadAll();
  toast('Folder updated');
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
      <div style="display:flex;gap:.25rem">
        <button class="btn btn-sm btn-ghost" onclick="resetPw(${u.id},'${esc(u.username)}')">🔑</button>
        ${u.role!=='admin'?`<button class="btn btn-sm btn-red" onclick="deleteUser(${u.id},'${esc(u.username)}')">✕</button>`:''}
      </div>
    </div>`;
  }).join('');
}

function showAddUser() { document.getElementById('addUserForm').style.display = 'block'; }

async function createUser() {
  const d = {
    action:'add_user',
    username: document.getElementById('nu-user').value.trim(),
    password: document.getElementById('nu-pass').value,
    first_name: document.getElementById('nu-first').value.trim(),
    last_name: document.getElementById('nu-last').value.trim(),
    role: document.getElementById('nu-role').value
  };
  if (!d.username || !d.password) return toast('Username and password required', true);
  await api('POST', d);
  document.getElementById('addUserForm').style.display = 'none';
  ['nu-user','nu-pass','nu-first','nu-last'].forEach(id => document.getElementById(id).value = '');
  await loadAll();
  toast('User created');
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
