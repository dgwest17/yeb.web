/* ═══════════════════════════════════════════════════════════
   nav.js — Your Energy Best Global Header
   ONE file controls the header on every page.
   To change the menu: edit LINKS below, upload this file. Done.

   Usage on any page:
     <script src="/nav.js" defer></script>   (in <head>)
   The script removes any legacy nav markup, injects the
   standardized header + overlay, and binds all mobile logic.
   Fresh "yebnav" class namespace = legacy CSS can't break it.
   ═══════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  // ─── EDIT ME: menu order ───
  var LINKS = [
    { label: 'Home',             href: '/' },
    { label: 'Options',          href: '/options/' },
    { label: 'Knowledge Center', href: '/knowledge/' },
    { label: 'Services',         href: '/services/' },
    { label: 'Get a Quote',      href: '/quote/' },
    { label: 'Build With Us',    href: '/build/' },
    { label: 'Blog',             href: '/blog.html' }
  ];
  var PHONE_DISPLAY = '(760) 860-7862';
  var PHONE_TEL     = 'tel:7608607862';
  var CTA_LABEL     = 'Get Your Custom Quote';
  var CTA_HREF      = '/quote/';
  var LOGO_SRC      = '/img/logo.png';

  // ─── Active link detection ───
  function isActive(href) {
    var p = location.pathname.replace(/\/index\.html$/, '/');
    if (href === '/') return p === '/' || p === '/index.html';
    return p === href || p.indexOf(href.replace(/\/$/, '') + '/') === 0 || p === href.replace(/\/$/, '');
  }

  // ─── CSS (injected) ───
  var CSS = ''
  + '.yebnav{position:fixed;top:0;left:0;right:0;z-index:9999;padding:.5rem 2rem;transition:background .3s ease;font-family:"Outfit",-apple-system,BlinkMacSystemFont,sans-serif}'
  + '.yebnav--scrolled{background:rgba(2,48,71,.95);-webkit-backdrop-filter:blur(10px);backdrop-filter:blur(10px);box-shadow:0 2px 20px rgba(0,0,0,.25)}'
  + '.yebnav__inner{max-width:1280px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:1rem;position:relative}'
  + '.yebnav__logo img{height:64px;width:auto;display:block;transition:height .3s}'
  + '.yebnav--scrolled .yebnav__logo img{height:52px}'
  + '.yebnav__phone{display:flex;align-items:center;gap:.4rem;color:rgba(255,255,255,.7);text-decoration:none;font-size:.85rem;font-weight:500;white-space:nowrap;margin-left:auto}'
  + '.yebnav__phone:hover{color:#FFB703}'
  + '.yebnav__cta-mobile{display:none}'
  + '.yebnav__toggle{display:none;background:none;border:none;cursor:pointer;width:32px;height:24px;position:relative;z-index:10001;padding:0;flex-shrink:0}'
  + '.yebnav__toggle span{display:block;position:absolute;left:0;right:0;height:2.5px;background:#fff;border-radius:2px;transition:all .3s ease}'
  + '.yebnav__toggle span:nth-child(1){top:0}.yebnav__toggle span:nth-child(2){top:10px}.yebnav__toggle span:nth-child(3){top:20px}'
  + '.yebnav__toggle.active span:nth-child(1){top:10px;transform:rotate(45deg)}'
  + '.yebnav__toggle.active span:nth-child(2){opacity:0}'
  + '.yebnav__toggle.active span:nth-child(3){top:10px;transform:rotate(-45deg)}'
  + '.yebnav__links{display:flex;list-style:none;gap:.25rem;align-items:center;margin:0;padding:0}'
  + '.yebnav__links li{list-style:none}'
  + '.yebnav__links a{color:rgba(255,255,255,.8);text-decoration:none;font-size:.9rem;font-weight:500;padding:.5rem .75rem;border-radius:6px;transition:color .2s,background .2s;white-space:nowrap;display:inline-block}'
  + '.yebnav__links a:hover,.yebnav__links a.active{color:#fff;background:rgba(255,255,255,.08)}'
  + '.yebnav__links-phone,.yebnav__links-cta{display:none}'
  + '.yebnav__overlay{display:none;position:fixed;inset:0;z-index:9998;background:rgba(0,0,0,.6);opacity:0;transition:opacity .3s}'
  + '.yebnav__overlay.open{display:block;opacity:1}'
  + 'body.yebnav-open{overflow:hidden}'
  + '@media(max-width:900px){'
  +   '.yebnav{padding:.5rem 1rem}'
  +   '.yebnav__logo img{height:48px}'
  +   '.yebnav--scrolled .yebnav__logo img{height:44px}'
  +   '.yebnav__toggle{display:block}'
  +   '.yebnav__phone span{display:none}'
  +   '.yebnav__phone{padding:.4rem;margin-left:auto}'
  +   '.yebnav__cta-mobile{display:inline-block;background:#FFB703;color:#023047;padding:.5rem 1rem;border-radius:100px;font-weight:700;font-size:.8rem;text-decoration:none;white-space:nowrap;margin-right:.5rem}'
  +   '.yebnav__links{position:fixed;top:0;right:0;width:280px;max-width:80vw;height:100vh;background:linear-gradient(180deg,#023047 0%,#011627 100%);flex-direction:column;align-items:stretch;padding:5rem 1.5rem 2rem;gap:0;transform:translateX(100%);transition:transform .35s cubic-bezier(.16,1,.3,1);z-index:10000;box-shadow:-4px 0 30px rgba(0,0,0,.3);overflow-y:auto}'
  +   '.yebnav__links.open{transform:translateX(0)}'
  +   '.yebnav__links li{display:block;border-bottom:1px solid rgba(255,255,255,.06);width:100%}'
  +   '.yebnav__links li a{display:block;padding:1rem .75rem;font-size:1.05rem;border-radius:0;color:rgba(255,255,255,.8);width:100%}'
  +   '.yebnav__links li a:hover,.yebnav__links li a.active{color:#FFB703;background:rgba(255,183,3,.06)}'
  +   '.yebnav__links-phone{display:block;margin-top:.5rem;border-top:1px solid rgba(255,255,255,.1)}'
  +   '.yebnav__links-phone a{color:#FFB703;font-weight:600}'
  +   '.yebnav__links-cta{display:block;border-bottom:none;padding:.5rem 0}'
  +   '.yebnav__links-cta a{background:#FFB703;color:#023047;font-weight:700;text-align:center;border-radius:100px;padding:.85rem 1rem;margin-top:.5rem}'
  + '}';

  function build() {
    // 1) Remove ANY legacy nav so we never get duplicates
    ['#mainNav', '.nav[role="navigation"]', '#navOverlay', '.nav__overlay', '.top-nav', '.yebnav', '.yebnav__overlay']
      .forEach(function (sel) {
        document.querySelectorAll(sel).forEach(function (el) { el.remove(); });
      });

    // 2) Inject CSS
    var style = document.createElement('style');
    style.id = 'yebnav-css';
    style.textContent = CSS;
    document.head.appendChild(style);

    // 3) Build markup
    var linksHtml = LINKS.map(function (l) {
      return '<li><a href="' + l.href + '"' + (isActive(l.href) ? ' class="active"' : '') + '>' + l.label + '</a></li>';
    }).join('');

    var nav = document.createElement('nav');
    nav.className = 'yebnav';
    nav.id = 'yebNav';
    nav.setAttribute('role', 'navigation');
    nav.setAttribute('aria-label', 'Main navigation');
    nav.innerHTML =
      '<div class="yebnav__inner">'
      + '<a href="/" class="yebnav__logo" aria-label="Your Energy Best – Home"><img src="' + LOGO_SRC + '" alt="Your Energy Best"></a>'
      + '<a href="' + PHONE_TEL + '" class="yebnav__phone" aria-label="Call us">'
      +   '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.12.96.36 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.34 1.85.58 2.81.7A2 2 0 0122 16.92z"/></svg>'
      +   '<span>' + PHONE_DISPLAY + '</span>'
      + '</a>'
      + '<a href="' + CTA_HREF + '" class="yebnav__cta-mobile">' + CTA_LABEL + '</a>'
      + '<button class="yebnav__toggle" id="yebNavToggle" aria-label="Open menu" aria-expanded="false" aria-controls="yebNavLinks"><span></span><span></span><span></span></button>'
      + '<ul class="yebnav__links" id="yebNavLinks">'
      +   linksHtml
      +   '<li class="yebnav__links-phone"><a href="' + PHONE_TEL + '">📞 ' + PHONE_DISPLAY + '</a></li>'
      +   '<li class="yebnav__links-cta"><a href="' + CTA_HREF + '">' + CTA_LABEL + ' →</a></li>'
      + '</ul>'
      + '</div>';

    var overlay = document.createElement('div');
    overlay.className = 'yebnav__overlay';
    overlay.id = 'yebNavOverlay';
    overlay.setAttribute('aria-hidden', 'true');

    document.body.insertBefore(overlay, document.body.firstChild);
    document.body.insertBefore(nav, document.body.firstChild);

    // 4) Logic
    var toggle = document.getElementById('yebNavToggle');
    var links  = document.getElementById('yebNavLinks');

    function openMenu() {
      links.classList.add('open');
      overlay.classList.add('open');
      toggle.classList.add('active');
      toggle.setAttribute('aria-expanded', 'true');
      toggle.setAttribute('aria-label', 'Close menu');
      document.body.classList.add('yebnav-open');
    }
    function closeMenu() {
      links.classList.remove('open');
      overlay.classList.remove('open');
      toggle.classList.remove('active');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', 'Open menu');
      document.body.classList.remove('yebnav-open');
    }

    toggle.addEventListener('click', function () {
      links.classList.contains('open') ? closeMenu() : openMenu();
    });
    overlay.addEventListener('click', closeMenu);
    links.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', closeMenu); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMenu(); });

    // Scrolled state
    function onScroll() { nav.classList.toggle('yebnav--scrolled', window.scrollY > 40); }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', build);
  } else {
    build();
  }
})();
