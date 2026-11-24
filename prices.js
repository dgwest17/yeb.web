/* prices.js — writes the live price list (/plans.php) into the page.
   Mark any price on a page with data-price-of="<plan key>", e.g.
     <span data-price-of="full">$219</span>
   The typed number is the fallback; this replaces it with what Stripe will charge.
   Cards with data-plan + data-price (used in the Zoho note) get the number too. */
(function () {
  'use strict';
  fetch('/plans.php').then(function (r) { return r.json(); }).then(function (plans) {
    function money(n) { return '$' + (n % 1 ? n.toFixed(2) : n.toLocaleString('en-US')); }
    document.querySelectorAll('[data-price-of]').forEach(function (el) {
      var p = plans[el.getAttribute('data-price-of')];
      if (!p) return;
      if (p.free) {   // limited-time free: the regular price struck through, then Free
        el.innerHTML = '<s style="opacity:.7">' + money(p.price) + '</s> <b style="color:#06A77D">' + (document.documentElement.lang === 'es' ? 'Gratis' : 'Free') + '</b>';
        var until = new Date(p.free_until + 'T12:00:00');
        el.title = document.documentElement.lang === 'es' ? 'Gratis hasta el ' + until.toLocaleDateString('es-US', { month: 'long', day: 'numeric' }) : 'Free through ' + until.toLocaleDateString('en-US', { month: 'long', day: 'numeric' });
      } else el.textContent = money(p.price);
    });
    document.querySelectorAll('[data-plan][data-price]').forEach(function (el) {
      var p = plans[el.getAttribute('data-plan')];
      if (p) el.setAttribute('data-price', String(p.price));
    });
  }).catch(function () {});
})();
