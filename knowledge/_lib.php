<?php
/**
 * knowledge/_lib.php — the Knowledge Center's data and helpers, shared by
 * /knowledge/ (index.php), each answer's own page (entry.php) and the feed Luma
 * reads (/knowledge-feed.php).
 *
 * THE ONE SOURCE: Admin → Knowledge saves knowledge.json (server-only, like
 * content.json). Until the first save it falls back to knowledge-seed.json, the
 * answers as they were written into the old page.
 *
 *   cats:    [{ id, icon, title, intro }]
 *   entries: [{ id, slug, question, answer (HTML), cat, tags, audience: all|shopping|owners,
 *               next: auto|quote|audit|lumaria|none, live, luma, updated }]
 */
if (!defined('KC_ROOT')) define('KC_ROOT', dirname(__DIR__));
const KC_BASE = 'https://yourenergybest.com';

function kc_data() {
  static $d = null;
  if ($d !== null) return $d;
  foreach (['knowledge.json', 'knowledge-seed.json'] as $f) {
    $p = KC_ROOT . '/' . $f;
    if (is_file($p) && ($j = json_decode(file_get_contents($p), true))) { $d = $j + ['cats' => [], 'entries' => []]; return $d; }
  }
  return $d = ['cats' => [], 'entries' => []];
}
function kc_live() { return array_values(array_filter(kc_data()['entries'], fn($e) => ($e['live'] ?? true) !== false && !empty($e['slug']))); }
function kc_cat($id) { foreach (kc_data()['cats'] as $c) if ($c['id'] === $id) return $c; return ['id' => $id, 'icon' => '', 'title' => ucfirst($id), 'intro' => '']; }
function kc_find($slug) { foreach (kc_live() as $e) if ($e['slug'] === $slug) return $e; return null; }
function kc_url($e) { return '/knowledge/' . rawurlencode($e['slug']) . '/'; }

function kc_e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** Answer HTML with only simple formatting kept (the editor allows the same set). */
function kc_html($html) {
  $h = preg_replace('#<(script|style|iframe)[^>]*>.*?</\1>#is', '', (string)$html);
  $h = strip_tags($h, '<p><br><strong><b><em><i><u><a><ul><ol><li><h3><h4>');
  $h = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $h);
  return preg_replace('/href\s*=\s*"(?!https?:|mailto:|tel:|\/)[^"]*"/i', 'href="#"', $h);
}
/** Plain text, paragraphs kept as blank lines — for descriptions, search markup and Luma. */
function kc_text($html) {
  $s = preg_replace('/<li[^>]*>/i', '• ', (string)$html);
  $s = preg_replace('/<\s*(br|\/p|\/li|\/h[1-6])\s*\/?>/i', "\n", $s);
  $s = html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8');
  return trim(preg_replace("/\n{2,}/", "\n\n", preg_replace("/[ \t]*\n[ \t]*/", "\n", $s)));
}
function kc_summary($html, $len = 158) {
  $t = preg_replace('/\s+/', ' ', kc_text($html));
  return mb_strlen($t) <= $len ? $t : rtrim(mb_substr($t, 0, $len - 1), " ,.;:—-") . '…';
}

/**
 * The next step under an answer. 'auto' picks by who it's for, then by topic:
 * already have solar → audit (and the free lumaria check); shopping → quote.
 */
function kc_next($e) {
  $n = $e['next'] ?? 'auto';
  if ($n === 'none') return null;
  if ($n === 'auto') {
    $aud = $e['audience'] ?? 'all';
    if ($aud === 'owners') $n = 'audit';
    elseif ($aud === 'shopping') $n = 'quote';
    else $n = in_array($e['cat'] ?? '', ['service', 'batteries'], true) ? 'audit' : 'quote';
  }
  $steps = [
    'quote'   => ['Thinking about solar or a battery?', 'Get a free, no-pressure quote. A real person reviews your home — no credit check.', 'Get my free quote →', '/quote/',
                  ['Already have solar?', '/audit/']],
    'audit'   => ['Already have solar?', 'A Solar Savings Audit shows what your system is really saving and what would fix it — or check it yourself free in the lumaria app.', 'Book a solar audit →', '/audit/',
                  ['Free self-check in lumaria', 'https://www.lumaria.energy/']],
    'lumaria' => ['See it for your own system', 'The free lumaria app shows your real savings, your rates, and what\'s ahead for your system.', 'Open the lumaria app →', 'https://www.lumaria.energy/',
                  ['Talk to David instead', '/audit/']],
  ];
  return $steps[$n] ?? $steps['quote'];
}
function kc_next_html($e) {
  $s = kc_next($e);
  if (!$s) return '';
  [$h, $p, $btn, $href, $alt] = $s;
  return '<div class="kc-next"><h3>' . kc_e($h) . '</h3><p>' . kc_e($p) . '</p>'
    . '<a class="btn-gold" href="' . kc_e($href) . '">' . kc_e($btn) . '</a>'
    . ' <a class="kc-next-alt" href="' . kc_e($alt[1]) . '">' . kc_e($alt[0]) . '</a></div>';
}
