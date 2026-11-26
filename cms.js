// ============================================================
// cms.js — Shared CMS runtime
// Included by every page. Fetches content.json once, then
// exposes render functions each page calls to populate itself.
// ============================================================
window.CMS = {};

window.CMS.load = async function() {
  const res = await fetch('/content.json?' + Date.now()); // absolute: pages live in folders (/quote/)
  window.CMS.data = await res.json();
  return window.CMS.data;
};

// ─── Escape HTML to prevent XSS when rendering user content ───
function esc(str) {
  const d = document.createElement('div');
  d.textContent = str || '';
  return d.innerHTML;
}

// ─── Strip Quill <p> wrapper tags and decode HTML entities for plain-text fields ───
function strip(str) {
  if (!str) return '';
  // Remove outer <p>...</p> tags from Quill
  let s = String(str).replace(/^<p>/i, '').replace(/<\/p>$/i, '');
  // Remove any remaining HTML tags
  const d = document.createElement('div');
  d.innerHTML = s;
  return d.textContent || '';
}

// ─── Render Quill HTML safely (for rich content fields like FAQ answers) ───
function richHtml(str) {
  if (!str) return '';
  // Allow Quill HTML through but sanitize script tags
  return String(str)
    .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
    .replace(/on\w+\s*=/gi, '');
}

// ─── Simple markdown → HTML (headings, bold, italic, lists, paragraphs) ───
function md(text) {
  const lines = text.split('\n');
  let html = '', inList = false;

  lines.forEach(line => {
    if (inList && !line.startsWith('- ')) { html += '</ul>'; inList = false; }

    if (line.startsWith('## ')) {
      html += '</p><h2>' + inlineMd(line.slice(3)) + '</h2><p>';
    } else if (line.startsWith('- ')) {
      if (!inList) { html += '</p><ul>'; inList = true; }
      html += '<li>' + inlineMd(line.slice(2)) + '</li>';
    } else if (line.trim() === '') {
      html += '</p><p>';
    } else {
      html += inlineMd(line) + ' ';
    }
  });
  if (inList) html += '</ul>';

  html = '<p>' + html + '</p>';
  html = html.replace(/<p>\s*<\/p>/g, '');
  html = html.replace(/<p>\s*<h2>/g, '<h2>');
  html = html.replace(/<\/h2>\s*<p>/g, '</h2>');
  html = html.replace(/<p>\s*<ul>/g, '<ul>');
  html = html.replace(/<\/ul>\s*<\/p>/g, '</ul>');
  html = html.replace(/<\/ul>([^<])/g, '</ul><p>$1');
  html = html.replace(/<\/h2>([^<])/g, '</h2><p>$1');
  if (!html.endsWith('</p>') && !html.endsWith('</ul>') && !html.endsWith('</h2>')) html += '</p>';
  html = html.replace(/<p>\s*<\/p>/g, '');

  return html;
}

function inlineMd(text) {
  text = esc(text);
  // [text](https://…) or [text](/page/) → a link (only web and site links)
  text = text.replace(/\[([^\]]+)\]\(((?:https?:\/\/|\/)[^\s)]+)\)/g, (m, t, u) => '<a href="' + u + '"' + (/^https?:/.test(u) ? ' target="_blank" rel="noopener"' : '') + '>' + t + '</a>');
  text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
  text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');
  return text;
}

// ─── Post body: the admin's editor saves simple HTML; older posts are markdown. ───
// HTML is cleaned to a short list of tags (links only to web and site addresses).
function safeHtml(html) {
  const ok = new Set(['P','BR','STRONG','B','EM','I','U','A','UL','OL','LI','H2','H3','H4','BLOCKQUOTE']);
  const d = document.createElement('div'); d.innerHTML = html;
  (function walk(n) {
    for (const el of [...n.children]) {
      if (!ok.has(el.tagName)) { if (/^(SCRIPT|STYLE|IFRAME|OBJECT)$/.test(el.tagName)) el.remove(); else el.replaceWith(...el.childNodes); continue; }
      for (const a of [...el.attributes]) if (!(el.tagName === 'A' && a.name === 'href')) el.removeAttribute(a.name);
      if (el.tagName === 'A') { const u = el.getAttribute('href') || ''; if (!/^(https?:|\/)/i.test(u)) el.removeAttribute('href'); else if (/^https?:/i.test(u)) { el.target = '_blank'; el.rel = 'noopener'; } }
      walk(el);
    }
  })(d);
  return d.innerHTML;
}
function postBody(b) { b = String(b || ''); return /^\s*</.test(b) ? safeHtml(b) : md(b); }
function postText(b) { const d = document.createElement('div'); d.innerHTML = postBody(b); return d.textContent.replace(/\s+/g, ' ').trim(); }
function postImage(p, ratio, radius) {
  if (p.image && /^(https?:\/\/|\/|img\/|uploads\/)/.test(p.image)) {
    const src = /^(https?:|\/)/.test(p.image) ? p.image : '/' + p.image;
    return '<img src="' + esc(src) + '" alt="" loading="lazy" style="width:100%;aspect-ratio:' + ratio + ';object-fit:cover;display:block;' + (radius ? 'border-radius:var(--radius-lg);margin-bottom:var(--space-xl);' : '') + '">';
  }
  return '';
}
// Pinned at the top of the blog: the audit results (case studies)
const RESULTS_CARD = '<a href="/solar-audit-results/" style="display:flex;gap:1rem;align-items:center;text-decoration:none;color:#fff;background:linear-gradient(135deg,var(--navy) 0%,#126782 100%);border-radius:var(--radius);padding:1.1rem 1.3rem;margin-bottom:var(--space-lg);">'
  + '<span style="font-size:1.8rem">📊</span><span style="flex:1"><b style="display:block;font-size:1.05rem">Audit results: what we found in real San Diego homes</b>'
  + '<span style="opacity:.85;font-size:.9rem">Batteries, non-export add-ons that keep NEM, repairs, and getting ahead of the NEM 1.0 cliff.</span></span>'
  + '<span style="font-weight:700;white-space:nowrap">See the results →</span></a>';

// ─── Helper: get testimonials items regardless of old/new format ───
function getTestimonialItems() {
  const t = window.CMS.data.testimonials;
  if (!t) return [];
  // New format: { items: [...], rating, total_reviews }
  if (t.items && Array.isArray(t.items)) return t.items;
  // Old format: flat array
  if (Array.isArray(t)) return t;
  return [];
}

// ─── STATS ───
window.CMS.renderStats = function(containerId) {
  const s = window.CMS.data.stats;
  document.getElementById(containerId).innerHTML =
    `<div><div class="stat__number">${strip(s.savings)}</div><div class="stat__label">${strip(s.savings_label)}</div></div>
     <div><div class="stat__number">${strip(s.hours)}</div><div class="stat__label">${strip(s.hours_label)}</div></div>
     <div><div class="stat__number">${strip(s.installations)}</div><div class="stat__label">${strip(s.installations_label)}</div></div>
     <div><div class="stat__number">${strip(s.customers)}</div><div class="stat__label">${strip(s.customers_label)}</div></div>`;
};

// ─── TESTIMONIALS (3-column cards, used on home page) ───
window.CMS.renderTestimonials = function(containerId) {
  const items = getTestimonialItems();
  document.getElementById(containerId).innerHTML = items.map(t => `
    <div class="testimonial">
      <div class="testimonial__quote">"</div>
      <p>${strip(t.text)}</p>
      <div class="testimonial__author">
        <div class="testimonial__avatar">${strip(t.initials)}</div>
        <div class="testimonial__author-info">
          <strong>${strip(t.name)}</strong>
          <small>${strip(t.role)}</small>
        </div>
      </div>
    </div>
  `).join('');
};

// ─── TESTIMONIALS GLOBAL (expandable grid, shows 6 then expand) ───
window.CMS.renderTestimonialsGlobal = function(containerId, initialCount) {
  initialCount = initialCount || 6;
  const items = getTestimonialItems();
  const el = document.getElementById(containerId);
  if (!el || items.length === 0) return;

  const t = window.CMS.data.testimonials;
  const rating = (t && t.rating) ? t.rating : '5.0';
  const totalReviews = (t && t.total_reviews) ? t.total_reviews : '';

  // Rating header
  let ratingHTML = '';
  if (rating || totalReviews) {
    ratingHTML = `
      <div class="testimonials__rating">
        <div class="stars">★★★★★</div>
        <div class="rating-text"><strong>${strip(rating)}</strong> Average Rating${totalReviews ? ' · ' + strip(totalReviews) + ' Reviews' : ''}</div>
      </div>`;
  }

  const gridHTML = items.map((item, i) => `
    <div class="testimonials__item${i >= initialCount ? ' hidden' : ''}">
      <div class="testimonials__avatar">${strip(item.initials)}</div>
      <p class="testimonials__text">"${strip(item.text)}"</p>
      <div class="testimonials__author">
        <strong>${strip(item.name)}</strong>
        <span>${strip(item.role)}</span>
      </div>
    </div>
  `).join('');

  const seeMoreHTML = items.length > initialCount
    ? `<div style="text-align:center;"><button class="testimonials__see-more" id="${containerId}-see-more">See More Reviews</button></div>`
    : '';

  el.innerHTML = ratingHTML + `<div class="testimonials__grid">${gridHTML}</div>` + seeMoreHTML;

  // Wire up see more toggle
  const btn = document.getElementById(containerId + '-see-more');
  if (btn) {
    let showingAll = false;
    btn.addEventListener('click', function() {
      const allItems = el.querySelectorAll('.testimonials__item');
      if (!showingAll) {
        allItems.forEach(item => item.classList.remove('hidden'));
        this.textContent = 'Show Less';
        showingAll = true;
      } else {
        allItems.forEach((item, i) => {
          if (i >= initialCount) item.classList.add('hidden');
        });
        this.textContent = 'See More Reviews';
        showingAll = false;
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  }
};

// Render only first N testimonials (e.g. sidebar)
window.CMS.renderTestimonialsN = function(containerId, n) {
  const items = getTestimonialItems().slice(0, n);
  document.getElementById(containerId).innerHTML = items.map((t, i) => `
    <div class="testimonial" ${i > 0 ? 'style="margin-top:var(--space-md);"' : ''}>
      <div class="testimonial__quote">"</div>
      <p>${strip(t.text)}</p>
      <div class="testimonial__author">
        <div class="testimonial__avatar">${strip(t.initials)}</div>
        <div class="testimonial__author-info">
          <strong>${strip(t.name)}</strong>
          <small>${strip(t.role)}</small>
        </div>
      </div>
    </div>
  `).join('');
};

// ─── GALLERY ───
window.CMS.renderGallery = function(containerId) {
  document.getElementById(containerId).innerHTML = window.CMS.data.gallery.map(g => {
    if (g.url) {
      return `<div style="aspect-ratio:4/3; border-radius:var(--radius); overflow:hidden; background:var(--gray-100);">
                <img src="${esc(g.url)}" alt="${strip(g.caption)}" style="width:100%;height:100%;object-fit:cover;" />
              </div>`;
    }
    return `<div class="card__img" style="aspect-ratio:4/3; border-radius:var(--radius); min-height:200px;">
              <div style="text-align:center; color:var(--slate); opacity:.5;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                <div style="font-size:.75rem; margin-top:.3rem;">Photo placeholder</div>
              </div>
            </div>`;
  }).join('');
};

// ─── VIDEOS (filtered by context/page) ───
window.CMS.renderVideos = function(containerId, context) {
  const el = document.getElementById(containerId);
  const allVideos = window.CMS.data.videos || [];
  const videos = allVideos.filter(v => v.context === context && v.youtube_id);
  if (videos.length === 0) { el.style.display = 'none'; return; }
  el.style.display = 'block';
  el.innerHTML = videos.map(v => `
    <div style="margin-bottom:var(--space-lg);">
      <div style="aspect-ratio:16/9; border-radius:var(--radius); overflow:hidden; box-shadow:var(--shadow-md);">
        <iframe src="https://www.youtube.com/embed/${esc(v.youtube_id)}"
          frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowfullscreen style="width:100%;height:100%;"></iframe>
      </div>
      ${v.title ? `<p style="font-size:.88rem; color:var(--slate); margin-top:.5rem; text-align:center;">${esc(v.title)}</p>` : ''}
    </div>
  `).join('');
};

// ─── FAQ ───
window.CMS.renderFaq = function(containerId) {
  document.getElementById(containerId).innerHTML = window.CMS.data.faq.map(f => `
    <div class="faq__item">
      <button class="faq__q" onclick="CMS.toggleFaq(this)">
        ${strip(f.question)}
        <span class="icon"><svg viewBox="0 0 12 12" fill="none" stroke="var(--navy)" stroke-width="2"><path d="M2 6h8M6 2v8"/></svg></span>
      </button>
      <div class="faq__a">${richHtml(f.answer)}</div>
    </div>
  `).join('');
};
window.CMS.toggleFaq = function(btn) {
  const item = btn.closest('.faq__item');
  const isOpen = item.classList.contains('open');
  document.querySelectorAll('.faq__item').forEach(i => i.classList.remove('open'));
  if (!isOpen) item.classList.add('open');
};

// ─── REBATE ALERT ───
window.CMS.renderRebate = function(containerId) {
  const r = window.CMS.data.rebate_alert;
  let linksHtml = '';

  // Support both new .links array and legacy flat fields
  if (r.links && Array.isArray(r.links) && r.links.length > 0) {
    linksHtml = r.links.map(l =>
      `<a href="${esc(l.url)}" ${l.external ? 'target="_blank" rel="noopener"' : ''} style="${!l.external ? 'color:var(--orange);font-weight:600;' : ''}">${strip(l.label)}</a>`
    ).join('');
  } else {
    // Fallback: build links from legacy fields
    if (r.home_link_text) {
      linksHtml += `<a href="/quote/" style="color:var(--orange);font-weight:600;">${strip(r.home_link_text)}</a>`;
    }
  }

  document.getElementById(containerId).innerHTML = `
    <div class="rebate-alert">
      <div class="rebate-alert__icon">⚡</div>
      <div>
        <h4>${strip(r.heading)}</h4>
        <p style="margin-bottom:0.6rem;">${richHtml(r.text)}</p>
        <div style="display:flex; flex-wrap:wrap; gap:0.6rem; margin-top:0.5rem;">${linksHtml}</div>
      </div>
    </div>`;
};

// ─── BLOG INDEX ───
window.CMS.renderBlogIndex = function(containerId) {
  const posts = [...(window.CMS.data.blog_posts || [])].sort((a,b) => new Date(b.date) - new Date(a.date));
  if (posts.length === 0) {
    document.getElementById(containerId).innerHTML = RESULTS_CARD + `
      <div style="text-align:center; padding:4rem 2rem; color:var(--slate);">
        <h3 style="margin-bottom:1rem;">Coming Soon</h3>
        <p>We're working on some great content. Check back soon!</p>
      </div>`;
    return;
  }
  document.getElementById(containerId).innerHTML = RESULTS_CARD + posts.map((p, i) => `
    <a href="#" onclick="event.preventDefault(); CMS.renderBlogArticle('${containerId}', ${i})"
       style="text-decoration:none; color:inherit; display:block; border:1px solid var(--gray-200); border-radius:var(--radius); overflow:hidden; background:#fff; transition:box-shadow .25s, transform .25s; margin-bottom:var(--space-lg);"
       onmouseenter="this.style.boxShadow='var(--shadow-md)';this.style.transform='translateY(-2px)'"
       onmouseleave="this.style.boxShadow='none';this.style.transform='none'">
      ${postImage(p, '16/6', false)}
      <div style="padding:var(--space-lg);">
        <div style="display:flex; align-items:center; gap:var(--space-md); font-size:.8rem; color:var(--slate); margin-bottom:var(--space-sm);">
          <span style="display:inline-block; color:var(--teal); font-size:.72rem; font-weight:600; letter-spacing:.06em; text-transform:uppercase; background:rgba(34,168,179,.1); padding:.2rem .55rem; border-radius:50px;">${strip(p.tag||'General')}</span>
          <span>·</span><span>${strip(p.date)}</span><span>·</span><span>${strip(p.readtime||'')}</span>
        </div>
        <h2 style="font-family:var(--font-display); font-weight:400; font-size:clamp(1.3rem,2.5vw,1.6rem); margin-bottom:.4rem; line-height:1.2;">${strip(p.title)}</h2>
        <p style="font-size:.92rem; color:var(--slate); margin:0; line-height:1.6;">${esc(postText(p.body).slice(0,200))}${postText(p.body).length > 200 ? '…' : ''}</p>
        <div style="display:inline-flex; align-items:center; gap:.3rem; color:var(--teal); font-size:.82rem; font-weight:600; margin-top:var(--space-sm);">
          Read article <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 8h10M9 4l4 4-4 4"/></svg>
        </div>
      </div>
    </a>
  `).join('');
};

// ─── BLOG ARTICLE (single post view) ───
window.CMS.renderBlogArticle = function(containerId, index) {
  const posts = [...(window.CMS.data.blog_posts || [])].sort((a,b) => new Date(b.date) - new Date(a.date));
  const p = posts[index];
  const bodyHtml = postBody(p.body);

  document.getElementById(containerId).innerHTML = `
    <div style="display:grid; grid-template-columns:1.7fr 0.8fr; gap:var(--space-3xl); align-items:start;">
      <article>
        <a href="#" onclick="event.preventDefault(); CMS.renderBlogIndex('${containerId}')"
           style="display:inline-flex; align-items:center; gap:.4rem; font-size:.85rem; color:var(--teal); font-weight:500; margin-bottom:var(--space-lg); text-decoration:none;">
          <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 3L5 8l5 5"/></svg> Back to Blog
        </a>
        ${postImage(p, '16/7', true)}
        <div style="display:flex; align-items:center; gap:var(--space-md); margin-bottom:var(--space-md); font-size:.82rem; color:var(--slate);">
          <span style="display:inline-block; color:var(--teal); font-size:.72rem; font-weight:600; letter-spacing:.06em; text-transform:uppercase; background:rgba(34,168,179,.1); padding:.2rem .55rem; border-radius:50px;">${strip(p.tag||'General')}</span>
          <span>·</span><span>${strip(p.date)}</span><span>·</span><span>${strip(p.readtime||'')}</span>
        </div>
        <h1 style="font-family:var(--font-display); font-weight:400; font-size:clamp(2.2rem,5vw,3.4rem); line-height:1.2; margin-bottom:var(--space-md);">${strip(p.title)}</h1>
        <div style="max-width:720px; color:var(--navy-light); line-height:1.8;">${bodyHtml}</div>
        <div style="background:var(--cream); border-radius:var(--radius); padding:var(--space-lg) var(--space-xl); margin-top:var(--space-xl); text-align:center;">
          <h3 style="font-family:var(--font-display); font-weight:400; margin-bottom:.3rem;">Ready to see what solar could save you?</h3>
          <p style="font-size:.88rem; margin-bottom:var(--space-md); color:var(--slate);">Get a free, no-obligation quote tailored to your home.</p>
          <a href="/quote/" class="btn btn--primary">Get Your Free Quote</a>
        </div>
      </article>
      <aside style="display:flex; flex-direction:column; gap:var(--space-xl);">
        <div style="background:var(--cream); border-radius:var(--radius); padding:var(--space-lg);">
          <h4 style="margin-bottom:var(--space-sm); color:var(--navy);">📊 Audit results</h4>
          <p style="font-size:.85rem; margin:0; color:var(--slate);">What our audits found in real San Diego homes, and what we fixed.</p>
          <a href="/solar-audit-results/" class="btn btn--primary btn--sm" style="margin-top:var(--space-md); width:100%; justify-content:center; display:inline-flex;">See the results</a>
        </div>
        <div style="background:var(--cream); border-radius:var(--radius); padding:var(--space-lg);">
          <h4 style="margin-bottom:var(--space-sm); color:var(--navy);">🔋 Battery Rebate Resources</h4>
          <p style="font-size:.85rem; margin:0; color:var(--slate);">Learn more about SD Community Power's Solar Battery Savings program.</p>
          <a href="https://sdcommunitypower.org/solar-battery-savings/" target="_blank" rel="noopener" class="btn btn--outline btn--sm" style="margin-top:var(--space-md); width:100%; justify-content:center; display:inline-flex;">Learn More →</a>
        </div>
        <div style="background:var(--navy); border-radius:var(--radius); padding:var(--space-lg);">
          <h4 style="margin-bottom:var(--space-sm); color:#fff;">🤝 Join Our Team</h4>
          <p style="font-size:.85rem; margin:0; color:rgba(255,255,255,.6);">Interested in earning while helping homeowners go solar?</p>
          <a href="build" class="btn btn--outline btn--sm" style="margin-top:var(--space-md); width:100%; justify-content:center; display:inline-flex; border-color:var(--teal-light); color:var(--teal-light);">Apply Now</a>
        </div>
      </aside>
    </div>`;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};
