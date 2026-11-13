/* prep.js — the "before we meet" questions, 30–60 seconds, one per screen.
 *
 *   LumariaPrep.mount(el, { path: 'solar' | 'audit', ref, contact, booked })
 *     path     solar = new solar (surf look), audit = existing solar (ship look);
 *              leave it out and the first screen asks "Do you already have solar?"
 *     ref      the signed Lead reference zoho.php returns, so answers attach to that Lead
 *     contact  { first, last, phone, email } when already known (skips the contact screen)
 *     token    a link sent from app.lumaria.energy (?t=…) — it already knows who they are,
 *              so no contact screen, it greets them by name, and answers go on that client
 *     booked   true when they've just been shown the calendar (the finish screen won't repeat it)
 *
 * Used on the quote success screen, the audit booking pages and /prep/ (a link David texts).
 * Answers go to /prep.php → their Zoho Lead (note + fields + bill), David is told, and the
 * homeowner gets a booking link (by text once Quo is connected). Progress is kept on the
 * phone, so leaving and coming back picks up where they were. */
(function () {
  'use strict';
  var OPTIONS_URL = '/options/';

  var CHANGES = [
    ['ev', 'Electric vehicle (EV)'], ['pool', 'Pool or spa'], ['sauna', 'Sauna'], ['ac', 'Air conditioning'],
    ['heatpump', 'Heat pump'], ['dryer', 'Electric dryer'], ['range', 'Electric range / stove'],
    ['waterheater', 'Electric water heater'], ['addition', 'Home addition or ADU']
  ];
  var Q = {
    solar: [
      { id: 'bill', type: 'bill', q: 'About how much is your electric bill?', help: 'A typical month. Snap a photo of a bill too if you have one handy — optional.' },
      { id: 'changes', type: 'tri', q: 'Anything coming that adds to your usage?', help: 'Everything starts at No — tap Possibly or 100% yes.', items: CHANGES },
      { id: 'roof_age', type: 'one', q: 'How old is your roof?', opts: ['Under 5 years', '5–10 years', '10–15 years', '15–20 years', '20+ years', 'Not sure'] },
      { id: 'stay', type: 'one', q: 'How long do you plan to live in this home?', opts: ['Under 3 years', '3–7 years', '7–15 years', '15+ years / forever', 'Not sure yet'] },
      { id: 'timeline', type: 'one', q: 'How soon would you like it installed?', opts: ['ASAP', 'In a couple of weeks', 'In a couple of months', 'Within 6 months', 'Within a year', 'In the next few years'] },
      { id: 'backup', type: 'many', q: 'In an outage, what would you want kept on?', opts: ['Fridge & freezer', 'Lights & outlets', 'Internet / Wi-Fi', 'Medical equipment', 'Well or pool pump', 'AC / heating', 'The whole home', 'Nothing — just savings'] },
      { id: 'leaning', type: 'one', q: 'How were you hoping to do the project?', helpHtml: 'Not sure? <a class="pp-link" href="' + OPTIONS_URL + '" target="_blank" rel="noopener">See how each option works →</a>',
        opts: [['Upfront', 'Fast payoff, you own it, and you have cash you want to put toward it'],
               ['$0 down ownership', 'You own it with no cash now and a 0% annual increase: savings right away, no more big utility bills, pay it off whenever you like'],
               ['$0 down PPA', 'Strong warranties, full maintenance and peace of mind for 25 years, with savings right away. You\'re willing to pay a bit more over the long term for that, and owning it doesn\'t matter to you'],
               ['🤪 Stick with the utility', 'Ever-increasing rates (4–12% a year), blackouts, no control, and no return on what you pay'],
               ['Other', 'A mix of these, not sure yet, or something else']] },
      { id: 'held_back', type: 'many', q: 'What\'s held you back from solar before?', other: true,
        opts: ['Roof concerns', 'Thought we might move', 'Worried about selling the home', 'Bill used to be lower', 'Heard bad things about solar', 'Bad experience with a solar rep', 'Other home projects', 'Thought it cost too much', 'Too busy', 'Nothing — just starting'] },
      { id: 'notes', type: 'text', q: 'Anything else David should know?', help: 'Questions, quotes you\'ve gotten, who\'ll be at the meeting — anything.', optional: true }
    ],
    audit: [
      { id: 'installed', type: 'installed', q: 'When was your system installed?', help: 'Your best guess is fine — just tell us if it\'s exact.' },
      { id: 'installer', type: 'textnot', q: 'Who installed it?', placeholder: 'e.g. Sunrun, Tesla, a local company', notSure: 'Not sure' },
      { id: 'panels', type: 'count', q: 'About how many panels?', notSure: 'Not sure' },
      { id: 'systems', type: 'one', q: 'How many solar systems are on the property?', opts: ['1', '2', '3 or more'] },
      { id: 'paid', type: 'paid', q: 'How did you pay for it?' },
      { id: 'battery', type: 'one', q: 'Any battery installed?', opts: ['No battery', 'Tesla Powerwall', 'Enphase', 'FranklinWH', 'LG', 'SolarEdge', 'Other', 'Not sure'] },
      { id: 'monitoring', type: 'one', q: 'Which monitoring app do you use?', help: 'Just the name — never a password.', opts: ['Enphase Enlighten', 'Tesla app', 'mySolarEdge', 'SunPower / SunStrong', 'Sunnova / SunStrong', 'Sunrun', 'Generac', 'Other', 'None / don\'t know'] },
      { id: 'bugging', type: 'many', q: 'What\'s bugging you?', other: true,
        opts: ['True-up was too high', 'Bill is still high', 'Production seems low', 'Error light or alert', 'Roof leak', 'Installer is gone / unresponsive', 'Warranty question', 'Thinking about a battery', 'Selling the home', 'Nothing — just a check-up'] },
      { id: 'changes', type: 'tri', q: 'Anything coming that adds to your usage?', help: 'Everything starts at No — tap Possibly or 100% yes.', items: CHANGES },
      { id: 'interested', type: 'many', q: 'What are you interested in?', opts: ['A battery', 'More panels', 'Lowering my bill', 'Fixing a problem', 'Selling-home help', 'Just answers'] },
      { id: 'questions', type: 'text', q: 'Any questions for David?', optional: true }
    ]
  };

  var css = (function () { var done = false; return function () { if (done) return; done = true; var l = document.createElement('link'); l.rel = 'stylesheet'; l.href = '/prep.css'; document.head.appendChild(l); }; })();
  var el = function (tag, attrs, kids) {
    var n = document.createElement(tag);
    for (var k in attrs || {}) { var v = attrs[k]; if (v == null || v === false) continue; if (k === 'class') n.className = v; else if (k === 'html') n.innerHTML = v; else if (k.slice(0, 2) === 'on') n.addEventListener(k.slice(2), v); else n.setAttribute(k, v === true ? '' : v); }
    [].concat(kids || []).forEach(function (c) { if (c != null && c !== false) n.appendChild(c.nodeType ? c : document.createTextNode(String(c))); });
    return n;
  };
  var SWELL = function (amp) { return 'M0 ' + (60 - amp) + ' Q 180 ' + (60 - 2 * amp) + ' 360 ' + (60 - amp) + ' T 720 ' + (60 - amp) + ' T 1080 ' + (60 - amp) + ' T 1440 ' + (60 - amp) + ' T 1800 ' + (60 - amp) + ' T 2160 ' + (60 - amp) + ' T 2520 ' + (60 - amp) + ' T 2880 ' + (60 - amp) + ' V 120 H 0 Z'; };
  var swellHtml = '<div class="pp-swell" aria-hidden="true">' + [['#0e7490', 22], ['#0b5566', 16], ['#06303f', 12]].map(function (c, i) {
    return '<svg class="l' + (i + 1) + '" viewBox="0 0 2880 120" preserveAspectRatio="none"><path d="' + SWELL(c[1]) + '" fill="' + c[0] + '"/>' + (i === 2 ? '<path d="' + SWELL(12).split(' V')[0] + '" fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="2"/>' : '') + '</svg>'; }).join('') + '</div>';
  var BOAT = '<svg viewBox="0 0 40 40" aria-hidden="true"><path d="M20 4 L20 27" stroke="#17222C" stroke-width="2"/><path d="M21 6 L33 25 L21 25 Z" fill="#F0C763" stroke="#9A7A16" stroke-width="1"/><path d="M19 9 L9 25 L19 25 Z" fill="#fff" stroke="#8A9099" stroke-width="1"/><path d="M5 28 L35 28 L30 35 L10 35 Z" fill="#17222C"/></svg>';
  var LIGHT = '<svg viewBox="0 0 34 46" aria-hidden="true"><rect x="12" y="10" width="10" height="30" fill="#fff" stroke="#17222C" stroke-width="1.5"/><rect x="12" y="18" width="10" height="5" fill="#A8452A"/><rect x="12" y="30" width="10" height="5" fill="#A8452A"/><rect x="10" y="6" width="14" height="5" fill="#17222C"/><circle cx="17" cy="4" r="3" fill="#F0C763"/><path d="M6 44 L28 44 L24 40 L10 40 Z" fill="#5E7285"/></svg>';

  function ripple(e, host) {
    var r = host.getBoundingClientRect(), s = Math.max(r.width, r.height) * 1.6;
    var d = el('span', { class: 'pp-ripple' });
    d.style.width = d.style.height = s + 'px';
    d.style.left = ((e.clientX || r.left + r.width / 2) - r.left - s / 2) + 'px';
    d.style.top = ((e.clientY || r.top + r.height / 2) - r.top - s / 2) + 'px';
    host.appendChild(d); setTimeout(function () { d.remove(); }, 800);
  }
  function wash() {
    var w = el('div', { class: 'pp-wash', 'aria-hidden': 'true', html: '<svg viewBox="0 0 1600 900" preserveAspectRatio="none"><defs><linearGradient id="ppw" x1="0" x2="1"><stop offset="0" stop-color="#5eead4" stop-opacity="0"/><stop offset=".55" stop-color="#2dd4bf" stop-opacity=".55"/><stop offset=".85" stop-color="#e0fffa" stop-opacity=".85"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient></defs><path d="M0 0 H1380 C 1500 120 1440 260 1560 380 C 1640 470 1520 620 1600 900 H0 Z" fill="url(#ppw)"/></svg>' });
    document.body.appendChild(w); setTimeout(function () { w.remove(); }, 1700);
  }

  function mount(host, opt) {
    css();
    opt = opt || {};
    var store = 'lumaria.prep.' + (opt.token ? 't.' + opt.token.slice(-16) : (opt.ref || 'open'));
    var saved = {}; try { saved = JSON.parse(localStorage.getItem(store) || '{}'); } catch (e) {}
    var st = { path: opt.path || saved.path || null, i: saved.i || 0, a: saved.a || {}, contact: opt.contact || saved.contact || null, bill: null };
    var needContact = !opt.ref && !opt.token && !(st.contact && st.contact.first);
    var hi = opt.token && st.contact && st.contact.first ? st.contact.first : '';
    var root = el('div', { class: 'pp' });
    host.innerHTML = ''; host.appendChild(root);

    var keep = function () { try { localStorage.setItem(store, JSON.stringify({ path: st.path, i: st.i, a: st.a, contact: st.contact })); } catch (e) {} };
    var steps = function () { var s = []; if (!st.path) s.push({ id: '_path' }); if (needContact) s.push({ id: '_contact' }); return s.concat(st.path ? Q[st.path] : []); };

    function draw() {
      var list = steps(), step = list[st.i];
      var surf = st.path !== 'audit';
      root.className = 'pp ' + (surf ? 'pp-surf' : 'pp-ship');
      root.innerHTML = '';
      if (surf) { root.appendChild(el('div', { class: 'pp-sun', 'aria-hidden': 'true' })); root.insertAdjacentHTML('beforeend', swellHtml); }
      else root.appendChild(el('div', { class: 'pp-hull', 'aria-hidden': 'true' }));
      var inner = el('div', { class: 'pp-in' });
      root.appendChild(inner);
      if (!step) return finish(inner);
      var pct = Math.round((st.i / list.length) * 100);
      inner.appendChild(el('div', { class: 'pp-kicker' }, surf ? 'Before we meet · ' + Math.max(1, list.length - st.i) + ' quick questions' : 'Come aboard · your system\'s logbook'));
      if (st.i === 0 || step.id === '_path') inner.appendChild(el('div', { class: 'pp-title' }, (hi ? 'Hi ' + hi + ' — ' : '') + (surf ? (hi ? 'let\'s make our time all about you.' : 'Make our time all about you.') : (hi ? 'let\'s log your system.' : 'Let\'s log your system.'))));
      if (surf) { var tide = el('div', { class: 'pp-tide' }, [el('i')]); inner.appendChild(tide); requestAnimationFrame(function () { tide.firstChild.style.width = Math.max(4, pct) + '%'; }); }
      else { var log = el('div', { class: 'pp-log', html: '<div class="sea"></div><div class="beam"></div><div class="light">' + LIGHT + '</div><div class="boat">' + BOAT + '</div>' }); inner.appendChild(log);
             requestAnimationFrame(function () { log.querySelector('.boat').style.left = (6 + pct * 0.8) + '%'; }); inner.appendChild(el('div', { class: 'pp-rope' })); }
      var box = el('div', { class: 'pp-step' }); inner.appendChild(box);
      var next = render(step, box);
      var nav = el('div', { class: 'pp-nav' });
      if (st.i > 0) nav.appendChild(el('button', { type: 'button', class: 'pp-back', onclick: function () { st.i--; keep(); draw(); } }, '← Back'));
      if (step.id[0] !== '_' && step.type !== 'text') nav.appendChild(el('button', { type: 'button', class: 'pp-skip', onclick: function () { go(); } }, 'Skip'));
      if (next) { var b = el('button', { type: 'button', class: 'pp-next', onclick: function (e) { ripple(e, b); if (next() !== false) go(); } }, st.i === list.length - 1 ? 'Finish ✓' : 'Next →'); nav.appendChild(b); }
      root.appendChild(nav);
    }
    function go() { st.i++; keep(); draw(); var t = root.getBoundingClientRect().top; if (t < 0 || t > window.innerHeight * .6) root.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    function choose(btn, e, after) { ripple(e, btn); setTimeout(after, 260); }

    function render(s, box) {
      var a = st.a;
      var head = function () { if (s.q) box.appendChild(el('div', { class: 'pp-q' }, s.q)); if (s.help) box.appendChild(el('p', { class: 'pp-help' }, s.help)); if (s.helpHtml) box.appendChild(el('p', { class: 'pp-help', html: s.helpHtml })); };
      if (s.id === '_path') {
        box.appendChild(el('div', { class: 'pp-q' }, 'Do you already have solar?'));
        var o = el('div', { class: 'pp-opts' });
        [['solar', 'Not yet', 'Thinking about solar or a battery'], ['audit', 'Yes, I have solar', 'Let\'s check how it\'s doing']].forEach(function (p) {
          var b = el('button', { type: 'button', class: 'pp-opt', onclick: function (e) { b.classList.add('on'); choose(b, e, function () { st.path = p[0]; st.i = 0; keep(); draw(); }); } }, [p[1], el('small', {}, p[2])]);
          o.appendChild(b);
        });
        box.appendChild(o); return null;
      }
      if (s.id === '_contact') {
        box.appendChild(el('div', { class: 'pp-q' }, 'First, who are we talking to?'));
        box.appendChild(el('p', { class: 'pp-help' }, 'So your answers land with your appointment. A phone or an email is enough.'));
        var c = st.contact || {};
        var f = function (k, ph, type, ac) { var i = el('input', { class: 'pp-field', type: type || 'text', placeholder: ph, autocomplete: ac, value: c[k] || '' }); i.addEventListener('input', function () { c[k] = i.value.trim(); st.contact = c; keep(); }); return i; };
        box.appendChild(el('div', { class: 'pp-two' }, [f('first', 'First name', 'text', 'given-name'), f('last', 'Last name', 'text', 'family-name')]));
        box.appendChild(el('div', { class: 'pp-two' }, [f('phone', 'Mobile', 'tel', 'tel'), f('email', 'Email', 'email', 'email')]));
        var trap = el('input', { class: 'pp-hp', name: 'website', tabindex: '-1', autocomplete: 'off', style: 'position:absolute;left:-9999px', 'aria-hidden': 'true' });
        trap.addEventListener('input', function () { st.hp = trap.value; });   // only bots fill this
        box.appendChild(trap);
        var err = el('div', { class: 'pp-err' }); box.appendChild(err);
        return function () { if (!c.first || (!c.phone && !c.email)) { err.textContent = 'Please add your first name and a phone or email.'; return false; } st.contact = c; return true; };
      }
      head();
      if (s.type === 'one') {
        var o1 = el('div', { class: 'pp-opts' });
        s.opts.forEach(function (op) {
          var label = Array.isArray(op) ? op[0] : op, sub = Array.isArray(op) ? op[1] : null;
          var b = el('button', { type: 'button', class: 'pp-opt' + (a[s.id] === label ? ' on' : ''), onclick: function (e) { a[s.id] = label; o1.querySelectorAll('.pp-opt').forEach(function (x) { x.classList.remove('on'); }); b.classList.add('on'); keep(); choose(b, e, go); } }, [label, sub ? el('small', {}, sub) : null]);
          o1.appendChild(b);
        });
        box.appendChild(o1); return null;
      }
      if (s.type === 'many') {
        a[s.id] = a[s.id] || [];
        var o2 = el('div', { class: 'pp-opts' });
        s.opts.forEach(function (label) {
          var b = el('button', { type: 'button', class: 'pp-opt' + (a[s.id].indexOf(label) >= 0 ? ' on' : ''), onclick: function (e) { ripple(e, b); var i = a[s.id].indexOf(label); if (i >= 0) a[s.id].splice(i, 1); else a[s.id].push(label); b.classList.toggle('on'); keep(); } }, label);
          o2.appendChild(b);
        });
        box.appendChild(o2);
        if (s.other) { var ot = el('input', { class: 'pp-field', placeholder: 'Something else? (optional)', value: a[s.id + '_other'] || '', style: 'margin-top:10px' }); ot.addEventListener('input', function () { a[s.id + '_other'] = ot.value; keep(); }); box.appendChild(ot); }
        return function () { return true; };
      }
      if (s.type === 'tri') {
        a[s.id] = a[s.id] || {};
        var t = el('div', { class: 'pp-tri' });
        s.items.forEach(function (it) {
          var seg = el('div', { class: 'pp-seg' });
          [['no', 'No'], ['maybe', 'Possibly'], ['yes', '100% yes']].forEach(function (v) {
            var bb = el('button', { type: 'button', class: (a[s.id][it[0]] || 'no') === v[0] ? 'on ' + v[0] : v[0], onclick: function (e) { ripple(e, bb); a[s.id][it[0]] = v[0]; seg.querySelectorAll('button').forEach(function (x) { x.classList.remove('on'); }); bb.classList.add('on'); keep(); } }, v[1]);
            seg.appendChild(bb);
          });
          t.appendChild(el('div', { class: 'pp-tri-row' }, [el('span', {}, it[1]), seg]));
        });
        box.appendChild(t); return function () { return true; };
      }
      if (s.type === 'bill') {
        var v = a.bill || 250;
        var out = el('div', { class: 'pp-bill' }, '$' + v + (v >= 700 ? '+' : ''));
        var r = el('input', { class: 'pp-range', type: 'range', min: '50', max: '700', step: '10', value: v });
        r.addEventListener('input', function () { a.bill = +r.value; out.textContent = '$' + r.value + (+r.value >= 700 ? '+' : ''); keep(); });
        box.appendChild(out); box.appendChild(r);
        box.appendChild(el('div', { class: 'pp-ticks' }, [el('span', {}, '$50'), el('span', {}, '$250'), el('span', {}, '$450'), el('span', {}, '$700+')]));
        var up = el('input', { type: 'file', accept: 'image/*,application/pdf' });
        var lab = el('label', { class: 'pp-upload' }, [up, st.bill ? '✓ ' + st.bill.name : '📎 Add a photo of your bill (optional)']);
        up.addEventListener('change', function () { if (up.files[0]) { st.bill = up.files[0]; lab.lastChild.textContent = '✓ ' + up.files[0].name; } });
        box.appendChild(lab);
        return function () { a.bill = a.bill || +r.value; return true; };
      }
      if (s.type === 'installed') {
        a.installed = a.installed || {};
        var now = new Date().getFullYear(), sel = el('select', { class: 'pp-field' }, [el('option', { value: '' }, 'Year installed')]);
        for (var y = now; y >= 2000; y--) sel.appendChild(el('option', { value: y }, String(y)));
        sel.appendChild(el('option', { value: 'before 2000' }, 'Before 2000'));
        sel.value = a.installed.year || '';
        var mon = el('select', { class: 'pp-field' }, [el('option', { value: '' }, 'Month (if you know)')].concat(['January','February','March','April','May','June','July','August','September','October','November','December'].map(function (m) { return el('option', { value: m }, m); })));
        mon.value = a.installed.month || '';
        sel.addEventListener('change', function () { a.installed.year = sel.value; keep(); }); mon.addEventListener('change', function () { a.installed.month = mon.value; keep(); });
        box.appendChild(el('div', { class: 'pp-two' }, [sel, mon]));
        var ex = el('div', { class: 'pp-opts', style: 'margin-top:4px' });
        [['exact', 'That\'s exact'], ['guess', 'Best guess']].forEach(function (p) {
          var b = el('button', { type: 'button', class: 'pp-opt' + (a.installed.how === p[0] ? ' on' : ''), onclick: function (e) { ripple(e, b); a.installed.how = p[0]; ex.querySelectorAll('.pp-opt').forEach(function (x) { x.classList.remove('on'); }); b.classList.add('on'); keep(); } }, p[1]);
          ex.appendChild(b);
        });
        box.appendChild(ex); return function () { return true; };
      }
      if (s.type === 'textnot' || s.type === 'count') {
        var inp = el('input', { class: 'pp-field', type: s.type === 'count' ? 'number' : 'text', inputmode: s.type === 'count' ? 'numeric' : null, min: '1', placeholder: s.placeholder || 'Number of panels', value: (a[s.id] && a[s.id] !== s.notSure) ? a[s.id] : '' });
        inp.addEventListener('input', function () { a[s.id] = inp.value.trim(); keep(); });
        box.appendChild(inp);
        var ns = el('button', { type: 'button', class: 'pp-opt' + (a[s.id] === s.notSure ? ' on' : ''), style: 'width:auto', onclick: function (e) { a[s.id] = s.notSure; inp.value = ''; ns.classList.add('on'); keep(); choose(ns, e, go); } }, s.notSure);
        box.appendChild(ns); return function () { return true; };
      }
      if (s.type === 'paid') {
        a.paid = a.paid || {};
        var o3 = el('div', { class: 'pp-opts' });
        ['Paid in cash', 'Loan', 'Lease', 'PPA (pay for the power)', 'Prepaid lease / PPA', 'Not sure'].forEach(function (label) {
          var b = el('button', { type: 'button', class: 'pp-opt' + (a.paid.how === label ? ' on' : ''), onclick: function (e) { ripple(e, b); a.paid.how = label; o3.querySelectorAll('.pp-opt').forEach(function (x) { x.classList.remove('on'); }); b.classList.add('on'); keep(); drawStill(); } }, label);
          o3.appendChild(b);
        });
        box.appendChild(o3);
        var still = el('div', { style: 'margin-top:14px' }); box.appendChild(still);
        function drawStill() {
          still.innerHTML = '';
          if (!a.paid.how || a.paid.how === 'Paid in cash') return;
          still.appendChild(el('div', { class: 'pp-q', style: 'font-size:16px' }, 'Still paying for it?'));
          var o4 = el('div', { class: 'pp-opts' });
          ['Yes', 'No, it\'s paid off', 'Not sure'].forEach(function (label) {
            var b = el('button', { type: 'button', class: 'pp-opt' + (a.paid.still === label ? ' on' : ''), onclick: function (e) { ripple(e, b); a.paid.still = label; o4.querySelectorAll('.pp-opt').forEach(function (x) { x.classList.remove('on'); }); b.classList.add('on'); keep(); } }, label);
            o4.appendChild(b);
          });
          still.appendChild(o4);
          var m = el('input', { class: 'pp-field', type: 'number', inputmode: 'decimal', placeholder: 'Monthly payment, if you know ($)', value: a.paid.monthly || '', style: 'margin-top:9px' });
          m.addEventListener('input', function () { a.paid.monthly = m.value; keep(); });
          still.appendChild(m);
        }
        drawStill(); return function () { return true; };
      }
      if (s.type === 'text') {
        var ta = el('textarea', { class: 'pp-field', rows: '4', placeholder: s.help || '' });
        ta.value = a[s.id] || '';
        ta.addEventListener('input', function () { a[s.id] = ta.value; keep(); });
        box.appendChild(ta); return function () { return true; };
      }
      return function () { return true; };
    }

    /** [question, answer] pairs for David's note — readable in Zoho. */
    function summary() {
      var rows = [], a = st.a;
      Q[st.path].forEach(function (s) {
        var v = a[s.id], txt = '';
        if (s.type === 'bill' && v) txt = '$' + v + '/month' + (st.bill ? ' (bill photo attached)' : '');
        else if (s.type === 'tri' && v) { var yes = [], maybe = []; s.items.forEach(function (it) { if (v[it[0]] === 'yes') yes.push(it[1]); else if (v[it[0]] === 'maybe') maybe.push(it[1]); });
          txt = [yes.length ? '100% yes: ' + yes.join(', ') : '', maybe.length ? 'Possibly: ' + maybe.join(', ') : ''].filter(Boolean).join(' · ') || 'None planned'; }
        else if (s.type === 'many' && v && v.length) txt = v.join(', ') + (a[s.id + '_other'] ? ' · Other: ' + a[s.id + '_other'] : '');
        else if (s.type === 'installed' && v && v.year) txt = (v.month ? v.month + ' ' : '') + v.year + (v.how === 'exact' ? ' (exact)' : v.how === 'guess' ? ' (best guess)' : '');
        else if (s.type === 'paid' && v && v.how) txt = v.how + (v.still ? ' · still paying: ' + v.still : '') + (v.monthly ? ' · $' + v.monthly + '/month' : '');
        else if (typeof v === 'string' && v.trim()) txt = v.trim();
        if (txt) rows.push([s.q, txt]);
      });
      return rows;
    }

    function finish(inner) {
      var surf = st.path !== 'audit';
      inner.appendChild(el('div', { class: 'pp-kicker' }, surf ? 'Sending your answers…' : 'Logging it…'));
      var fd = new FormData();
      fd.append('data', JSON.stringify({ path: st.path, ref: opt.ref || null, t: opt.token || null, contact: st.contact || null, answers: st.a, summary: summary() }));
      if (st.bill) fd.append('bill', st.bill);
      fd.append('website', st.hp || '');
      fetch('/prep.php', { method: 'POST', body: fd }).then(function (r) { return r.json(); }).catch(function () { return { ok: false }; }).then(function (j) {
        inner.innerHTML = '';
        if (!j || !j.ok) {
          inner.appendChild(el('div', { class: 'pp-q' }, 'That didn\'t send.'));
          inner.appendChild(el('p', { class: 'pp-help' }, (j && j.error) || 'Check your connection and try again — or call or text (858) 786-2204.'));
          inner.appendChild(el('button', { type: 'button', class: 'pp-next', style: 'margin:0', onclick: function () { st.i = steps().length - 1; draw(); } }, 'Try again'));
          return;
        }
        try { localStorage.removeItem(store); } catch (e) {}
        if (surf) wash(); else root.classList.add('arrived');
        if (!surf) { var log = el('div', { class: 'pp-log', html: '<div class="sea"></div><div class="beam"></div><div class="light">' + LIGHT + '</div><div class="boat" style="left:88%">' + BOAT + '</div>' }); inner.appendChild(log); }
        var d = el('div', { class: 'pp-done' });
        d.appendChild(el('div', { class: 'pp-kicker' }, surf ? 'You\'re all set' : 'Logged · welcome aboard'));
        d.appendChild(el('div', { class: 'pp-q' }, surf ? 'Thanks — that saves us a lot of time.' : 'Thanks — your system\'s logbook is ready.'));
        d.appendChild(el('p', { class: 'pp-help' }, j.texted ? 'We just texted you a link to pick a time. Reply to that text anytime.' : 'David will reach out to confirm. Or pick a time now:'));
        var acts = el('div', { class: 'pp-actions' });
        if (!opt.booked) acts.appendChild(el('a', { class: 'pp-act main', href: j.calendly, target: '_blank', rel: 'noopener' }, [el('span', { class: 'ic' }, '📅'), el('span', {}, ['Pick a time with David', el('small', {}, '20–45 minutes')])]));
        if (j.lumaria) acts.appendChild(el('a', { class: 'pp-act', href: j.lumaria, target: '_blank', rel: 'noopener' }, [el('span', { class: 'ic' }, '⚓'), el('span', {}, ['Come aboard the lumaria app', el('small', {}, 'Free — see what your system is really saving')])]));
        acts.appendChild(el('a', { class: 'pp-act', href: 'sms:+18587862204' }, [el('span', { class: 'ic' }, '💬'), el('span', {}, ['Text David', el('small', {}, '858-SUN-2204')])]));
        d.appendChild(acts); inner.appendChild(d);
      });
    }
    draw();
  }

  /**
   * offer(host, opt) — after booking on this site: the same before-we-meet form the rep app
   * sends, on lumaria.energy (Luma asks; utility from their address; an audit carries on into
   * the lumaria sign-up with the answers filled in). prep.php asks the rep platform for this
   * lead's link; if that can't be had, the questions run right here (mount) as before.
   */
  function offer(host, opt) {
    opt = opt || {};
    if (!opt.ref) return mount(host, opt);
    css();
    var done = false;
    var fallback = function () { if (!done) { done = true; mount(host, opt); } };
    var timer = setTimeout(fallback, 7000);
    fetch('/prep.php?link=' + encodeURIComponent(opt.ref) + '&kind=' + (opt.path === 'audit' ? 'audit' : 'solar'))
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (done) return;
        if (!j || !j.ok || !j.url) return fallback();
        done = true; clearTimeout(timer);
        var surf = opt.path !== 'audit';
        var first = opt.contact && opt.contact.first ? opt.contact.first : '';
        var root = el('div', { class: 'pp ' + (surf ? 'pp-surf' : 'pp-ship') });
        if (surf) { root.appendChild(el('div', { class: 'pp-sun', 'aria-hidden': 'true' })); root.insertAdjacentHTML('beforeend', swellHtml); }
        else root.appendChild(el('div', { class: 'pp-hull', 'aria-hidden': 'true' }));
        var inner = el('div', { class: 'pp-in' });
        inner.appendChild(el('div', { class: 'pp-kicker' }, surf ? 'Before we meet · about a minute' : 'Come aboard · your system\'s logbook'));
        inner.appendChild(el('div', { class: 'pp-title' }, (first ? first + ', ' : '') + (surf ? 'make our time all about you.' : 'let\'s log your system.')));
        inner.appendChild(el('p', { class: 'pp-help' }, surf
          ? 'Luma, David\'s assistant, asks a few quick questions — your bill, your roof, what you\'d want backed up — so your options are ready when we meet.'
          : 'Luma, David\'s assistant, asks a few quick questions about your system, then sets you up in lumaria, free, with what you told her already filled in.'));
        var acts = el('div', { class: 'pp-actions' });
        // The address people see says Your Energy Best; .htaccess forwards it to the form on lumaria.energy.
        var shown = j.url.replace(/^https:\/\/www\.lumaria\.energy\/prep\/(solar|audit)\?/, 'https://yourenergybest.com/prep/$1/?');
        acts.appendChild(el('a', { class: 'pp-act main', href: shown, target: '_blank', rel: 'noopener' },
          [el('span', { class: 'ic' }, surf ? '🌊' : '⚓'), el('span', {}, [surf ? 'Answer a few quick questions' : 'Log my system with Luma', el('small', {}, 'About a minute · Your Energy Best')])]));
        inner.appendChild(acts);
        root.appendChild(inner);
        host.innerHTML = ''; host.appendChild(root);
      })
      .catch(fallback);
  }

  window.LumariaPrep = { mount: mount, offer: offer };
})();
