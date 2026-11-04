/* audit-book.js — the audit booking flow for /locals-audit/ (flyer QR, fee waived)
   and /audit/ (standard, $219 or the free lumaria app).

   Step 1  name · address · phone or email · why — sent to Zoho as a Lead right
           away, so David has it whatever they pick next.
   Step 2  /locals-audit/: book a time, text, call, or email (drafts written for them)
           /audit/:       pay the audit fee then book, or use the free lumaria app
   Done    "get the lumaria app" (or "coming soon" until APP_LIVE is true)

   The page sets window.AUDIT_PAGE = { mode: 'local' | 'paid', source, leadSource }. */
(function () {
  'use strict';

  // ─── EDIT ME ───
  var DAVID = { name: 'David', phone: '3039419131', phoneShown: '(303) 941-9131', email: 'dwest@yourenergybest.com' };
  // David's 45-min "Solar Consultation" on purpose: it blocks both calendars for the
  // full window; the page tells people the audit takes 20–45 min.
  var CALENDLY = 'https://calendly.com/yourenergybest/energy';
  var APP_URL = 'https://www.lumaria.energy/';
  var APP_LIVE = false;          // true once the lumaria app is launched to the public
  var PLAN = 'savings-audit';    // plans.php — $219

  var cfg = window.AUDIT_PAGE || { mode: 'local', source: 'LocalsAudit', leadSource: 'Website - Locals Audit' };
  var $ = function (s) { return document.querySelector(s); };
  var loadTs = Math.floor(Date.now() / 1000);
  var lead = null;   // what they entered, once step 1 is done

  function show(id) {
    ['#stepForm', '#stepChoose', '#stepDone'].forEach(function (s) { var el = $(s); if (el) el.hidden = s !== id; });
    var el = $(id); if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
  function save(l) { try { sessionStorage.setItem('audit_lead', JSON.stringify(l)); } catch (e) {} }
  function restore() { try { return JSON.parse(sessionStorage.getItem('audit_lead') || 'null'); } catch (e) { return null; } }

  // The message a homeowner sends David (text or email), written from what they entered
  function draft(l) {
    var lines = [
      'Hi David, ' + (cfg.mode === 'local' ? 'I got your solar flyer and would like my free solar savings audit.' : 'I would like to set up a solar savings audit.'),
      '', 'Name: ' + l.first + ' ' + l.last, 'Address: ' + l.street + (l.zip ? ', ' + l.zip : '')
    ];
    if (l.phone) lines.push('Phone: ' + l.phone);
    if (l.email) lines.push('Email: ' + l.email);
    if (l.reason) lines.push('Why: ' + l.reason);
    if (l.notes) lines.push('', l.notes);
    lines.push('', 'What times work for you?');
    return lines.join('\n');
  }

  // ─── Step 1: the form ───
  var form = $('#auditForm');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var v = function (n) { return (form.elements[n] && form.elements[n].value || '').trim(); };
      var reasons = [].slice.call(form.querySelectorAll('input[name=reason]:checked')).map(function (c) { return c.value; });
      var l = { first: v('first'), last: v('last'), street: v('street'), zip: v('zip'), phone: v('phone'), email: v('email'),
                reason: reasons.join(', '), notes: v('notes') };
      var err = $('#formErr');
      var bad = !l.first ? 'Please add your first name.' : !l.last ? 'Please add your last name.'
        : !l.street ? 'Please add your home address.'
        : (!l.phone && !l.email) ? 'Please add a phone number or an email, so David can reach you.'
        : (l.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(l.email)) ? 'That email doesn’t look right.'
        : (l.phone && l.phone.replace(/\D/g, '').length < 10) ? 'Please add a 10-digit phone number.' : '';
      if (bad) { err.textContent = bad; err.hidden = false; return; }
      err.hidden = true;

      var btn = form.querySelector('button[type=submit]');
      btn.disabled = true; btn.textContent = 'One moment…';
      var attr = window.yebAttribution ? window.yebAttribution() : {};
      var payload = Object.assign({
        action: 'lead', source_form: cfg.source, lead_source: cfg.leadSource,
        opportunity_type: /battery/i.test(l.reason) ? 'Solar Owner – Add Battery' : 'Solar Owner – Audit / Review',
        first_name: l.first, last_name: l.last, street: l.street, zip: l.zip, phone: l.phone, email: l.email,
        reason: l.reason, notes: l.notes,
        customer_option: cfg.mode === 'local' ? 'Free solar savings audit (locals audit)' : 'Solar savings audit',
        fee_note: cfg.mode === 'local' ? 'Audit fee WAIVED (locals audit)' : '',
        website: form.elements.website ? form.elements.website.value : '', _ts: loadTs
      }, attr);
      fetch('/zoho.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
        .catch(function () {})   // never block them: the text/email/booking still reach David
        .then(function () {
          lead = l; save(l);
          btn.disabled = false; btn.textContent = 'Continue →';
          fillChoices(l);
          show('#stepChoose');
        });
    });
  }

  // ─── Step 2: how they want to set it up ───
  function fillChoices(l) {
    var body = draft(l);
    var sms = $('#optText'); if (sms) sms.href = 'sms:+1' + DAVID.phone + (/iPhone|iPad|Mac/i.test(navigator.userAgent) ? '&' : '?') + 'body=' + encodeURIComponent(body);
    var call = $('#optCall'); if (call) call.href = 'tel:+1' + DAVID.phone;
    var mail = $('#optEmail');
    if (mail) mail.href = 'mailto:' + DAVID.email + '?subject=' + encodeURIComponent((cfg.mode === 'local' ? 'Free solar audit (locals) — ' : 'Solar audit — ') + l.first + ' ' + l.last)
      + '&body=' + encodeURIComponent(body);
    var who = $('#chooseName'); if (who) who.textContent = l.first;
  }
  // Texting, calling or emailing opens their phone's app; when they come back, show the finish
  ['#optText', '#optCall', '#optEmail'].forEach(function (s) {
    var el = $(s); if (el) el.addEventListener('click', function () { setTimeout(function () { show('#stepDone'); }, 400); });
  });

  function calendlyUrl(l) {
    var q = new URLSearchParams({ name: l.first + ' ' + l.last, hide_gdpr_banner: '1' });
    if (l.email) q.set('email', l.email);
    // Shows up on the Calendly booking for David; the first custom question if the event has one
    q.set('a1', [l.street + (l.zip ? ' ' + l.zip : ''), l.phone, l.reason].filter(Boolean).join(' · '));
    return CALENDLY + '?' + q.toString();
  }
  function openCalendly(l) {
    var box = $('#calendlyBox');
    if (!box) { window.open(calendlyUrl(l), '_blank', 'noopener'); return; }
    box.hidden = false;
    box.innerHTML = '';
    var frame = document.createElement('iframe');
    frame.src = calendlyUrl(l) + '&embed_domain=' + location.hostname + '&embed_type=Inline';
    frame.title = 'Pick a time with David';
    frame.loading = 'lazy';
    box.appendChild(frame);
    var after = document.createElement('p');
    after.className = 'after';
    after.innerHTML = 'Booked? <a href="#" id="bookedLink">Finish up →</a>';
    box.appendChild(after);
    $('#bookedLink').addEventListener('click', function (e) { e.preventDefault(); show('#stepDone'); });
    box.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
  // Calendly tells the page when a booking is made
  window.addEventListener('message', function (e) {
    if (e.origin === 'https://calendly.com' && e.data && e.data.event === 'calendly.event_scheduled') setTimeout(function () { show('#stepDone'); }, 1200);
  });

  var book = $('#optBook');
  if (book) book.addEventListener('click', function (e) { e.preventDefault(); openCalendly(lead || restore() || {}); });

  // /audit/: pay first, then book
  var pay = $('#optPay');
  if (pay) pay.addEventListener('click', function (e) {
    e.preventDefault();
    var l = lead || restore();
    if (!l) return show('#stepForm');
    var email = l.email;
    if (!email) {
      email = (prompt('Stripe needs an email for your receipt:') || '').trim();
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return;
      l.email = email; save(l);
    }
    pay.textContent = 'Opening secure checkout…';
    var attr = window.yebAttribution ? window.yebAttribution() : {};
    fetch('/stripe-checkout.php', { method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(Object.assign({ plan: PLAN, name: l.first + ' ' + l.last, email: email, phone: l.phone, zip: l.zip,
        opp: 'Solar Owner – Audit / Review', return: '/audit/' }, attr)) })
      .then(function (r) { return r.json(); })
      .then(function (d) { if (d.url) location.href = d.url; else throw new Error(d.error); })
      .catch(function () { pay.textContent = 'Pay & book'; alert('Checkout could not start. Call or text ' + DAVID.phoneShown + '.'); });
  });

  // Back from Stripe on /audit/: confirm payment, then straight to booking
  var params = new URLSearchParams(location.search);
  if (params.get('session_id')) {
    fetch('/stripe-verify.php?session_id=' + encodeURIComponent(params.get('session_id')))
      .then(function (r) { return r.json(); })
      .then(function (d) {
        history.replaceState({}, '', location.pathname);
        if (!d.paid) return;
        lead = restore() || { first: (d.name || '').split(' ')[0], last: (d.name || '').split(' ').slice(1).join(' '), email: d.email, street: '' };
        var paid = $('#paidNote'); if (paid) paid.hidden = false;
        fillChoices(lead);
        show('#stepChoose');
        openCalendly(lead);
      }).catch(function () {});
  } else if (params.get('canceled')) {
    history.replaceState({}, '', location.pathname);
    lead = restore(); if (lead) { fillChoices(lead); show('#stepChoose'); }
  }

  // ─── Done: the lumaria app ───
  var app = $('#appPrompt');
  if (app) {
    if (APP_LIVE) {
      app.innerHTML = '<h3>One more thing: get the lumaria app</h3><p>Add your system details, bills and monitoring before the audit, so David walks in already knowing your system.</p>'
        + '<a class="btn btn--primary" href="' + APP_URL + '">Open the lumaria app →</a>';
    } else {
      app.innerHTML = '<h3>Coming soon: the lumaria app</h3><p>A free app for solar owners: your real savings, your rates, and a place to keep your bills and system details. David will send you the link when it launches.</p>';
    }
  }

  var appDoor = $('#appDoor');
  if (appDoor) { appDoor.href = APP_URL; if (!APP_LIVE) appDoor.querySelector('.tag').textContent = 'Launching soon'; }
})();
