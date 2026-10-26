/* prices.js — writes the live price list (/plans.php) into the page.
   Mark any price on a page with data-price-of="<plan key>", e.g.
     <span data-price-of="audit">$129</span>
   The typed number is the fallback; this replaces it with what Stripe will charge.
   Cards with data-plan + data-price (used in the Zoho note) get the number too. */
(function () {
  'use strict';
  fetch('/plans.php').then(function (r) { return r.json(); }).then(function (plans) {
    function money(n) { return '$' + (n % 1 ? n.toFixed(2) : n.toLocaleString('en-US')); }
    document.querySelectorAll('[data-price-of]').forEach(function (el) {
      var p = plans[el.getAttribute('data-price-of')];
      if (p) el.textContent = money(p.price);
    });
    document.querySelectorAll('[data-plan][data-price]').forEach(function (el) {
      var p = plans[el.getAttribute('data-plan')];
      if (p) el.setAttribute('data-price', String(p.price));
    });
  }).catch(function () {});
})();
