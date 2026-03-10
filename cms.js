// ============================================================
// cms.js — Shared CMS runtime
// Included by every page. Fetches content.json once, then
// exposes render functions each page calls to populate itself.
// ============================================================
window.CMS = {};

window.CMS.load = async function() {
  const res = await fetch('content.json?' + Date.now());
  window.CMS.data = await res.json();
  return window.CMS.data;
};

// ─── Escape HTML to prevent XSS when rendering user content ───
function esc(str) {
  if (!str) return '';
  // If the string contains HTML tags (from Quill), strip them first then escape
  const stripped = String(str).replace(/<[^>]*>/g, '').trim();
  const d = document.createElement('div');
  d.textContent = stripped;
  return d.innerHTML;
}

// ─── Sanitize Quill HTML — allow safe tags, strip dangerous ones ───
function richHtml(str) {
  if (!str) return '';
  // Allow Quill's standard tags, strip everything else
  const allowed = ['p','br','strong','b','em','i','u','s','strike','ul','ol','li','h1','h2','h3','h4','a','span','sub','sup'];
  const tmp = document.createElement('div');
  tmp.innerHTML = str;
  // Remove script/style/iframe tags entirely
  tmp.querySelectorAll('script,style,iframe,object,embed').forEach(el => el.remove());
  return tmp.innerHTML;
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
  text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
  text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');
  return text;
}

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
    `<div><div class="stat__number">${esc(s.savings)}</div><div class="stat__label">${esc(s.savings_label)}</div></div>
     <div><div class="stat__number">${esc(s.hours)}</div><div class="stat__label">${esc(s.hours_label)}</div></div>
     <div><div class="stat__number">${esc(s.installations)}</div><div class="stat__label">${esc(s.installations_label)}</div></div>
     <div><div class="stat__number">${esc(s.customers)}</div><div class="stat__label">${esc(s.customers_label)}</div></div>`;
};

// ─── TESTIMONIALS (3-column cards, used on home page) ───
window.CMS.renderTestimonials = function(containerId) {
  const items = getTestimonialItems();
  document.getElementById(containerId).innerHTML = items.map(t => `
    <div class="testimonial">
      <div class="testimonial__quote">"</div>
      <p>${esc(t.text)}</p>
      <div class="testimonial__author">
        <div class="testimonial__avatar">${esc(t.initials)}</div>
        <div class="testimonial__author-info">
          <strong>${esc(t.name)}</strong>
          <small>${esc(t.role)}</small>
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
        <div class="rating-text"><strong>${esc(rating)}</strong> Average Rating${totalReviews ? ' · ' + esc(totalReviews) + ' Reviews' : ''}</div>
      </div>`;
  }

  const gridHTML = items.map((item, i) => `
    <div class="testimonials__item${i >= initialCount ? ' hidden' : ''}">
      <div class="testimonials__avatar">${esc(item.initials)}</div>
      <p>${esc(item.text)}</p>
      <div class="testimonials__author">
        <strong>${esc(item.name)}</strong>
        <span>${esc(item.role)}</span>
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
      <p>${esc(t.text)}</p>
      <div class="testimonial__author">
        <div class="testimonial__avatar">${esc(t.initials)}</div>
        <div class="testimonial__author-info">
          <strong>${esc(t.name)}</strong>
          <small>${esc(t.role)}</small>
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
                <img src="${esc(g.url)}" alt="${esc(g.caption)}" style="width:100%;height:100%;object-fit:cover;" />
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
  const el = document.getElementById(containerId);
  if (!el || !window.CMS.data.faq) return;
  el.innerHTML = window.CMS.data.faq.map(f => `
    <div class="faq__item">
      <button class="faq__q" onclick="CMS.toggleFaq(this)">
        ${esc(f.question)}
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
      `<a href="${esc(l.url)}" ${l.external ? 'target="_blank" rel="noopener"' : ''} style="${!l.external ? 'color:var(--orange);font-weight:600;' : ''}">${esc(l.label)}</a>`
    ).join('');
  } else {
    // Fallback: build links from legacy fields
    if (r.home_link_text) {
      linksHtml += `<a href="quote.html" style="color:var(--orange);font-weight:600;">${esc(r.home_link_text)}</a>`;
    }
  }

  document.getElementById(containerId).innerHTML = `
    <div class="rebate-alert">
      <div class="rebate-alert__icon">⚡</div>
      <div>
        <h4>${esc(r.heading)}</h4>
        <div style="margin-bottom:0.6rem;">${richHtml(r.text)}</div>
        <div style="display:flex; flex-wrap:wrap; gap:0.6rem; margin-top:0.5rem;">${linksHtml}</div>
      </div>
    </div>`;
};

// ─── BLOG INDEX ───
window.CMS.renderBlogIndex = function(containerId) {
  const posts = [...(window.CMS.data.blog_posts || [])].sort((a,b) => new Date(b.date) - new Date(a.date));
  if (posts.length === 0) {
    document.getElementById(containerId).innerHTML = `
      <div style="text-align:center; padding:4rem 2rem; color:var(--slate);">
        <h3 style="margin-bottom:1rem;">Coming Soon</h3>
        <p>We're working on some great content. Check back soon!</p>
      </div>`;
    return;
  }
  document.getElementById(containerId).innerHTML = posts.map((p, i) => `
    <a href="#" onclick="event.preventDefault(); CMS.renderBlogArticle('${containerId}', ${i})"
       style="text-decoration:none; color:inherit; display:block; border:1px solid var(--gray-200); border-radius:var(--radius); overflow:hidden; background:#fff; transition:box-shadow .25s, transform .25s; margin-bottom:var(--space-lg);"
       onmouseenter="this.style.boxShadow='var(--shadow-md)';this.style.transform='translateY(-2px)'"
       onmouseleave="this.style.boxShadow='none';this.style.transform='none'">
      <div style="width:100%; aspect-ratio:16/6; background:var(--gray-100); display:flex; align-items:center; justify-content:center; color:var(--slate); opacity:.4;">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
      </div>
      <div style="padding:var(--space-lg);">
        <div style="display:flex; align-items:center; gap:var(--space-md); font-size:.8rem; color:var(--slate); margin-bottom:var(--space-sm);">
          <span style="display:inline-block; color:var(--teal); font-size:.72rem; font-weight:600; letter-spacing:.06em; text-transform:uppercase; background:rgba(34,168,179,.1); padding:.2rem .55rem; border-radius:50px;">${esc(p.tag||'General')}</span>
          <span>·</span><span>${esc(p.date)}</span><span>·</span><span>${esc(p.readtime||'')}</span>
        </div>
        <h2 style="font-family:var(--font-display); font-weight:400; font-size:clamp(1.3rem,2.5vw,1.6rem); margin-bottom:.4rem; line-height:1.2;">${esc(p.title)}</h2>
        <p style="font-size:.92rem; color:var(--slate); margin:0; line-height:1.6;">${esc((p.body||'').split('\\n\\n')[0].replace(/\\*\\*/g,'').replace(/\\*/g,'').slice(0,200))}</p>
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
  const bodyHtml = md(p.body);

  document.getElementById(containerId).innerHTML = `
    <div style="display:grid; grid-template-columns:1.7fr 0.8fr; gap:var(--space-3xl); align-items:start;">
      <article>
        <a href="#" onclick="event.preventDefault(); CMS.renderBlogIndex('${containerId}')"
           style="display:inline-flex; align-items:center; gap:.4rem; font-size:.85rem; color:var(--teal); font-weight:500; margin-bottom:var(--space-lg); text-decoration:none;">
          <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 3L5 8l5 5"/></svg> Back to Blog
        </a>
        <div style="width:100%; aspect-ratio:16/7; background:var(--gray-100); border-radius:var(--radius-lg); display:flex; align-items:center; justify-content:center; margin-bottom:var(--space-xl); color:var(--slate); opacity:.5;">
          <div style="text-align:center;">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
            <div style="font-size:.75rem; margin-top:.3rem;">Article photo placeholder</div>
          </div>
        </div>
        <div style="display:flex; align-items:center; gap:var(--space-md); margin-bottom:var(--space-md); font-size:.82rem; color:var(--slate);">
          <span style="display:inline-block; color:var(--teal); font-size:.72rem; font-weight:600; letter-spacing:.06em; text-transform:uppercase; background:rgba(34,168,179,.1); padding:.2rem .55rem; border-radius:50px;">${esc(p.tag||'General')}</span>
          <span>·</span><span>${esc(p.date)}</span><span>·</span><span>${esc(p.readtime||'')}</span>
        </div>
        <h1 style="font-family:var(--font-display); font-weight:400; font-size:clamp(2.2rem,5vw,3.4rem); line-height:1.2; margin-bottom:var(--space-md);">${esc(p.title)}</h1>
        <div style="max-width:720px; color:var(--navy-light); line-height:1.8;">${bodyHtml}</div>
        <div style="background:var(--cream); border-radius:var(--radius); padding:var(--space-lg) var(--space-xl); margin-top:var(--space-xl); text-align:center;">
          <h3 style="font-family:var(--font-display); font-weight:400; margin-bottom:.3rem;">Ready to see what solar could save you?</h3>
          <p style="font-size:.88rem; margin-bottom:var(--space-md); color:var(--slate);">Get a free, no-obligation quote tailored to your home.</p>
          <a href="quote.html" class="btn btn--primary">Get Your Free Quote</a>
        </div>
      </article>
      <aside style="display:flex; flex-direction:column; gap:var(--space-xl);">
        <div style="background:var(--cream); border-radius:var(--radius); padding:var(--space-lg);">
          <h4 style="margin-bottom:var(--space-sm); color:var(--navy);">💰 Don't Miss Out on Incentives</h4>
          <p style="font-size:.85rem; margin:0; color:var(--slate);">Battery rebates and federal tax credits are available now — but funds are limited.</p>
          <a href="quote.html" class="btn btn--primary btn--sm" style="margin-top:var(--space-md); width:100%; justify-content:center; display:inline-flex;">Get a Quote</a>
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
