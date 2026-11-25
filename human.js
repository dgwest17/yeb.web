/* human.js — loaded by nav.js on every page (and directly by pages without nav.js, like /build/).
   Server side: human-check.php. */
// ─── HUMAN CHECK (Cloudflare Turnstile) for the lead forms ───
// Every POST to /zoho.php or /prep.php gets an X-Turnstile header from one invisible widget.
// It only shows a checkbox when Cloudflare isn't sure. If no token arrives in a few seconds the
// form still sends: the server holds and emails it rather than losing a real person.
(function humanCheck() {
  if (location.pathname.indexOf('/admin') === 0 || window.__yebHuman) return;
  window.__yebHuman = true;
  var KEY = '0x4AAAAAAGJ0iCXjMOv6QHsv', box = null, wid = null, token = '', waiting = [], started = false;
  function start() {
    if (started) return; started = true;
    box = document.createElement('div');
    box.style.cssText = 'position:fixed;left:12px;bottom:84px;z-index:9997';
    document.body.appendChild(box);
    var s = document.createElement('script');
    s.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit'; s.async = true;
    s.onload = function () {
      wid = window.turnstile.render(box, { sitekey: KEY, appearance: 'interaction-only', action: 'lead',
        callback: function (t) { token = t; waiting.splice(0).forEach(function (f) { f(t); }); },
        'expired-callback': function () { token = ''; window.turnstile.reset(wid); },
        'error-callback': function () { token = ''; return true; } });
    };
    document.head.appendChild(s);
  }
  function getToken() {
    start();
    if (token) { var t = token; token = ''; setTimeout(function () { try { window.turnstile.reset(wid); } catch (e) {} }, 0); return Promise.resolve(t); }
    return new Promise(function (ok) {
      var done = false, give = function (t) { if (done) return; done = true; token = ''; try { window.turnstile.reset(wid); } catch (e) {} ok(t || ''); };
      waiting.push(give); setTimeout(function () { give(''); }, 6000);
    });
  }
  // Warm up as soon as someone starts typing, so the token is ready by Submit
  document.addEventListener('focusin', function (e) { if (e.target && /^(INPUT|TEXTAREA|SELECT)$/.test(e.target.tagName)) start(); });
  // ─── TEXT OPT-IN (A2P 10DLC): an unticked box under each lead form's phone field ───
  // Shown only where a form has a phone field; the answer rides along as texts_ok on the post to
  // zoho.php, which notes it on the Lead. Wording follows carrier rules (rates, frequency, STOP, HELP).
  function addOptIns() {
    document.querySelectorAll('input[type=tel]').forEach(function (tel) {
      if (tel.hasAttribute('data-no-optin') || tel.closest('.yeb-optin-done')) return;
      // Below the whole row when the phone sits beside another field (e.g. Mobile | Email)
      var host = tel.closest('.two, .row, .form-row, .grid-2') || tel.closest('label') || tel;
      host.classList.add('yeb-optin-done');
      var lab = document.createElement('label');
      lab.className = 'yeb-optin';
      lab.style.cssText = 'display:flex;gap:8px;align-items:flex-start;margin:8px 0 4px;font-size:.82rem;line-height:1.4;opacity:.85;font-weight:400;text-transform:none;letter-spacing:0;cursor:pointer;grid-column:1/-1';
      lab.innerHTML = '<input type="checkbox" name="texts_ok" value="1" style="margin-top:3px;width:auto;flex:none"> <span>OK to text me about my request. Msg &amp; data rates may apply; frequency varies. Reply STOP to opt out, HELP for help. <a href="https://www.lumaria.energy/sms/" target="_blank" rel="noopener" style="color:inherit;text-decoration:underline">Texting terms</a></span>';
      host.parentNode.insertBefore(lab, host.nextSibling);
    });
  }
  function optedIn() { return !!document.querySelector('.yeb-optin input:checked'); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', addOptIns); else addOptIns();
  new MutationObserver(function () { addOptIns(); }).observe(document.documentElement, { childList: true, subtree: true });

  var orig = window.fetch;
  window.fetch = function (url, opts) {
    var u = typeof url === 'string' ? url : (url && url.url) || '';
    opts = opts || {};
    if (!/\/(zoho|prep)\.php(\?|$)/.test(u) || String(opts.method || 'GET').toUpperCase() !== 'POST') return orig.apply(this, arguments);
    var self = this, args = arguments;
    return getToken().then(function (t) {
      var h = new Headers(opts.headers || {}); if (t) h.set('X-Turnstile', t);
      var body = opts.body;
      if (/zoho\.php/.test(u) && typeof body === 'string' && optedIn()) {
        try { var j = JSON.parse(body); j.texts_ok = true; body = JSON.stringify(j); } catch (e) {}
      }
      return orig.call(self, url, Object.assign({}, opts, { headers: h, body: body }));
    });
  };
})();

