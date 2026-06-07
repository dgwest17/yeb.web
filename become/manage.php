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

/* Flow Board — Compact stage-based */
.flow-nav{display:flex;gap:.3rem;flex-wrap:wrap;padding:.4rem 0;margin-bottom:.4rem;position:sticky;top:0;z-index:10;background:var(--bg)}
.flow-nav-btn{padding:.25rem .55rem;border-radius:6px;border:1px solid rgba(255,255,255,0.06);background:rgba(255,255,255,0.02);color:var(--dim);font-size:.68rem;font-weight:600;cursor:pointer;transition:all .12s;font-family:var(--bf);white-space:nowrap}
.flow-nav-btn:hover{border-color:var(--bdr-a);color:var(--txt)}
.flow-nav-btn.active{border-color:var(--teal);color:var(--teal);background:rgba(34,168,179,0.08)}
.flow-section{margin-bottom:.35rem;border:1px solid rgba(255,255,255,0.03);border-radius:8px;overflow:hidden}
.flow-hdr{display:flex;align-items:center;gap:.4rem;padding:.4rem .65rem;background:rgba(255,255,255,0.02);cursor:pointer;transition:all .12s;user-select:none}
.flow-hdr:hover{background:rgba(255,255,255,0.035)}
.flow-arrow{font-size:.65rem;color:var(--mute);transition:transform .12s;width:10px}
.flow-open > .flow-hdr{background:rgba(255,255,255,0.03)}
.flow-open > .flow-hdr .flow-arrow{transform:rotate(90deg)}
.flow-body{display:none;padding:.25rem .4rem .4rem}
.flow-open > .flow-body{display:block}
.flow-level-drop{min-height:24px}
.flow-level-drop.level-drop-over{background:rgba(255,183,3,0.06);border:2px dashed var(--gold);border-radius:6px}
.flow-empty{color:var(--mute);font-size:.72rem;text-align:center;padding:1rem;border:1px dashed rgba(255,255,255,0.05);border-radius:4px}
.flow-arrow-down{text-align:center;color:rgba(255,255,255,0.1);font-size:.6rem;line-height:1;padding:1px 0;user-select:none}
.flow-stage{border:1px solid rgba(255,255,255,0.04);border-radius:6px;padding:.25rem;margin-bottom:.15rem;background:rgba(255,255,255,0.008)}
.flow-stage.stage-drop-over{background:rgba(34,168,179,0.1);border-color:var(--teal);border-style:dashed;box-shadow:inset 0 0 12px rgba(34,168,179,0.1)}
.flow-stage-hdr{display:flex;align-items:center;justify-content:space-between;padding:0 .15rem;margin-bottom:.15rem}
.flow-stage-num{font-size:.58rem;font-weight:700;color:var(--mute);text-transform:uppercase;letter-spacing:.03em}
.flow-stage-hint{font-size:.55rem;color:var(--teal);font-style:italic}
.flow-stage-cards{display:flex;gap:.25rem;flex-wrap:wrap}
.flow-mod{display:flex;align-items:center;gap:.25rem;padding:.25rem .4rem;background:var(--card);border:1px solid var(--bdr);border-radius:5px;cursor:grab;transition:all .1s;flex:1;min-width:120px;max-width:100%}
.flow-mod:hover{background:var(--card-h);border-color:var(--bdr-a)}
.flow-mod.dragging{opacity:.15}
.flow-mod-handle{color:var(--mute);font-size:.65rem;cursor:grab;flex-shrink:0}
.flow-mod-info{flex:1;min-width:0;cursor:pointer}
.flow-mod-title{font-weight:600;font-size:.7rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.flow-mod-meta{font-size:.58rem;color:var(--dim)}
.flow-mod-arrows{display:flex;flex-direction:column;gap:1px;flex-shrink:0}
.flow-mod-arr{background:none;border:1px solid rgba(255,255,255,0.06);color:var(--dim);font-size:.5rem;line-height:1;padding:2px 4px;cursor:pointer;border-radius:3px;transition:all .12s}
.flow-mod-arr:hover{background:var(--teal);color:#fff;border-color:var(--teal)}
</style>
</head>
<body>

<header class="mgr-hdr">
  <span class="mgr-logo">Become Admin</span>
  <div class="mgr-tabs">
    <button class="mgr-tab active" data-panel="content">📚 Content</button>
    <button class="mgr-tab" data-panel="flow">🗺️ Progression</button>
    <button class="mgr-tab" data-panel="passoffs" id="passoff-tab">🎯 Pass-offs <span id="passoff-count" style="display:none;background:var(--red);color:#fff;border-radius:50%;font-size:.7rem;padding:1px 6px;margin-left:.25rem;animation:pulse 1.5s ease infinite"></span></button>
    <button class="mgr-tab" data-panel="users">👥 Users</button>
  </div>
  <div class="mgr-hdr-right">
    <span style="color:var(--dim);font-size:.85rem"><?= $userName ?></span>
    <a href="/become/">← Portal</a>
    <a href="/become/backup.php" style="color:var(--green)">💾 Backup</a>
    <a href="/become/logout.php">Log Out</a>
  </div>
</header>

<div class="mgr">

  <!-- CONTENT PANEL -->
  <div id="panel-content" class="panel active">
    <div class="sec-hdr">
      <h2>📚 Training Content</h2>
      <button class="btn btn-teal" data-action="addFolder">+ New Folder</button>
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
      <h2>🗺️ Progression</h2>
      <div style="display:flex;gap:.4rem">
        <button class="btn btn-ghost btn-sm" data-action="addModuleFromFlow">+ Module</button>
        <button class="btn btn-teal btn-sm" data-action="addLevel">+ Level</button>
      </div>
    </div>
    <!-- Quick nav -->
    <div id="flowNav" class="flow-nav"></div>
    <div id="flowBoard"></div>
  </div>

  <!-- PASS-OFFS PANEL -->
  <div id="panel-passoffs" class="panel">
    <div class="sec-hdr">
      <h2>🎯 Pending Pass-offs</h2>
      <button class="btn btn-ghost" data-action="loadPassoffs">↻ Refresh</button>
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
      <div style="display:flex;gap:.4rem">
        <button class="btn btn-ghost" data-action="syncZoho" id="zohoSyncBtn" title="Pull hires from Zoho Recruits and deactivate departures">⟲ Sync from Zoho</button>
        <button class="btn btn-teal" data-action="showAddUser">+ Add Rep</button>
      </div>
    </div>
    <div class="card" id="usersList"></div>
    <div class="card" id="addUserForm" style="display:none">
      <h3 style="margin-bottom:1rem;font-family:var(--hf)">New Team Member</h3>
      <div class="ed-row">
        <div class="ed-field"><label>Email <span style="color:var(--mute);font-weight:400">(used to log in)</span></label><input class="ed-input" id="nu-email" type="email" placeholder="rep@yourenergybest.com"></div>
        <div class="ed-field"><label>Password</label><input class="ed-input" id="nu-pass" type="password" placeholder="Temporary password"></div>
      </div>
      <div class="ed-row">
        <div class="ed-field"><label>First Name</label><input class="ed-input" id="nu-first"></div>
        <div class="ed-field"><label>Last Name</label><input class="ed-input" id="nu-last"></div>
      </div>
      <div class="ed-field"><label>Role</label>
        <select class="ed-input" id="nu-role" onchange="suggestStartLevel(this.value)"><option value="rep">Rep (Rookie)</option><option value="trainer">Trainer</option><option value="leader">Leader</option><option value="admin">Admin</option></select>
      </div>
      <div class="ed-row">
        <div class="ed-field"><label>Trainer <span style="color:var(--mute);font-weight:400">(who this rep reports to)</span></label>
          <select class="ed-input" id="nu-parent"><option value="">— No trainer —</option></select>
        </div>
        <div class="ed-field"><label>Engineer access</label>
          <label style="display:flex;align-items:center;gap:.5rem;padding:.6rem .2rem;cursor:pointer">
            <input type="checkbox" id="nu-full" style="width:18px;height:18px;accent-color:var(--orange)">
            <span style="font-size:.85rem;color:var(--dim)">⚡ Full access — unlock everything, skip all pass-offs</span>
          </label>
        </div>
      </div>
      <div class="ed-field"><label>Starting Level — Controls what content they can access immediately</label>
        <select class="ed-input" id="nu-level">
          <option value="0">Level 0 — Newbie 👶 (default for new reps)</option>
          <option value="1">Level 1 — Rookie 🌱</option>
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
        <button class="btn btn-green" data-action="createUser">Create</button>
        <button class="btn btn-ghost" data-action="cancelAddUser">Cancel</button>
      </div>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script data-cfasync="false" src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script data-cfasync="false">
const API = '/become/api/admin.php';
let data = { folders:[], modules:[], segments:[], users:[], thresholds:[] };
let selectedModId = null;
let quillEditor = null;
let quillAutoSaveTimer = null;
let currentEditSegId = null;

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
  // Normalize stage gaps on first load
  var lvlSet = {};
  data.modules.forEach(function(m) {
    var r = m.unlock_rule;
    var k = (r && r.kind === 'open') ? 'open' : (r && r.kind === 'level' ? r.value : 0);
    lvlSet[k] = true;
  });
  for (var k in lvlSet) { await normalizeStages(k); }
  renderTree();
  renderUsers();
  renderFlow();
  loadPassoffs();

  if (selectedModId) openModuleEditor(selectedModId);
}
const loadData = loadAll;

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
  // Scroll editor into view
  setTimeout(function() { area.scrollIntoView({behavior:'smooth', block:'start'}); }, 50);
  const curUnlock = mod.unlock_rule;
  const isOpen = curUnlock && curUnlock.kind === 'open';
  const curLvl = curUnlock && curUnlock.kind === 'level' ? curUnlock.value : (isOpen ? 'open' : 0);
  const levelOpts = `<option value="open" ${isOpen?'selected':''}>📚 Open to All (always accessible)</option>` +
    [0,1,2,3,4,5,6,7].map(l => `<option value="${l}" ${!isOpen && curLvl==l?'selected':''}>${l===0?'👶 Level 0 — Newbie (sequential)':'Level '+l+' — '+(data.thresholds.find(t=>t.level==l)||{title:'?'}).title}</option>`).join('');

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
        <select class="ed-input" onchange="setModuleUnlock(${modId}, this.value)">${levelOpts}</select>
      </div>
      <div class="ed-field"><label>🔗 Prerequisites — Which modules must be completed first?</label>
        <div id="prereq-list-${modId}" style="margin-bottom:.5rem">${buildPrereqList(modId, mod)}</div>
        <select class="ed-input" id="prereq-add-${modId}" onchange="addPrereq(${modId}, parseInt(this.value));this.value=''">
          <option value="">+ Add prerequisite...</option>
          ${data.modules.filter(m => m.id != modId).map(m => {
            const f = data.folders.find(f => f.id == m.folder_id);
            return `<option value="${m.id}">${m.icon||'📄'} ${esc(m.title)} (${f?esc(f.title):''})</option>`;
          }).join('')}
        </select>
        <p style="color:var(--mute);font-size:.72rem;margin-top:.3rem">If set, the rep must complete ALL listed modules before this one unlocks. Leave empty for sequential (previous module must be done).</p>
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
// ─── PREREQUISITES ───
function buildPrereqList(modId, mod) {
  const prereqs = mod.prerequisites || [];
  if (!prereqs.length) return '<span style="color:var(--mute);font-size:.82rem">None — uses sequential order</span>';
  return prereqs.map(pid => {
    const pm = data.modules.find(m => m.id == pid);
    if (!pm) return '';
    const pf = data.folders.find(f => f.id == pm.folder_id);
    return `<span style="display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .6rem;background:rgba(34,168,179,.08);border:1px solid rgba(34,168,179,.2);border-radius:8px;font-size:.8rem;margin:0 .3rem .3rem 0">
      ${pm.icon||'📄'} ${esc(pm.title)} <span style="color:var(--dim);font-size:.7rem">${pf?esc(pf.title):''}</span>
      <button style="background:none;border:none;color:var(--red);cursor:pointer;font-size:.9rem;padding:0 .2rem" onclick="removePrereq(${modId},${pid})">✕</button>
    </span>`;
  }).join('');
}

async function addPrereq(modId, prereqId) {
  if (!prereqId) return;
  const mod = data.modules.find(m => m.id == modId);
  if (!mod) return;
  const prereqs = mod.prerequisites || [];
  if (prereqs.includes(prereqId)) return;
  prereqs.push(prereqId);
  await api('POST', {action:'update_module', id:modId, prerequisites:prereqs});
  mod.prerequisites = prereqs;
  document.getElementById('prereq-list-'+modId).innerHTML = buildPrereqList(modId, mod);
  renderTree();
  renderFlow();
  toast('Prerequisite added');
}

async function removePrereq(modId, prereqId) {
  const mod = data.modules.find(m => m.id == modId);
  if (!mod) return;
  const prereqs = (mod.prerequisites || []).filter(p => p != prereqId);
  await api('POST', {action:'update_module', id:modId, prerequisites:prereqs.length ? prereqs : null});
  mod.prerequisites = prereqs.length ? prereqs : null;
  document.getElementById('prereq-list-'+modId).innerHTML = buildPrereqList(modId, mod);
  renderTree();
  renderFlow();
  toast('Prerequisite removed');
}

// ─── QUIZ BUILDER ───
function buildQuizEditor(segId, seg) {
  // Quiz data stored in content_html as JSON block after a separator
  let quiz = [];
  try {
    const qd = seg.customer_quote; // Reuse customer_quote field to store quiz JSON
    if (qd) quiz = JSON.parse(qd);
  } catch(e) {}
  if (!Array.isArray(quiz)) quiz = [];

  let html = `<div class="ed-field" style="background:rgba(255,183,3,0.05);border:1px solid rgba(255,183,3,0.15);border-radius:8px;padding:1rem;margin-bottom:1rem">
    <label style="font-size:.85rem;font-weight:700;color:var(--gold);display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem">📝 Quiz Questions <button class="btn btn-sm btn-gold" onclick="addQuizQuestion(${segId})">+ Add Question</button></label>`;

  quiz.forEach((q, qi) => {
    html += `<div style="background:var(--card);border:1px solid var(--bdr);border-radius:8px;padding:.75rem;margin-bottom:.5rem">
      <div style="display:flex;gap:.5rem;align-items:start;margin-bottom:.5rem">
        <span style="color:var(--gold);font-weight:700;font-size:.85rem">${qi+1}.</span>
        <input class="ed-input" value="${esc(q.question||'')}" placeholder="Question text" style="flex:1;font-size:.9rem"
          onchange="updateQuizQuestion(${segId},${qi},'question',this.value)">
        <button class="btn btn-sm btn-red" onclick="removeQuizQuestion(${segId},${qi})">✕</button>
      </div>
      <div style="padding-left:1.5rem">`;

    (q.options || []).forEach((opt, oi) => {
      const isCorrect = q.correct === oi;
      html += `<div style="display:flex;gap:.5rem;align-items:center;margin-bottom:.3rem">
        <input type="radio" name="q${segId}_${qi}" ${isCorrect?'checked':''} onchange="setCorrectAnswer(${segId},${qi},${oi})" style="accent-color:var(--green)">
        <input class="ed-input" value="${esc(opt)}" placeholder="Option ${oi+1}" style="flex:1;font-size:.85rem;padding:.4rem .6rem"
          onchange="updateQuizOption(${segId},${qi},${oi},this.value)">
        <button class="btn btn-sm btn-red" onclick="removeQuizOption(${segId},${qi},${oi})" style="padding:.2rem .4rem;font-size:.7rem">✕</button>
      </div>`;
    });

    html += `<button class="btn btn-sm btn-ghost" onclick="addQuizOption(${segId},${qi})" style="margin-top:.25rem;font-size:.75rem">+ Add Option</button>
      </div></div>`;
  });

  if (!quiz.length) {
    html += `<p style="color:var(--mute);font-size:.85rem;text-align:center;padding:1rem">No questions yet. Click "+ Add Question" to create a quiz.</p>`;
  }

  html += `<p style="color:var(--mute);font-size:.72rem;margin-top:.5rem">Select the radio button next to the correct answer. Reps must get all questions right to complete the segment.</p></div>`;
  return html;
}

function getQuizData(segId) {
  const seg = data.segments.find(s => s.id == segId);
  if (!seg) return [];
  try { return JSON.parse(seg.customer_quote || '[]'); } catch(e) { return []; }
}

function saveQuizData(segId, quiz) {
  const json = JSON.stringify(quiz);
  updateSegment(segId, {customer_quote: json});
  const seg = data.segments.find(s => s.id == segId);
  if (seg) seg.customer_quote = json;
}

function addQuizQuestion(segId) {
  const quiz = getQuizData(segId);
  quiz.push({question: '', options: ['', ''], correct: 0});
  saveQuizData(segId, quiz);
  setTimeout(() => openSegEditor(segId), 300);
}

function removeQuizQuestion(segId, qi) {
  const quiz = getQuizData(segId);
  quiz.splice(qi, 1);
  saveQuizData(segId, quiz);
  setTimeout(() => openSegEditor(segId), 300);
}

function updateQuizQuestion(segId, qi, field, value) {
  const quiz = getQuizData(segId);
  if (quiz[qi]) quiz[qi][field] = value;
  saveQuizData(segId, quiz);
}

function addQuizOption(segId, qi) {
  const quiz = getQuizData(segId);
  if (quiz[qi]) quiz[qi].options.push('');
  saveQuizData(segId, quiz);
  setTimeout(() => openSegEditor(segId), 300);
}

function removeQuizOption(segId, qi, oi) {
  const quiz = getQuizData(segId);
  if (quiz[qi]) {
    quiz[qi].options.splice(oi, 1);
    if (quiz[qi].correct >= quiz[qi].options.length) quiz[qi].correct = 0;
  }
  saveQuizData(segId, quiz);
  setTimeout(() => openSegEditor(segId), 300);
}

function updateQuizOption(segId, qi, oi, value) {
  const quiz = getQuizData(segId);
  if (quiz[qi] && quiz[qi].options) quiz[qi].options[oi] = value;
  saveQuizData(segId, quiz);
}

function setCorrectAnswer(segId, qi, oi) {
  const quiz = getQuizData(segId);
  if (quiz[qi]) quiz[qi].correct = oi;
  saveQuizData(segId, quiz);
}

function openSegEditor(segId) {
  // Cancel any pending auto-save from previous segment
  if (quillAutoSaveTimer) { clearTimeout(quillAutoSaveTimer); quillAutoSaveTimer = null; }
  currentEditSegId = segId;
  
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
        <select class="ed-input" id="seg-type-select-${segId}" onchange="updateSegment(${segId},{segment_type:this.value});setTimeout(()=>openSegEditor(${segId}),500)">
          <option value="lesson" ${(seg.segment_type||'lesson')==='lesson'?'selected':''}>📄 Lesson — Rep marks complete on their own</option>
          <option value="passoff" ${seg.segment_type==='passoff'?'selected':''}>🎯 Leader Pass-off — Rep must be approved by a leader</option>
          <option value="quiz" ${seg.segment_type==='quiz'?'selected':''}>📝 Quiz — Rep must answer questions correctly</option>
        </select>
      </div>
      ${seg.segment_type === 'quiz' ? buildQuizEditor(segId, seg) : ''}
      <div class="ed-field"><label>Content ${seg.segment_type === 'quiz' ? '(shown above the quiz)' : ''}</label>
        <div class="quill-wrap"><div id="quill-seg"></div></div>
        <div style="display:flex;gap:.5rem;align-items:center;margin-top:.5rem;flex-wrap:wrap">
          <button class="btn btn-teal" onclick="saveSegContent(${segId})">💾 Save Content</button>
          <button class="btn btn-ghost btn-sm" onclick="triggerFileUpload('image')">🖼️ Upload Image</button>
          <button class="btn btn-ghost btn-sm" onclick="insertPDF(${segId})">📎 Upload PDF</button>
          <button class="btn btn-ghost btn-sm" onclick="insertYouTube()">▶️ YouTube</button>
          <button class="btn btn-ghost btn-sm" onclick="triggerFileUpload('any')">📁 Upload File</button>
          <button class="btn btn-ghost btn-sm" onclick="insertDivider()">— Divider</button>
          <button class="btn btn-green btn-sm" onclick="manualSave()" style="padding:.3rem .8rem;font-weight:700">💾 Save Now</button>
          <span id="save-status" style="color:var(--green);font-size:.78rem;margin-left:auto"></span>
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
          'image': function() { triggerFileUpload('image'); }
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
  
  // Override video toolbar button to use our YouTube parser
  var toolbar = quillEditor.getModule('toolbar');
  toolbar.addHandler('video', insertYouTube);
  
  quillEditor.root.innerHTML = seg.content_html || '';

  // Auto-save after 2 seconds of inactivity
  const statusEl = document.getElementById('save-status');
  quillEditor.on('text-change', function() {
    if (statusEl) statusEl.textContent = '● Unsaved changes';
    if (statusEl) statusEl.style.color = 'var(--gold)';
    if (quillAutoSaveTimer) clearTimeout(quillAutoSaveTimer);
    const saveSegId = currentEditSegId; // capture current ID at time of change
    quillAutoSaveTimer = setTimeout(async () => {
      if (!quillEditor || currentEditSegId !== saveSegId) return; // segment changed, don't save
      const html = quillEditor.root.innerHTML;
      const content = html === '<p><br></p>' ? '' : html;
      try {
        await api('POST', {action:'update_segment', id: saveSegId, content_html: content});
        const s = data.segments.find(x => x.id == saveSegId);
        if (s) s.content_html = content;
        if (statusEl && currentEditSegId === saveSegId) { statusEl.textContent = '✓ Auto-saved'; statusEl.style.color = 'var(--green)'; }
      } catch(e) {
        if (statusEl && currentEditSegId === saveSegId) { statusEl.textContent = '✕ Save failed'; statusEl.style.color = 'var(--red)'; }
      }
    }, 2000);
  });

  // Enable drag-drop and paste uploads
  setupEditorDragDrop();
}

function closeSegEditor() {
  if (quillAutoSaveTimer) { clearTimeout(quillAutoSaveTimer); quillAutoSaveTimer = null; }
  currentEditSegId = null;
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
  triggerFileUpload('pdf');
}

function insertDivider() {
  if (!quillEditor) return;
  const range = quillEditor.getSelection(true);
  quillEditor.insertText(range.index, '\n───────────────────\n');
  quillEditor.setSelection(range.index + 22);
}

async function manualSave() {
  if (!quillEditor || !currentEditSegId) { toast('No segment open', true); return; }
  const html = quillEditor.root.innerHTML;
  const content = html === '<p><br></p>' ? '' : html;
  const statusEl = document.getElementById('save-status');
  try {
    if (statusEl) { statusEl.textContent = '⏳ Saving...'; statusEl.style.color = 'var(--gold)'; }
    await api('POST', {action:'update_segment', id: currentEditSegId, content_html: content});
    const s = data.segments.find(x => x.id == currentEditSegId);
    if (s) s.content_html = content;
    if (statusEl) { statusEl.textContent = '✓ Saved!'; statusEl.style.color = 'var(--green)'; }
    toast('Saved');
  } catch(e) {
    if (statusEl) { statusEl.textContent = '✕ Failed'; statusEl.style.color = 'var(--red)'; }
  }
}

function insertYouTube() {
  if (!quillEditor) return;
  const url = prompt('Paste a YouTube URL (videos or Shorts):\n\nAll formats work:\nhttps://youtube.com/watch?v=...\nhttps://youtu.be/...\nhttps://youtube.com/shorts/...\nOr just the video ID');
  if (!url) return;

  // Extract video ID from various YouTube URL formats
  let videoId = url.trim();
  const patterns = [
    /(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/,
    /^([a-zA-Z0-9_-]{11})$/
  ];
  for (const p of patterns) {
    const m = videoId.match(p);
    if (m) { videoId = m[1]; break; }
  }

  if (!videoId || videoId.length !== 11) {
    toast('Could not find a valid YouTube video ID', true);
    return;
  }

  const embedUrl = 'https://www.youtube.com/embed/' + videoId;
  const range = quillEditor.getSelection(true);
  quillEditor.insertEmbed(range.index, 'video', embedUrl);
  quillEditor.setSelection(range.index + 1);
  toast('YouTube video embedded');
}

// ─── FILE UPLOAD SYSTEM ───
function triggerFileUpload(type) {
  const input = document.createElement('input');
  input.type = 'file';
  if (type === 'image') input.accept = 'image/*';
  else if (type === 'pdf') input.accept = '.pdf,application/pdf';
  else input.accept = 'image/*,.pdf,application/pdf,video/mp4,video/webm';
  
  input.onchange = async () => {
    if (!input.files[0]) return;
    await uploadAndInsert(input.files[0]);
  };
  input.click();
}

async function uploadAndInsert(file) {
  if (!quillEditor) return;
  const statusEl = document.getElementById('save-status');
  if (statusEl) { statusEl.textContent = '⏳ Uploading ' + file.name + '...'; statusEl.style.color = 'var(--gold)'; }

  const formData = new FormData();
  formData.append('file', file);

  try {
    const res = await fetch('/become/api/upload.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (!data.success) {
      toast(data.error || 'Upload failed', true);
      if (statusEl) { statusEl.textContent = '✕ Upload failed'; statusEl.style.color = 'var(--red)'; }
      return;
    }

    const range = quillEditor.getSelection(true);

    if (data.type === 'image') {
      quillEditor.insertEmbed(range.index, 'image', data.url);
      quillEditor.setSelection(range.index + 1);
    } else if (data.type === 'pdf') {
      quillEditor.insertText(range.index, '\n');
      quillEditor.insertText(range.index + 1, '📄 ' + (data.filename || 'Download PDF'), { link: data.url });
      quillEditor.insertText(range.index + 1 + data.filename.length + 3, '\n');
    } else if (data.type === 'video') {
      quillEditor.insertEmbed(range.index, 'video', data.url);
      quillEditor.setSelection(range.index + 1);
    }

    if (statusEl) { statusEl.textContent = '✓ Uploaded'; statusEl.style.color = 'var(--green)'; }
    toast('File uploaded');
  } catch(e) {
    toast('Upload error: ' + e.message, true);
    if (statusEl) { statusEl.textContent = '✕ Upload failed'; statusEl.style.color = 'var(--red)'; }
  }
}

// Drag-and-drop onto Quill editor
function setupEditorDragDrop() {
  if (!quillEditor) return;
  const editorEl = quillEditor.root;

  editorEl.addEventListener('dragover', (e) => {
    e.preventDefault();
    editorEl.style.outline = '2px dashed var(--teal)';
    editorEl.style.outlineOffset = '-4px';
  });

  editorEl.addEventListener('dragleave', () => {
    editorEl.style.outline = '';
    editorEl.style.outlineOffset = '';
  });

  editorEl.addEventListener('drop', async (e) => {
    e.preventDefault();
    editorEl.style.outline = '';
    editorEl.style.outlineOffset = '';
    const files = e.dataTransfer.files;
    if (files.length > 0) {
      for (let i = 0; i < files.length; i++) {
        await uploadAndInsert(files[i]);
      }
    }
  });

  // Also handle paste (screenshots)
  editorEl.addEventListener('paste', async (e) => {
    const items = e.clipboardData?.items;
    if (!items) return;
    for (let i = 0; i < items.length; i++) {
      if (items[i].type.startsWith('image/')) {
        e.preventDefault();
        const file = items[i].getAsFile();
        if (file) await uploadAndInsert(file);
        return;
      }
    }
  });
}

// ─── PROGRESSION FLOW BOARD ───
function renderFlow() {
  const board = document.getElementById('flowBoard');
  const nav = document.getElementById('flowNav');
  if (!board) return;

  const openMods = [];
  const groups = {};
  data.modules.forEach(m => {
    const rule = m.unlock_rule;
    if (rule && rule.kind === 'open') { openMods.push(m); }
    else {
      const lvl = rule && rule.kind === 'level' ? rule.value : 0;
      if (!groups[lvl]) groups[lvl] = [];
      groups[lvl].push(m);
    }
  });
  openMods.sort((a,b) => (a.module_order||0) - (b.module_order||0));
  Object.values(groups).forEach(arr => arr.sort((a,b) => (a.module_order||0) - (b.module_order||0)));

  const thresholds = data.thresholds || [];
  const usedLevels = Object.keys(groups).map(Number);
  const maxLvl = Math.max(3, ...usedLevels);
  const colors = ['var(--teal)','var(--green)','var(--gold)','var(--orange)','var(--teal)','var(--green)'];

  // Build quick nav
  let navHtml = '<button class="flow-nav-btn" data-scroll-flow="open">📚 Open</button>';
  navHtml += '<button class="flow-nav-btn" data-scroll-flow="0">L0</button>';
  for (let lvl = 1; lvl <= maxLvl; lvl++) {
    const th = thresholds.find(t => t.level == lvl);
    const n = (groups[lvl]||[]).length;
    navHtml += '<button class="flow-nav-btn" data-scroll-flow="'+lvl+'">'+(th?th.badge_icon:'')+' L'+lvl+(n?' ('+n+')':'')+'</button>';
  }
  if (nav) nav.innerHTML = navHtml;

  let html = '';
  html += levelSection('open', '📚 Open to All', openMods.length+' modules', 'var(--dim)', openMods);
  const th0 = thresholds.find(t => t.level == 0);
  html += levelSection(0, (th0?th0.badge_icon:'👶')+' L0 — '+(th0?th0.title:'Newbie'), (groups[0]||[]).length+' modules', 'var(--teal)', groups[0]||[]);
  for (let lvl = 1; lvl <= maxLvl; lvl++) {
    const th = thresholds.find(t => t.level == lvl);
    const mods = groups[lvl] || [];
    if (!mods.length && lvl > Math.max(3, ...usedLevels)) continue;
    const color = colors[lvl % colors.length];
    const title = th ? th.badge_icon+' L'+lvl+' — '+th.title : 'Level '+lvl;
    const sub = mods.length+' modules'+(th?' · '+th.xp_required+' XP':'');
    html += levelSection(lvl, title, sub, color, mods);
  }
  board.innerHTML = html;
}

function levelSection(lvl, title, subtitle, color, mods) {
  const isOpen = lvl === 'open';
  const expanded = mods.length > 0 || lvl === 0 || lvl === 1 || isOpen ? ' flow-open' : '';
  const dropLvl = isOpen ? 'open' : lvl;
  const totalSegs = data.segments.filter(s => mods.some(m => m.id == s.module_id)).length;

  // Group mods into stages by module_order
  const stages = {};
  mods.forEach(m => {
    const stage = m.module_order || 1;
    if (!stages[stage]) stages[stage] = [];
    stages[stage].push(m);
  });
  const stageNums = Object.keys(stages).map(Number).sort((a,b) => a-b);

  let bodyHtml = '';
  stageNums.forEach((sn, si) => {
    const row = stages[sn];
    if (si > 0) bodyHtml += '<div class="flow-arrow-down">↓</div>';
    bodyHtml += '<div class="flow-stage" data-stage="'+sn+'" data-level="'+dropLvl+'">';
    bodyHtml += '<div class="flow-stage-hdr"><span class="flow-stage-num">Stage '+sn+'</span>';
    if (row.length > 1) bodyHtml += '<span class="flow-stage-hint">Complete all</span>';
    bodyHtml += '</div>';
    bodyHtml += '<div class="flow-stage-cards">';
    row.forEach(m => { bodyHtml += modCard(m); });
    bodyHtml += '</div></div>';
  });

  return '<div class="flow-section'+expanded+'" data-flow-lvl="'+dropLvl+'">' +
    '<div class="flow-hdr" data-toggle="flow-section">' +
      '<span class="flow-arrow">▸</span>' +
      '<div style="flex:1"><div style="font-weight:700;font-size:1rem;color:'+color+'">'+title+'</div>' +
      '<div style="font-size:.75rem;color:var(--dim)">'+subtitle+'</div></div>' +
      '<div style="display:flex;align-items:center;gap:.5rem">' +
        '<span style="font-size:.8rem;color:var(--dim);font-weight:600">'+mods.length+' mod'+(mods.length!==1?'s':'')+'</span>' +
        (totalSegs ? '<span style="font-size:.68rem;padding:2px 7px;border-radius:10px;background:rgba(255,255,255,0.06);color:var(--dim)">'+totalSegs+' segs</span>' : '') +
        '<button class="btn btn-sm btn-ghost" data-add-stage="'+dropLvl+'" style="font-size:.7rem;padding:.15rem .4rem">+ Stage</button>' +
      '</div>' +
    '</div>' +
    '<div class="flow-body">' +
      '<div class="flow-level-drop" data-drop-level="'+dropLvl+'">' +
        (bodyHtml || '<div class="flow-empty">Drag modules here or click + Stage</div>') +
      '</div>' +
    '</div></div>';
}

function modCard(m) {
  const folder = data.folders.find(f => f.id == m.folder_id);
  const ficon = folder ? (folder.icon||'📁') : '';
  const fname = folder ? folder.title : '';
  const segs = data.segments.filter(s => s.module_id == m.id).length;
  const stg = m.module_order || 1;
  return '<div class="flow-mod" draggable="true" data-mod-id="'+m.id+'" data-mod-stage="'+stg+'">' +
    '<div class="flow-mod-arrows">' +
      '<button class="flow-mod-arr" data-move-mod="'+m.id+'" data-move-dir="up" title="Move to earlier stage">▲</button>' +
      '<button class="flow-mod-arr" data-move-mod="'+m.id+'" data-move-dir="down" title="Move to later stage">▼</button>' +
    '</div>' +
    '<div class="flow-mod-info" data-edit-mod="'+m.id+'">' +
      '<div class="flow-mod-title">'+(m.icon||'📄')+' '+esc(m.title)+'</div>' +
      '<div class="flow-mod-meta">'+ficon+' '+esc(fname)+' · S'+stg+' · '+segs+' seg'+(segs!==1?'s':'')+'</div>' +
    '</div>' +
  '</div>';
}

// Normalize stages: renumber so they're always 1, 2, 3... with no gaps
async function normalizeStages(level) {
  var levelMods = data.modules.filter(function(m) {
    var r = m.unlock_rule;
    if (level === 'open') return r && r.kind === 'open';
    var ml = (r && r.kind === 'level') ? r.value : ((r && r.kind === 'open') ? -99 : 0);
    return ml == parseInt(level);
  });
  
  // Get unique stage numbers, sorted
  var stageNums = [];
  levelMods.forEach(function(m) {
    var s = m.module_order || 1;
    if (stageNums.indexOf(s) === -1) stageNums.push(s);
  });
  stageNums.sort(function(a,b) { return a - b; });
  
  // Check if already normalized (1, 2, 3...)
  var needsNorm = false;
  for (var i = 0; i < stageNums.length; i++) {
    if (stageNums[i] !== i + 1) { needsNorm = true; break; }
  }
  if (!needsNorm) return;
  
  // Build renumber map: oldStage -> newStage
  var remap = {};
  stageNums.forEach(function(old, idx) { remap[old] = idx + 1; });
  
  // Update each module
  var updates = [];
  levelMods.forEach(function(m) {
    var oldStage = m.module_order || 1;
    var newStage = remap[oldStage];
    if (newStage !== oldStage) {
      updates.push(api('POST', {action:'update_module', id:m.id, module_order:newStage}));
      m.module_order = newStage;
    }
  });
  
  if (updates.length) await Promise.all(updates);
}

// ─── Drag & Drop — fully delegated ───
let dragModId = null;

async function moveModStage(modId, dir) {
  const mod = data.modules.find(m => m.id == modId);
  if (!mod) return;
  const curStage = mod.module_order || 1;
  let newStage;
  if (dir === 'up') {
    newStage = Math.max(1, curStage - 1);
  } else {
    newStage = curStage + 1;
  }
  if (newStage === curStage) return;
  await api('POST', {action:'update_module', id:modId, module_order:newStage});
  mod.module_order = newStage;
  // Get the level to normalize
  var rule = mod.unlock_rule;
  var lvl = rule && rule.kind === 'open' ? 'open' : (rule && rule.kind === 'level' ? rule.value : 0);
  await normalizeStages(lvl);
  renderFlow();
  toast((dir==='up'?'↑':'↓') + ' Stage ' + newStage);
}

// Drag start/end via delegation
document.addEventListener('dragstart', function(e) {
  const mod = e.target.closest('.flow-mod');
  if (!mod) return;
  dragModId = parseInt(mod.dataset.modId);
  e.dataTransfer.effectAllowed = 'move';
  setTimeout(function() { mod.classList.add('dragging'); }, 0);
});

document.addEventListener('dragend', function(e) {
  document.querySelectorAll('.dragging,.stage-drop-over,.level-drop-over').forEach(function(el) {
    el.classList.remove('dragging','stage-drop-over','level-drop-over');
  });
  dragModId = null;
});

// Dragover — highlight the closest stage or level
document.addEventListener('dragover', function(e) {
  if (!dragModId) return;
  const stage = e.target.closest('.flow-stage');
  const levelDrop = e.target.closest('.flow-level-drop');
  if (stage || levelDrop) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
  }
  // Clear all highlights
  document.querySelectorAll('.stage-drop-over,.level-drop-over').forEach(function(el) {
    el.classList.remove('stage-drop-over','level-drop-over');
  });
  // Highlight target
  if (stage) {
    stage.classList.add('stage-drop-over');
  } else if (levelDrop) {
    levelDrop.classList.add('level-drop-over');
  }
});

// Drop handler
document.addEventListener('drop', async function(e) {
  if (!dragModId) return;
  const stage = e.target.closest('.flow-stage');
  const levelDrop = e.target.closest('.flow-level-drop');
  if (!stage && !levelDrop) return;
  e.preventDefault();
  e.stopPropagation();

  document.querySelectorAll('.stage-drop-over,.level-drop-over').forEach(function(el) {
    el.classList.remove('stage-drop-over','level-drop-over');
  });

  const mod = data.modules.find(function(m) { return m.id == dragModId; });
  if (!mod) { dragModId = null; return; }

  if (stage) {
    // Dropped on a specific stage — move to that stage
    var stageNum = parseInt(stage.dataset.stage);
    var level = stage.dataset.level;
    var unlock;
    if (level === 'open') unlock = {kind:'open'};
    else if (parseInt(level) > 0) unlock = {kind:'level', value:parseInt(level)};
    else unlock = null;
    await api('POST', {action:'update_module', id:dragModId, unlock_rule:unlock, module_order:stageNum});
    mod.unlock_rule = unlock;
    mod.module_order = stageNum;
    toast('→ Stage ' + stageNum);
    await normalizeStages(level);
  } else if (levelDrop) {
    // Dropped on level area (not on a stage) — new stage at end
    var level = levelDrop.dataset.dropLevel;
    var unlock;
    if (level === 'open') unlock = {kind:'open'};
    else if (parseInt(level) > 0) unlock = {kind:'level', value:parseInt(level)};
    else unlock = null;
    var levelMods = data.modules.filter(function(m) {
      var r = m.unlock_rule;
      if (level === 'open') return r && r.kind === 'open';
      return (r && r.kind === 'level' ? r.value : (r ? -1 : 0)) == parseInt(level);
    });
    var maxStage = levelMods.length ? Math.max.apply(null, levelMods.map(function(m) { return m.module_order || 1; })) : 0;
    await api('POST', {action:'update_module', id:dragModId, unlock_rule:unlock, module_order:maxStage+1});
    mod.unlock_rule = unlock;
    mod.module_order = maxStage + 1;
    toast('→ New stage ' + (maxStage+1));
    await normalizeStages(level);
  }

  dragModId = null;
  renderFlow();
  renderTree();
});

async function addModuleFromFlow() {
  const title = prompt('Module title:');
  if (!title) return;
  if (!data.folders.length) { toast('Create a folder first in Content tab'); return; }
  const names = data.folders.map((f,i) => (i+1) + '. ' + (f.icon||'📁') + ' ' + f.title).join('\n');
  const pick = prompt('Which folder?\n' + names);
  let folderId = data.folders[0].id;
  if (pick) {
    const idx = parseInt(pick) - 1;
    if (data.folders[idx]) folderId = data.folders[idx].id;
  }
  await api('POST', {action:'add_module', folder_id:folderId, title:title});
  await loadData();
  renderTree(); renderFlow();
  toast('Module created');
}

async function addNewLevel() {
  const th = data.thresholds || [];
  const maxLvl = th.length ? Math.max(...th.map(t => t.level)) : 0;
  const newLvl = maxLvl + 1;
  const title = prompt('Level ' + newLvl + ' title (e.g. "Expert"):');
  if (!title) return;
  const prevXp = th.length ? Math.max(...th.map(t => parseInt(t.xp_required))) : 0;
  const suggestedXp = prevXp + 1000;
  const xpStr = prompt('XP required to reach Level ' + newLvl + ':', suggestedXp);
  if (!xpStr) return;
  const xp = parseInt(xpStr);
  const icon = prompt('Badge emoji (e.g. 🏆):', '⭐') || '⭐';

  try {
    const res = await fetch('/become/api/admin.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({action:'add_level', level:newLvl, title:title, xp_required:xp, badge_icon:icon})
    });
    const json = await res.json();
    if (json.error) throw new Error(json.error);
    await loadData();
    renderFlow();
    toast('Level ' + newLvl + ' created!');
  } catch(e) { toast(e.message, true); }
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
      <select class="ed-input" style="width:auto;font-size:.8rem;padding:.3rem .5rem" onchange="setModuleUnlock(${m.id}, this.value)">${levelOpts}</select>
    </div>`;
  }).join('');
}

async function setModuleUnlock(modId, level) {
  let unlock;
  if (level === 'open') unlock = {kind:'open'};
  else if (parseInt(level) > 0) unlock = {kind:'level', value:parseInt(level)};
  else unlock = null;
  await api('POST', {action:'update_module', id:modId, unlock_rule: unlock});
  const m = data.modules.find(x => x.id == modId);
  if (m) m.unlock_rule = unlock;
  renderTree();
  renderFlow();
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
function trainerOptions(selId) {
  const mgrs = (data.users || []).filter(u => ['trainer','leader','admin'].includes(u.role));
  const label = u => (u.first_name || u.last_name) ? `${u.first_name||''} ${u.last_name||''}`.trim() : (u.email || u.username);
  return '<option value="">— No trainer —</option>' +
    mgrs.map(u => `<option value="${u.id}" ${String(selId)===String(u.id)?'selected':''}>${esc(label(u))}</option>`).join('');
}
function populateTrainerSelect() {
  const s = document.getElementById('nu-parent');
  if (s) s.innerHTML = trainerOptions('');
}

function renderUsers() {
  const el = document.getElementById('usersList');
  populateTrainerSelect();
  if (!data.users || data.users.length === 0) {
    el.innerHTML = '<div style="padding:2rem;text-align:center;color:var(--mute)">No users yet</div>';
    return;
  }
  el.innerHTML = data.users.map(u => {
    const initials = ((u.first_name||'')[0]||'') + ((u.last_name||'')[0]||'') || (u.username||'?')[0].toUpperCase();
    const roleCls = u.role === 'admin' ? 'admin' : u.role === 'leader' ? 'leader' : 'rep';
    const full = Number(u.full_access) === 1;
    return `<div class="user-row">
      <div class="user-avatar">${esc(initials)}</div>
      <div class="user-name">${esc(u.first_name||'')} ${esc(u.last_name||'')}<small>${esc(u.email||u.username||'')}</small></div>
      <span class="user-role user-role--${roleCls}">${u.role}</span>
      ${full ? '<span class="user-role" style="background:rgba(251,155,71,.15);color:var(--orange)">⚡ Engineer</span>' : ''}
      <select class="ed-input" style="width:auto;font-size:.8rem;padding:.3rem .5rem" onchange="updateUser(${u.id},{role:this.value})" ${u.role==='admin'?'disabled':''}>
        <option value="rep" ${u.role==='rep'?'selected':''}>Rep</option>
        <option value="trainer" ${u.role==='trainer'?'selected':''}>Trainer</option>
        <option value="leader" ${u.role==='leader'?'selected':''}>Leader</option>
        <option value="admin" ${u.role==='admin'?'selected':''}>Admin</option>
      </select>
      <select class="ed-input" style="width:auto;font-size:.8rem;padding:.3rem .5rem" onchange="setUserLevel(${u.id},parseInt(this.value))" title="Set access level">
        ${[0,1,2,3,4,5,6,7].map(l => `<option value="${l}">Lvl ${l}</option>`).join('')}
      </select>
      <select class="ed-input" style="width:auto;font-size:.8rem;padding:.3rem .5rem" onchange="updateUser(${u.id},{parent_id:this.value})" title="Reports to (trainer)">
        ${trainerOptions(u.parent_id)}
      </select>
      <label style="display:flex;align-items:center;gap:.3rem;font-size:.72rem;color:var(--dim);cursor:pointer" title="Engineer = full access">
        <input type="checkbox" ${full?'checked':''} style="accent-color:var(--orange)" onchange="updateUser(${u.id},{full_access:this.checked?1:0})">⚡
      </label>
      <div style="display:flex;gap:.25rem">
        <button class="btn btn-sm btn-ghost" onclick="editEmail(${u.id},'${esc(u.email||'')}')" title="Edit login email">✉</button>
        <button class="btn btn-sm btn-ghost" onclick="resetPw(${u.id},'${esc(u.email||u.username)}')">🔑</button>
        ${u.role!=='admin'?`<button class="btn btn-sm btn-red" onclick="deleteUser(${u.id},'${esc(u.email||u.username)}')">✕</button>`:''}
      </div>
    </div>`;
  }).join('');
}

function showAddUser() { document.getElementById('addUserForm').style.display = 'block'; }

async function syncZoho() {
  const btn = document.getElementById('zohoSyncBtn');
  if (btn) { btn.disabled = true; btn.textContent = '⟲ Syncing…'; }
  try {
    const r = await api('POST', {action:'zoho_sync'});
    if (r && r.ok) {
      toast(`Zoho: ${r.created} created · ${r.reactivated} reactivated · ${r.deactivated} deactivated`);
      if (r.errors && r.errors.length) { console.warn('Zoho sync errors:', r.errors); toast(r.errors.length + ' record issue(s): ' + r.errors[0], true); }
      await loadAll();
    } else {
      toast((r && r.error) ? r.error : 'Zoho sync failed', true);
    }
  } catch (e) { toast('Zoho: ' + (e && e.message ? e.message : 'sync failed'), true); }
  if (btn) { btn.disabled = false; btn.textContent = '⟲ Sync from Zoho'; }
}

async function createUser() {
  const startLevel = parseInt(document.getElementById('nu-level').value) || 0;
  const d = {
    action:'add_user',
    email: document.getElementById('nu-email').value.trim(),
    password: document.getElementById('nu-pass').value,
    first_name: document.getElementById('nu-first').value.trim(),
    last_name: document.getElementById('nu-last').value.trim(),
    role: document.getElementById('nu-role').value,
    parent_id: document.getElementById('nu-parent').value,
    full_access: document.getElementById('nu-full').checked ? 1 : 0
  };
  if (!d.email || !d.password) return toast('Email and password required', true);
  const result = await api('POST', d);
  if (result && result.error) return toast(result.error, true);

  // Set starting level if higher than 1
  if (startLevel > 1 && result.id) {
    const th = data.thresholds.find(t => t.level == startLevel);
    const xp = th ? th.xp_required : 0;
    await fetch(API, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({action:'set_user_level', user_id: result.id, level: startLevel, xp: xp})
    });
  }

  document.getElementById('addUserForm').style.display = 'none';
  ['nu-email','nu-pass','nu-first','nu-last'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('nu-level').value = '0';
  document.getElementById('nu-parent').value = '';
  document.getElementById('nu-full').checked = false;
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
// dragModId already declared in flow board section

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


// ─── STARTING LEVEL: Auto-suggest based on role ───
function suggestStartLevel(role) {
  const levelSelect = document.getElementById('nu-level');
  if (!levelSelect) return;
  const suggestions = { rep: '0', trainer: '3', leader: '4', admin: '7' };
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

    if (el) el.innerHTML = items.map(p => {
      const initials = `${esc((p.first_name||'')[0]||'')}${esc((p.last_name||'')[0]||'')}`;
      const isLevel = p.kind === 'level';
      const detail = isLevel
        ? `<span style="color:var(--teal);font-weight:700">🎓 Level ${p.level} pass-off</span> — ready to advance to Level ${(parseInt(p.level)||0)+1}`
        : `Segment: <strong style="color:var(--gold)">${esc(p.segment_title||'')}</strong>`;
      const actions = isLevel
        ? `<button class="btn btn-green" onclick="approveLevel(${p.id},this)" style="padding:.6rem 1.2rem">✓ Approve</button>
           <button class="btn btn-ghost" onclick="rejectLevel(${p.id},this)" style="padding:.6rem .9rem">Return</button>`
        : `<button class="btn btn-green" onclick="approvePassoff(${p.id},this)" style="font-size:1.05rem;padding:.7rem 1.8rem;box-shadow:0 0 20px rgba(6,214,160,0.3)">✓ Pass</button>`;
      return `
      <div style="display:flex;align-items:center;gap:1rem;padding:1rem;border-bottom:1px solid rgba(255,255,255,0.04)">
        <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,${isLevel?'var(--teal),#1a8a93':'var(--gold),var(--orange)'});display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0">${initials}</div>
        <div style="flex:1">
          <div style="font-weight:700;font-size:.95rem">${esc(p.first_name||'')} ${esc(p.last_name||'')} <span style="color:var(--dim);font-weight:400;font-size:.85rem">${esc(p.email||p.username||'')}</span></div>
          <div style="font-size:.88rem;color:var(--dim);margin-top:.15rem">${detail}</div>
          <div style="font-size:.75rem;color:var(--mute);margin-top:.15rem">Requested ${timeAgo(p.requested_at)}</div>
        </div>
        <div style="display:flex;gap:.4rem;align-items:center">${actions}</div>
      </div>`;
    }).join('');
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
      btn.parentElement.parentElement.style.opacity = '.3';
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

async function approveLevel(requestId, btn) {
  btn.disabled = true; btn.textContent = '⏳...';
  try {
    const res = await fetch('/become/api/index.php?route=level-passoff/' + requestId + '/approve', {
      method:'POST', headers:{'Content-Type':'application/json'}
    });
    const data = await res.json();
    if (data.success) {
      const row = btn.closest('div[style*="border-bottom"]');
      if (row) row.style.opacity = '.3';
      toast('Level pass-off approved — rep advanced to Level ' + data.new_level + '.');
      setTimeout(() => loadPassoffs(), 1200);
    } else {
      btn.textContent = '✕'; btn.disabled = false;
      toast(data.error || 'Approval failed', true);
    }
  } catch(e) { btn.textContent = '✕'; btn.disabled = false; }
}

async function rejectLevel(requestId, btn) {
  const notes = prompt('Optional note to the rep about why this pass-off is being returned:') || '';
  btn.disabled = true; btn.textContent = '⏳...';
  try {
    const res = await fetch('/become/api/index.php?route=level-passoff/' + requestId + '/reject', {
      method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({notes})
    });
    const data = await res.json();
    if (data.success) { toast('Pass-off returned to rep.'); setTimeout(() => loadPassoffs(), 1000); }
    else { btn.textContent = 'Return'; btn.disabled = false; toast(data.error || 'Failed', true); }
  } catch(e) { btn.textContent = 'Return'; btn.disabled = false; }
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

// ─── INIT — All event delegation (Cloudflare-safe, no onclick) ───
document.addEventListener('click', function(e) {
  // Tab switching
  var tab = e.target.closest('[data-panel]');
  if (tab) {
    var name = tab.dataset.panel;
    document.querySelectorAll('.panel').forEach(function(p) { p.classList.remove('active'); });
    document.querySelectorAll('.mgr-tab').forEach(function(t) { t.classList.remove('active'); });
    document.getElementById('panel-' + name).classList.add('active');
    tab.classList.add('active');
    return;
  }

  // Button actions
  var actionBtn = e.target.closest('[data-action]');
  if (actionBtn) {
    var action = actionBtn.dataset.action;
    if (action === 'addFolder') addFolder();
    else if (action === 'addModuleFromFlow') addModuleFromFlow();
    else if (action === 'loadPassoffs') loadPassoffs();
    else if (action === 'showAddUser') showAddUser();
    else if (action === 'createUser') createUser();
    else if (action === 'cancelAddUser') document.getElementById('addUserForm').style.display = 'none';
    else if (action === 'addLevel') addNewLevel();
    else if (action === 'syncZoho') syncZoho();
    return;
  }

  // Flow nav — scroll to level
  var navBtn = e.target.closest('[data-scroll-flow]');
  if (navBtn) {
    var lvl = navBtn.dataset.scrollFlow;
    var target = document.querySelector('[data-flow-lvl="'+lvl+'"]');
    if (target) {
      // Expand it
      target.classList.add('flow-open');
      target.scrollIntoView({behavior:'smooth',block:'start'});
      // Highlight nav btn
      document.querySelectorAll('.flow-nav-btn').forEach(function(b) { b.classList.remove('active'); });
      navBtn.classList.add('active');
    }
    return;
  }

  // Move module between stages (up/down arrows)
  var moveBtn = e.target.closest('[data-move-mod]');
  if (moveBtn) {
    e.stopPropagation();
    var modId = parseInt(moveBtn.dataset.moveMod);
    var dir = moveBtn.dataset.moveDir;
    moveModStage(modId, dir);
    return;
  }

  // Flow section toggle
  var flowHdr = e.target.closest('[data-toggle="flow-section"]');
  if (flowHdr) {
    flowHdr.parentElement.classList.toggle('flow-open');
    return;
  }

  // Add stage button
  var addStageBtn = e.target.closest('[data-add-stage]');
  if (addStageBtn) {
    e.stopPropagation();
    var lvl = addStageBtn.dataset.addStage;
    var levelMods = data.modules.filter(function(m) {
      var r = m.unlock_rule;
      if (lvl === 'open') return r && r.kind === 'open';
      return (r && r.kind === 'level' ? r.value : (r ? -1 : 0)) == parseInt(lvl);
    });
    var maxStage = levelMods.length ? Math.max.apply(null, levelMods.map(function(m) { return m.module_order || 1; })) : 0;
    var newStage = maxStage + 1;
    var title = prompt('Module title for new Stage ' + newStage + ':');
    if (!title) return;
    if (!data.folders.length) { toast('Create a folder first'); return; }
    var folderId = data.folders[0].id;
    var unlock = lvl === 'open' ? {kind:'open'} : (parseInt(lvl) > 0 ? {kind:'level', value:parseInt(lvl)} : null);
    api('POST', {action:'add_module', folder_id:folderId, title:title}).then(function(res) {
      if (res && res.id) {
        api('POST', {action:'update_module', id:res.id, unlock_rule:unlock, module_order:newStage}).then(function() {
          loadAll();
          toast('Stage ' + newStage + ' added');
        });
      }
    });
    return;
  }

  // Flow card click-to-edit
  var editDiv = e.target.closest('[data-edit-mod]');
  if (editDiv) {
    var modId = parseInt(editDiv.dataset.editMod);
    document.querySelectorAll('.panel').forEach(function(p) { p.classList.remove('active'); });
    document.querySelectorAll('.mgr-tab').forEach(function(t) { t.classList.remove('active'); });
    document.getElementById('panel-content').classList.add('active');
    document.querySelector('.mgr-tab').classList.add('active');
    openModuleEditor(modId);
    return;
  }
});

loadAll();
</script>
</body>
</html>
