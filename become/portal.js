/* =============================================
   portal.js — Training Portal Interactivity
   Location: public_html/become/portal.js
   ============================================= */

// ── FOLDER TOGGLE ──
function toggleFolder(hdr) {
    var f = hdr.closest('.folder');
    if (f) f.classList.toggle('folder--open');
}

// ── SEGMENT COMPLETION ──
async function completeSeg(segId, btn) {
    btn.disabled = true;
    btn.textContent = '⏳ Saving...';
    try {
        var res = await fetch('/become/api/index.php?route=segments/' + segId + '/complete', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }
        });
        var data = await res.json();

        if (data.already_completed) { btn.textContent = '✅ Already done'; return; }

        if (data.success) {
            var card = document.getElementById('seg-' + segId);
            if (card) {
                card.classList.remove('seg--active');
                card.classList.add('seg--done', 'seg--pop');
                var ico = card.querySelector('.seg-ico');
                if (ico) ico.textContent = '✅';
            }

            xpToast(data.xp_awarded);

            (data.events || []).forEach(function(ev) {
                if (ev.type === 'module_complete') xpToast(ev.xp);
                if (ev.type === 'folder_complete') xpToast(ev.xp);
                if (ev.type === 'level_up') setTimeout(function(){ levelUpModal(ev); }, 1200);
            });

            // Show next steps card
            var allSegs = document.querySelectorAll('.seg');
            var currentIdx = -1;
            allSegs.forEach(function(s, i) { if (s.id === 'seg-' + segId) currentIdx = i; });

            // Collect remaining segments in this module
            var remaining = [];
            for (var i = currentIdx + 1; i < allSegs.length && remaining.length < 4; i++) {
                var s = allSegs[i];
                var sid = s.getAttribute('data-seg') || s.id.replace('seg-', '');
                var title = s.querySelector('.seg-title');
                var isDone = s.classList.contains('seg--done');
                if (!isDone) {
                    remaining.push({
                        id: sid,
                        title: title ? title.textContent : 'Segment ' + sid,
                        locked: s.classList.contains('seg--locked'),
                        el: s
                    });
                }
            }

            // Unlock the immediate next segment
            var nxt = document.querySelector('.seg--locked');
            if (nxt) {
                nxt.classList.remove('seg--locked');
                nxt.classList.add('seg--active', 'seg--unlock');
                var ni = nxt.querySelector('.seg-ico');
                if (ni) ni.textContent = (nxt.querySelector('[data-action="check-quiz"]')) ? '📝' : '📄';
            }

            // Build next-steps card
            var nextHtml = '<div class="seg-done-badge">✅ Completed</div>';

            if (remaining.length > 0) {
                // More segments in this module
                nextHtml += '<div class="next-steps-card">';
                nextHtml += '<div class="next-steps-title">Up Next</div>';
                remaining.forEach(function(r) {
                    if (r.locked) {
                        nextHtml += '<div class="next-step-item next-step--locked">🔒 ' + r.title + '</div>';
                    } else {
                        nextHtml += '<a class="next-step-item" href="#seg-' + r.id + '" data-scroll-seg="' + r.id + '">' +
                            '📄 ' + r.title + ' <span style="color:var(--teal)">→</span></a>';
                    }
                });
                nextHtml += '</div>';
                btn.parentElement.innerHTML = nextHtml;
            } else {
                // Module complete — fetch ALL modules in next stage
                btn.parentElement.innerHTML = nextHtml + '<div class="next-steps-card"><div class="next-steps-title">⏳ Finding what\'s next...</div></div>';
                fetchNextStage(btn.parentElement, nextHtml);
            }

            updateProg();
            confetti();
        }
    } catch (err) {
        btn.textContent = '✕ Error — Retry';
        btn.disabled = false;
    }
}

// ── PASS-OFF REQUEST ──
async function requestPassoff(segId, btn) {
    btn.disabled = true;
    btn.textContent = '⏳ Requesting...';
    try {
        var res = await fetch('/become/api/index.php?route=segments/' + segId + '/request-passoff', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }
        });
        var data = await res.json();
        if (data.success) {
            btn.textContent = '⏳ Waiting for Leader Approval';
            btn.style.opacity = '0.8';
            xpToast(0);
            var t = document.createElement('div');
            t.className = 'xp-toast';
            t.innerHTML = '🎯 Pass-off requested!';
            t.style.background = 'var(--gold)';
            document.body.appendChild(t);
            requestAnimationFrame(function(){ t.classList.add('xp-toast--in'); });
            setTimeout(function(){ t.classList.add('xp-toast--out'); setTimeout(function(){ t.remove(); }, 500); }, 3000);
        }
    } catch (err) {
        btn.textContent = '✕ Error — Retry';
        btn.disabled = false;
    }
}

// ── QUIZ CHECK ──
function checkQuiz(segId, btn) {
    var quizEl = document.getElementById('quiz-' + segId);
    if (!quizEl) return;

    var questions = quizEl.querySelectorAll('.quiz-q');
    var allCorrect = true;
    var unanswered = false;

    questions.forEach(function(q) {
        var selected = q.querySelector('input[type="radio"]:checked');
        var feedback = q.querySelector('.quiz-feedback');
        var opts = q.querySelectorAll('.quiz-opt');

        // Reset styles
        opts.forEach(function(o) { o.classList.remove('correct', 'wrong'); });
        if (feedback) { feedback.style.display = 'none'; feedback.className = 'quiz-feedback'; }

        if (!selected) {
            unanswered = true;
            allCorrect = false;
            if (feedback) {
                feedback.textContent = 'Please select an answer';
                feedback.className = 'quiz-feedback fail';
                feedback.style.display = 'block';
            }
            return;
        }

        var isCorrect = selected.getAttribute('data-correct') === '1';
        var label = selected.closest('.quiz-opt');

        if (isCorrect) {
            if (label) label.classList.add('correct');
            if (feedback) {
                feedback.textContent = '✅ Correct!';
                feedback.className = 'quiz-feedback pass';
                feedback.style.display = 'block';
            }
        } else {
            allCorrect = false;
            if (label) label.classList.add('wrong');
            // Show which was correct
            opts.forEach(function(o) {
                var inp = o.querySelector('input');
                if (inp && inp.getAttribute('data-correct') === '1') o.classList.add('correct');
            });
            if (feedback) {
                feedback.textContent = '❌ Not quite — the correct answer is highlighted';
                feedback.className = 'quiz-feedback fail';
                feedback.style.display = 'block';
            }
        }
    });

    if (unanswered) {
        btn.textContent = '⚠️ Answer all questions first';
        setTimeout(function() { btn.textContent = '📝 Check Answers'; }, 2000);
        return;
    }

    if (allCorrect) {
        // Replace quiz button with Mark Complete
        btn.setAttribute('data-action', 'complete');
        btn.textContent = '✅ All Correct! Mark Complete';
        btn.style.background = 'linear-gradient(135deg, var(--green), #05b88a)';
        confetti();
        xpToast(0);
        var t = document.createElement('div');
        t.className = 'xp-toast';
        t.innerHTML = '🎉 Quiz passed!';
        t.style.background = 'var(--green)';
        document.body.appendChild(t);
        requestAnimationFrame(function(){ t.classList.add('xp-toast--in'); });
        setTimeout(function(){ t.classList.add('xp-toast--out'); setTimeout(function(){ t.remove(); }, 500); }, 2500);
    } else {
        btn.textContent = '❌ Some answers wrong — try again';
        btn.style.background = 'linear-gradient(135deg, var(--red, #EF476F), #d62828)';
        setTimeout(function() {
            btn.textContent = '📝 Check Answers';
            btn.style.background = 'linear-gradient(135deg, var(--gold), var(--orange))';
        }, 3000);
    }
}

function updateProg() {
    var fill = document.querySelector('.prog-wrap .bar-fill');
    var info = document.querySelector('.prog-info');
    if (!fill) return;
    var total = document.querySelectorAll('.seg').length;
    var done = document.querySelectorAll('.seg--done').length;
    var pct = total > 0 ? Math.round((done / total) * 100) : 0;
    fill.style.width = pct + '%';
    if (info) info.innerHTML = '<span>' + done + '/' + total + ' segments</span><span>' + pct + '%</span>';
}

// Fetch all modules in the next progression stage
async function fetchNextStage(container, baseHtml) {
    try {
        var res = await fetch('/become/api/index.php?route=next_stage');
        var ns = await res.json();

        if (ns.type === 'next_stage' && ns.modules && ns.modules.length > 0) {
            var stageHtml = baseHtml + '<div class="next-steps-card">';
            if (ns.modules.length === 1) {
                stageHtml += '<div class="next-steps-title">🎉 Module Complete! Next Up:</div>';
            } else {
                stageHtml += '<div class="next-steps-title">🎉 Module Complete! Complete all ' + ns.modules.length + ':</div>';
            }
            ns.modules.forEach(function(m) {
                var href = m.segment_id ? '/become/module.php?id=' + m.module_id + '#seg-' + m.segment_id : '/become/module.php?id=' + m.module_id;
                stageHtml += '<a class="next-step-item" href="' + href + '" style="font-weight:600">' +
                    (m.module_icon || '📄') + ' ' + m.module_title +
                    '<span style="color:var(--dim);font-size:.7rem;margin-left:.5rem">' + (m.folder_icon || '📁') + ' ' + m.folder_title + '</span>' +
                    ' <span style="color:var(--teal)">→</span></a>';
            });
            stageHtml += '<a class="next-step-item" href="/become/" style="color:var(--dim);font-size:.78rem">← Or back to dashboard</a>';
            stageHtml += '</div>';
            container.innerHTML = stageHtml;
        } else if (ns.type === 'level_up') {
            container.innerHTML = baseHtml +
                '<div class="next-steps-card">' +
                '<div class="next-steps-title">🎉 Level Complete!</div>' +
                '<div class="next-step-item" style="color:var(--gold)">' + (ns.label || 'Keep going!') + '</div>' +
                '<a class="next-step-item" href="/become/" style="color:var(--teal);font-weight:700">← Back to Dashboard</a></div>';
        } else {
            container.innerHTML = baseHtml +
                '<div class="next-steps-card">' +
                '<div class="next-steps-title">🎉 All Complete!</div>' +
                '<a class="next-step-item" href="/become/" style="color:var(--teal);font-weight:700">← Back to Dashboard</a></div>';
        }
    } catch(e) {
        container.innerHTML = baseHtml +
            '<div class="next-steps-card">' +
            '<div class="next-steps-title">🎉 Module Complete!</div>' +
            '<a class="next-step-item" href="/become/">← Back to Dashboard</a></div>';
    }
}

// ── XP TOAST ──
function xpToast(xp) {
    if (!xp) return;
    var t = document.createElement('div');
    t.className = 'xp-toast';
    t.innerHTML = '⚡ +' + xp + ' XP';
    document.body.appendChild(t);
    requestAnimationFrame(function(){ t.classList.add('xp-toast--in'); });
    setTimeout(function(){ t.classList.add('xp-toast--out'); setTimeout(function(){ t.remove(); }, 500); }, 2500);
}

// ── LEVEL UP MODAL ──
function levelUpModal(ev) {
    var bg = document.createElement('div');
    bg.className = 'modal-bg';
    bg.innerHTML =
        '<div class="lvl-modal">' +
        '<h2 class="lvl-title">⚡ LEVEL UP!</h2>' +
        '<p class="lvl-from">Level ' + ev.from + '</p>' +
        '<p class="lvl-arrow">→</p>' +
        '<p class="lvl-to">Level ' + ev.to + '</p>' +
        '<button class="btn-teal lvl-btn" onclick="this.closest(\'.modal-bg\').remove()">Let\'s Go! 🚀</button>' +
        '</div>';
    document.body.appendChild(bg);
    confetti(); confetti();
}

// ── CONFETTI ──
function confetti() {
    var colors = ['#22A8B3','#FB9B47','#06D6A0','#FFB703','#8ECAE6'];
    for (var i = 0; i < 40; i++) {
        var c = document.createElement('div');
        c.className = 'confetti';
        c.style.left = Math.random()*100 + 'vw';
        c.style.backgroundColor = colors[Math.floor(Math.random()*colors.length)];
        c.style.animationDelay = Math.random()*0.5 + 's';
        c.style.animationDuration = (Math.random()*1.5+1.5) + 's';
        document.body.appendChild(c);
        setTimeout(function(el){ el.remove(); }, 3500, c);
    }
}

// ── INLINE EDITING (Leaders) ──
var activeEditor = null;

function startEdit(segId) {
    if (typeof IS_LEADER==='undefined'||!IS_LEADER) return;
    if (typeof Quill==='undefined') return;
    if (activeEditor) cancelEdit();

    var body = document.getElementById('body-' + segId);
    if (!body) return;
    var rich = body.querySelector('.seg-rich');
    var html = rich ? rich.innerHTML : '';

    var wrap = document.createElement('div');
    wrap.id = 'ew-' + segId;
    wrap.innerHTML =
        '<div id="qe-' + segId + '" class="inline-editor"></div>' +
        '<div class="inline-actions">' +
        '<button class="inline-save" onclick="saveEdit(' + segId + ')">💾 Save</button>' +
        '<button class="inline-cancel" onclick="cancelEdit()">Cancel</button>' +
        '</div>';

    if (rich) rich.style.display = 'none';
    var eb = body.querySelector('.edit-btn--seg');
    if (eb) eb.style.display = 'none';
    body.insertBefore(wrap, body.firstChild);

    var quill = new Quill('#qe-' + segId, {
        theme:'snow',
        modules:{toolbar:[
            ['bold','italic','underline','strike'],
            [{header:[1,2,3,false]}],
            [{size:['small',false,'large','huge']}],
            [{color:[]},{background:[]}],
            [{list:'ordered'},{list:'bullet'}],
            [{align:[]}],
            ['link','image','video'],
            ['clean']
        ]}
    });
    quill.root.innerHTML = html;
    activeEditor = { segId:segId, quill:quill, original:html };
}

async function saveEdit(segId) {
    if (!activeEditor || activeEditor.segId !== segId) return;
    var html = activeEditor.quill.root.innerHTML;
    var btn = document.querySelector('#ew-'+segId+' .inline-save');
    if (btn) btn.textContent = '⏳ Saving...';
    try {
        var res = await fetch('/become/api/index.php?route=segments/'+segId+'/edit', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({content_html:html})
        });
        var data = await res.json();
        if (data.success) {
            var body = document.getElementById('body-'+segId);
            var rich = body.querySelector('.seg-rich');
            if (!rich) { rich = document.createElement('div'); rich.className='seg-rich'; body.prepend(rich); }
            rich.innerHTML = html;
            rich.style.display = '';
            cancelEdit();
        }
    } catch(err) { if(btn) btn.textContent='✕ Error'; }
}

function cancelEdit() {
    if (!activeEditor) return;
    var w = document.getElementById('ew-'+activeEditor.segId);
    if (w) w.remove();
    var body = document.getElementById('body-'+activeEditor.segId);
    if (body) {
        var r = body.querySelector('.seg-rich'); if(r) r.style.display='';
        var b = body.querySelector('.edit-btn--seg'); if(b) b.style.display='';
    }
    activeEditor = null;
}

function toggleTitleEdit(btn) {
    var el = btn.previousElementSibling;
    if (el.contentEditable==='true') {
        el.contentEditable='false'; el.classList.remove('editing'); btn.textContent='✏️';
        fetch('/become/api/index.php?route=modules/'+el.dataset.mod+'/edit',{
            method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({title:el.textContent.trim()})
        }).catch(function(){});
    } else {
        el.contentEditable='true'; el.classList.add('editing'); el.focus(); btn.textContent='💾';
    }
}

// ── EVENT DELEGATION — Cloudflare-safe button handling ──
document.addEventListener('click', function(e) {
    // Next step item — scroll to segment
    var nextStep = e.target.closest('[data-scroll-seg]');
    if (nextStep) {
        e.preventDefault();
        var segId = nextStep.dataset.scrollSeg;
        var target = document.getElementById('seg-' + segId);
        if (target) {
            target.scrollIntoView({behavior:'smooth', block:'center'});
            target.style.transition = 'box-shadow .3s';
            target.style.boxShadow = '0 0 25px rgba(34,168,179,0.35)';
            setTimeout(function() { target.style.boxShadow = ''; }, 2000);
        }
        return;
    }

    // Quiz check answers
    var quizBtn = e.target.closest('[data-action="check-quiz"]');
    if (quizBtn) {
        var segId = quizBtn.getAttribute('data-seg-id');
        checkQuiz(parseInt(segId), quizBtn);
        return;
    }

    // Mark Complete / Pass-off buttons
    var btn = e.target.closest('.btn-complete');
    if (btn && !btn.disabled) {
        var segId = btn.getAttribute('data-seg-id');
        var action = btn.getAttribute('data-action');
        if (segId) {
            if (action === 'passoff') {
                requestPassoff(parseInt(segId), btn);
            } else {
                completeSeg(parseInt(segId), btn);
            }
            return;
        }
        // Fallback: try getting segId from parent card
        var segCard = btn.closest('.seg');
        if (segCard && segCard.dataset.seg) {
            completeSeg(parseInt(segCard.dataset.seg), btn);
        }
        return;
    }

    // Folder gallery card — click to expand/collapse module list
    var galFolder = e.target.closest('.gal-folder');
    if (galFolder && !e.target.closest('.gal-mod') && !e.target.closest('a')) {
        galFolder.classList.toggle('gal-folder--expanded');
        return;
    }

    // Edit button (leader) — title
    var titleEdit = e.target.closest('[data-action="title-edit"]');
    if (titleEdit) {
        toggleTitleEdit(titleEdit);
        return;
    }

    // Edit button (leader) — segment
    var segEdit = e.target.closest('[data-action="seg-edit"]');
    if (segEdit) {
        var segEditId = segEdit.getAttribute('data-seg-id');
        if (segEditId && typeof startEdit === 'function') startEdit(parseInt(segEditId));
        return;
    }

    // Folder toggle (works with both onclick and data-toggle attribute)
    var folderHdr = e.target.closest('.folder-hdr') || e.target.closest('[data-toggle="folder"]');
    if (folderHdr) {
        var folder = folderHdr.closest('.folder');
        if (folder) folder.classList.toggle('folder--open');
        return;
    }

    // Edit button (leader)
    var editBtn = e.target.closest('.edit-btn--seg');
    if (editBtn) {
        var segEl = editBtn.closest('.seg');
        if (segEl && segEl.dataset.seg) {
            startEdit(parseInt(segEl.dataset.seg));
        }
        return;
    }

    // Title edit (leader)
    var titleEditBtn = e.target.closest('.edit-btn:not(.edit-btn--seg)');
    if (titleEditBtn) {
        toggleTitleEdit(titleEditBtn);
        return;
    }
});
