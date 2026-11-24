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
  var orig = window.fetch;
  window.fetch = function (url, opts) {
    var u = typeof url === 'string' ? url : (url && url.url) || '';
    opts = opts || {};
    if (!/\/(zoho|prep)\.php(\?|$)/.test(u) || String(opts.method || 'GET').toUpperCase() !== 'POST') return orig.apply(this, arguments);
    var self = this, args = arguments;
    return getToken().then(function (t) {
      var h = new Headers(opts.headers || {}); if (t) h.set('X-Turnstile', t);
      return orig.call(self, url, Object.assign({}, opts, { headers: h }));
    });
  };
})();

