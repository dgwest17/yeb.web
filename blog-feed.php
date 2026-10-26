<?php
/**
 * blog-feed.php — The blog as a JSON feed, for Lumaria's Beacons.
 *
 * Posts are written in /admin/ (content.json → blog_posts). Every post with
 * "Also post to Lumaria Beacons" ticked (the default) appears here; Lumaria's
 * /api/beacon-feed reads this and shows each one under "From the Admiral".
 *
 * GET /blog-feed.php → { posts: [{ id, title, date, tag, summary, body, url }] }, newest first.
 * body is plain text with blank lines between paragraphs (the app renders text, not HTML).
 */
header('Content-Type: application/json');
header('Cache-Control: public, max-age=300');
header('Access-Control-Allow-Origin: *');

$content = json_decode(@file_get_contents(__DIR__ . '/content.json') ?: '{}', true) ?: [];

function plain($html) {
  $s = preg_replace('/<li[^>]*>/i', '• ', (string)$html);
  $s = preg_replace('/<\s*(br|\/p|\/li|\/h[1-6]|\/div)\s*\/?>/i', "\n", $s);
  $s = html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8');
  // Escapes saved as literal text by older editor versions ("—")
  $s = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', fn($m) => mb_chr(hexdec($m[1]), 'UTF-8'), $s);
  $s = preg_replace("/[ \t]+\n/", "\n", $s);
  return trim(preg_replace("/\n{2,}/", "\n\n", preg_replace("/\n/", "\n\n", $s)));
}

/** Same rule as blog.html's postId(): the saved id, else date + title. */
function post_id($p) {
  if (!empty($p['id'])) return preg_replace('/[^a-z0-9-]/i', '', $p['id']);
  $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', ($p['date'] ?? '') . '-' . plain($p['title'] ?? '')), '-'));
  return substr($slug, 0, 70);
}

$posts = [];
foreach ($content['blog_posts'] ?? [] as $p) {
  if (($p['beacon'] ?? true) === false) continue;
  $title = plain($p['title'] ?? '');
  if ($title === '') continue;
  $body = plain($p['body'] ?? '');
  $id = post_id($p);
  $posts[] = [
    'id'      => $id,
    'title'   => $title,
    'date'    => $p['date'] ?? '',
    'tag'     => plain($p['tag'] ?? ''),
    'summary' => mb_strimwidth(explode("\n\n", $body)[0] ?? '', 0, 220, '…', 'UTF-8'),
    'body'    => $body,
    'url'     => 'https://yourenergybest.com/blog.html?p=' . rawurlencode($id),
  ];
}
usort($posts, fn($a, $b) => strcmp($b['date'], $a['date']));

echo json_encode(['posts' => $posts], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
