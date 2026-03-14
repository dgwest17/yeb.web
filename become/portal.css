/* =============================================
   portal.css — Dark Wave Training Portal
   Location: public_html/become/portal.css
   ============================================= */

:root {
    --bg:#0a0a0f;
    --card:rgba(255,255,255,0.03);
    --card-h:rgba(255,255,255,0.06);
    --bdr:rgba(34,168,179,0.15);
    --bdr-a:rgba(34,168,179,0.4);
    --teal:#22A8B3;
    --teal-g:rgba(34,168,179,0.3);
    --orange:#FB9B47;
    --green:#06D6A0;
    --gold:#FFB703;
    --navy:#023047;
    --txt:#fff;
    --dim:rgba(255,255,255,0.5);
    --mute:rgba(255,255,255,0.3);
    --hf:'Playfair Display',serif;
    --bf:'DM Sans',sans-serif;
    --r:16px;
    --rs:10px;
}
*{margin:0;padding:0;box-sizing:border-box}

body{
    font-family:var(--bf);background:var(--bg);color:var(--txt);
    min-height:100vh;overflow-x:hidden;
}
body::before{
    content:'';position:fixed;inset:0;pointer-events:none;z-index:0;
    background:
        radial-gradient(ellipse at 20% 80%,rgba(34,168,179,0.06) 0%,transparent 60%),
        radial-gradient(ellipse at 80% 20%,rgba(251,155,71,0.04) 0%,transparent 60%);
}

/* ─── LAYOUT ─── */
.portal{position:relative;z-index:1;max-width:800px;margin:0 auto;padding:1.5rem;padding-bottom:4rem}

/* ─── HEADER ─── */
.hdr{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem;gap:1rem;flex-wrap:wrap}
.hdr-title{font-family:var(--hf);font-size:1.6rem;background:linear-gradient(135deg,var(--teal),var(--orange));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hdr-sub{color:var(--dim);font-size:.95rem;margin-top:.25rem}
.hdr-right{display:flex;align-items:center;gap:1rem}
.hdr-logout{color:var(--dim);text-decoration:none;font-size:.85rem}
.hdr-logout:hover{color:var(--teal)}
.badge-leader{background:linear-gradient(135deg,var(--gold),var(--orange));color:var(--navy);padding:4px 12px;border-radius:20px;font-size:.8rem;font-weight:700}

/* ─── CARDS ─── */
.card{background:var(--card);border:1px solid var(--bdr);border-radius:var(--r);padding:1.25rem;margin-bottom:1rem}

/* ─── XP CARD ─── */
.xp-card{margin-bottom:1rem}
.xp-top{display:flex;justify-content:space-between;margin-bottom:.5rem}
.xp-label{font-weight:700;font-size:1rem}
.xp-num{color:var(--teal);font-weight:700;font-size:1rem}
.xp-hint{color:var(--dim);font-size:.8rem;margin-top:.4rem}

/* ─── PROGRESS BARS ─── */
.bar{height:12px;background:rgba(255,255,255,0.08);border-radius:6px;overflow:hidden}
.bar--sm{height:3px;margin:0 1.25rem .5rem;border-radius:2px}
.bar-fill{height:100%;background:linear-gradient(90deg,var(--teal),var(--green));border-radius:inherit;transition:width .8s ease;box-shadow:0 0 12px var(--teal-g)}

/* ─── NEXT ACTION ─── */
.next-card{background:linear-gradient(135deg,rgba(34,168,179,0.12),rgba(251,155,71,0.08));border-color:var(--bdr-a);margin-bottom:1.5rem}
.next-top{display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem}
.next-icon{font-size:1.5rem}
.next-label{font-weight:700;font-size:.85rem;text-transform:uppercase;letter-spacing:.05em;color:var(--teal)}
.next-text{font-size:1rem;margin-bottom:.75rem}
.btn-teal{display:inline-block;padding:.6rem 1.5rem;background:linear-gradient(135deg,var(--teal),#1a8a93);color:#fff;border:none;border-radius:var(--rs);font-weight:700;font-size:.95rem;cursor:pointer;text-decoration:none;transition:transform .2s,box-shadow .2s;font-family:var(--bf)}
.btn-teal:hover{transform:translateY(-1px);box-shadow:0 4px 20px var(--teal-g)}

/* ─── STATS GRID ─── */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:2rem}
.stat{background:var(--card);border:1px solid var(--bdr);border-radius:var(--rs);padding:.75rem;text-align:center}
.stat-n{display:block;font-size:1.4rem;font-weight:700;color:var(--teal)}
.stat-l{font-size:.7rem;color:var(--dim);text-transform:uppercase;letter-spacing:.05em}

/* ─── SECTIONS ─── */
.section{margin-bottom:2rem}
.sec-title{font-family:var(--hf);font-size:1.3rem;margin-bottom:.5rem}
.sec-sub{color:var(--dim);font-size:.85rem;margin-bottom:1rem}
.sq-section{border-top:1px solid rgba(255,255,255,0.05);padding-top:1.5rem}

/* ─── FOLDER CARDS ─── */
.folder{background:var(--card);border:1px solid var(--bdr);border-radius:var(--r);margin-bottom:.75rem;overflow:hidden;transition:border-color .3s}
.folder:hover{border-color:var(--bdr-a)}
.folder--locked{opacity:.45;pointer-events:none;position:relative}
.folder--sq{border-left:3px solid var(--gold)}
.folder--child{margin:0 .5rem .5rem 1rem;border-radius:var(--rs)}
.folder-lock{position:absolute;top:50%;right:1.5rem;transform:translateY(-50%);font-size:1.5rem;z-index:2}
.folder-hdr{display:flex;align-items:center;padding:1rem 1.25rem;cursor:pointer;gap:.75rem;user-select:none}
.folder-icon{font-size:1.5rem;flex-shrink:0}
.folder-info{flex:1}
.folder-info h3{font-size:1rem;font-weight:700;margin:0}
.folder-meta{font-size:.8rem;color:var(--dim)}
.folder-pct{font-size:.85rem;font-weight:700;color:var(--teal)}
.folder-arrow{color:var(--mute);font-size:.8rem;transition:transform .2s}
.folder--open > .folder-hdr .folder-arrow{transform:rotate(90deg)}
.folder-body{display:none;padding:0 .75rem .75rem}
.folder--open > .folder-body{display:block}

/* ─── MODULE ITEMS ─── */
.mod{display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;border-radius:var(--rs);text-decoration:none;color:var(--txt);transition:background .2s;margin-bottom:.25rem}
.mod:hover{background:var(--card-h)}
.mod--locked{opacity:.4;cursor:default}
.mod--locked:hover{background:none}
.mod--done{opacity:.7}
.mod-ico{font-size:1.1rem;flex-shrink:0}
.mod-info{flex:1}
.mod-title{display:block;font-weight:600;font-size:.9rem}
.mod-meta{font-size:.75rem;color:var(--dim)}
.mod-bar{width:60px;height:4px;background:rgba(255,255,255,0.1);border-radius:2px;overflow:hidden}
.mod-bar-fill{height:100%;background:var(--teal);border-radius:2px}
.mod-xp{font-size:.75rem;color:var(--green);font-weight:600}

/* ─── BREADCRUMB ─── */
.crumb{margin-bottom:1.5rem;font-size:.85rem;color:var(--dim)}
.crumb a{color:var(--teal);text-decoration:none}
.crumb a:hover{text-decoration:underline}
.crumb span{margin:0 .3rem}
.crumb-cur{color:var(--dim)}

/* ─── MODULE HEADER CARD ─── */
.mod-hdr-card{margin-bottom:1.5rem}
.mod-hdr-top{display:flex;align-items:center;gap:.75rem}
.mod-page-title{font-family:var(--hf);font-size:1.5rem;flex:1}
.mod-page-title.editing{outline:2px solid var(--teal);border-radius:4px;padding:2px 6px}
.mod-desc{color:var(--dim);margin-top:.5rem;font-size:.9rem}
.prog-wrap{margin-top:1rem}
.prog-info{display:flex;justify-content:space-between;font-size:.8rem;color:var(--dim);margin-bottom:.4rem}

/* ─── EDIT BUTTONS ─── */
.edit-btn{background:none;border:none;cursor:pointer;font-size:1.1rem;opacity:.4;transition:opacity .2s}
.edit-btn:hover{opacity:1}
.edit-btn--seg{display:block;margin-top:.75rem;font-size:.85rem;opacity:.3}
.edit-btn--seg:hover{opacity:.8}

/* ─── SEGMENT CARDS ─── */
.seg-list{display:flex;flex-direction:column;gap:1rem}
.seg{background:var(--card);border:1px solid var(--bdr);border-radius:var(--r);overflow:hidden;transition:border-color .3s,transform .3s}
.seg--active{border-color:var(--bdr-a)}
.seg--active:hover{transform:translateY(-1px)}
.seg--done{border-color:rgba(6,214,160,0.2)}
.seg--locked{opacity:.4}
.seg--pop{animation:segPop .6s ease}
.seg--unlock{animation:segUnlock .6s ease}
@keyframes segPop{0%{transform:scale(1)}50%{transform:scale(1.02)}100%{transform:scale(1)}}
@keyframes segUnlock{0%{opacity:.4;transform:translateY(10px)}100%{opacity:1;transform:translateY(0)}}

.seg-hdr{display:flex;align-items:center;gap:.75rem;padding:1rem 1.25rem}
.seg-ico{font-size:1.2rem;flex-shrink:0}
.seg-title{font-weight:700;font-size:1rem;flex:1}
.seg-xp{font-size:.75rem;font-weight:700;color:var(--green);background:rgba(6,214,160,0.1);padding:3px 10px;border-radius:20px}

.seg-body{padding:0 1.25rem 1rem}
.seg-locked-msg{padding:1rem 1.25rem;color:var(--mute);font-size:.85rem;font-style:italic}

/* ─── SEGMENT RICH CONTENT ─── */
.seg-rich{font-size:.95rem;line-height:1.7;color:rgba(255,255,255,0.85)}
.seg-rich h1{font-family:var(--hf);font-size:1.4rem;margin:1rem 0 .5rem}
.seg-rich h2{font-family:var(--hf);font-size:1.2rem;margin:1rem 0 .5rem;color:var(--teal)}
.seg-rich h3{font-size:1rem;margin:.75rem 0 .4rem;color:var(--orange)}
.seg-rich p{margin-bottom:.75rem}
.seg-rich ul,.seg-rich ol{margin:0 0 .75rem 1.5rem;color:rgba(255,255,255,0.8)}
.seg-rich li{margin-bottom:.3rem}
.seg-rich strong{color:#fff}
.seg-rich a{color:var(--teal)}
.seg-rich img{max-width:100%;border-radius:var(--rs);margin:.5rem 0}

/* ─── CONVERSATION BUBBLES ─── */
.seg-convo{margin-top:1rem;display:flex;flex-direction:column;gap:.5rem}
.bubble{padding:.75rem 1rem;border-radius:var(--rs);font-size:.9rem;line-height:1.6}
.bubble-who{font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:.25rem}
.bubble--cust{background:rgba(255,255,255,0.05);border-left:3px solid var(--dim)}
.bubble--cust .bubble-who{color:var(--dim)}
.bubble--rep{background:rgba(34,168,179,0.08);border-left:3px solid var(--teal)}
.bubble--rep .bubble-who{color:var(--teal)}
.bubble--tip{background:rgba(255,183,3,0.08);border-left:3px solid var(--gold)}
.bubble--tip .bubble-who{color:var(--gold)}

/* ─── SEGMENT MEDIA ─── */
.seg-media{margin-top:.75rem}
.seg-img{max-width:100%;border-radius:var(--rs)}
.seg-vid{position:relative;padding-bottom:56.25%;height:0;border-radius:var(--rs);overflow:hidden}
.seg-vid iframe{position:absolute;top:0;left:0;width:100%;height:100%}
.seg-pdf{display:inline-block;padding:.5rem 1rem;background:rgba(255,255,255,0.05);border:1px solid var(--bdr);border-radius:var(--rs);color:var(--teal);text-decoration:none;font-size:.9rem}
.seg-pdf:hover{border-color:var(--teal)}

/* ─── COMPLETE BUTTON ─── */
.seg-actions{padding:0 1.25rem 1.25rem}
.btn-complete{width:100%;padding:.85rem;background:linear-gradient(135deg,var(--green),#05b88a);color:var(--navy);border:none;border-radius:var(--rs);font-size:.95rem;font-weight:700;cursor:pointer;font-family:var(--bf);transition:transform .2s,box-shadow .2s}
.btn-complete:hover{transform:translateY(-1px);box-shadow:0 4px 20px rgba(6,214,160,0.3)}
.btn-complete:disabled{opacity:.6;cursor:not-allowed;transform:none}
.seg-done-badge{padding:.75rem 1.25rem;color:rgba(6,214,160,0.6);font-size:.85rem;font-weight:600}

/* ─── NEXT STEP ─── */
.next-step{margin-top:1.5rem;color:var(--dim);font-size:.95rem}

/* ─── XP TOAST ─── */
.xp-toast{position:fixed;top:1.5rem;right:1.5rem;background:rgba(6,214,160,0.95);color:var(--navy);padding:.6rem 1.2rem;border-radius:var(--rs);font-weight:700;font-size:1rem;font-family:var(--bf);z-index:9999;transform:translateX(120%);transition:transform .3s ease}
.xp-toast--in{transform:translateX(0)}
.xp-toast--out{transform:translateY(-80px);opacity:0;transition:all .4s ease}

/* ─── LEVEL UP MODAL ─── */
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,0.8);z-index:9990;display:flex;align-items:center;justify-content:center;animation:fadeIn .3s ease}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.lvl-modal{background:var(--bg);border:2px solid var(--teal);border-radius:20px;padding:2.5rem;text-align:center;max-width:360px;width:90%;position:relative;overflow:hidden}
.lvl-modal::before{content:'';position:absolute;inset:-50%;background:radial-gradient(circle,var(--teal-g) 0%,transparent 70%);animation:pulse 2s ease infinite}
@keyframes pulse{0%,100%{opacity:.3}50%{opacity:.6}}
.lvl-title{font-family:var(--hf);font-size:1.8rem;color:var(--teal);position:relative;z-index:1;margin-bottom:.75rem}
.lvl-from,.lvl-arrow,.lvl-to,.lvl-btn{position:relative;z-index:1}
.lvl-from{color:var(--dim);font-size:1rem}
.lvl-arrow{font-size:1.5rem;margin:.5rem 0}
.lvl-to{font-size:1.3rem;font-weight:700;margin-bottom:1rem}

/* ─── CONFETTI ─── */
.confetti{position:fixed;top:-10px;width:8px;height:8px;border-radius:2px;z-index:9999;pointer-events:none;animation:fall linear forwards}
@keyframes fall{0%{transform:translateY(0) rotate(0deg);opacity:1}100%{transform:translateY(100vh) rotate(720deg);opacity:0}}

/* ─── INLINE EDITOR (Leader) ─── */
.inline-editor{background:var(--card);border:1px solid var(--bdr-a);border-radius:var(--rs);margin-bottom:.75rem}
.inline-editor .ql-toolbar{background:rgba(255,255,255,0.03);border-bottom:1px solid var(--bdr);border-radius:var(--rs) var(--rs) 0 0}
.inline-editor .ql-container{border:none;min-height:150px;color:var(--txt);font-family:var(--bf);font-size:.95rem}
.inline-editor .ql-editor{padding:1rem}
.inline-editor .ql-editor.ql-blank::before{color:var(--mute)}
.inline-actions{display:flex;gap:.5rem;margin-top:.5rem}
.inline-save{padding:.5rem 1.2rem;background:var(--teal);color:#fff;border:none;border-radius:var(--rs);font-weight:700;cursor:pointer;font-family:var(--bf);font-size:.85rem}
.inline-save:hover{background:#1a8a93}
.inline-cancel{padding:.5rem 1.2rem;background:transparent;color:var(--dim);border:1px solid var(--bdr);border-radius:var(--rs);cursor:pointer;font-family:var(--bf);font-size:.85rem}
.inline-cancel:hover{border-color:var(--dim)}

/* ─── RESPONSIVE ─── */
@media(max-width:600px){
    .portal{padding:1rem}
    .hdr-title{font-size:1.3rem}
    .stats-grid{grid-template-columns:repeat(2,1fr)}
    .mod-page-title{font-size:1.2rem}
    .seg-hdr{padding:.75rem 1rem}
    .seg-body{padding:0 1rem .75rem}
    .seg-actions{padding:0 1rem 1rem}
}

/* ─── CONTINUE BUTTON ─── */
.btn-continue{
    display:block;text-align:center;margin:2rem auto 1rem;padding:1rem 2rem;
    background:linear-gradient(135deg,var(--teal),#1a8a93);color:#fff;
    border-radius:var(--rs);font-size:1.05rem;font-weight:700;text-decoration:none;
    font-family:var(--bf);transition:transform .2s,box-shadow .2s;max-width:500px;
}
.btn-continue:hover{transform:translateY(-2px);box-shadow:0 6px 24px rgba(34,168,179,0.3)}

/* ─── PASSOFF BADGE ─── */
.seg--passoff{border-left:3px solid var(--gold)}
.seg--passoff .seg-ico{color:var(--gold)}
.passoff-badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:.7rem;font-weight:700;background:rgba(255,183,3,0.15);color:var(--gold);margin-left:.5rem}
