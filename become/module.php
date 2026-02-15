<?php
require_once __DIR__ . '/includes/auth.php';
requireTrainAuth();

$tc = getTrainContent();
$manualId = $_GET['manual'] ?? '';
$folderIdx = intval($_GET['folder'] ?? 0);
$subfolderIdx = isset($_GET['subfolder']) ? intval($_GET['subfolder']) : null;
$moduleIdx = intval($_GET['module'] ?? 0);

// Find the module
$manual = null;
foreach ($tc['manuals'] ?? [] as $m) {
    if ($m['id'] === $manualId) { $manual = $m; break; }
}
if (!$manual) { header('Location: index.php'); exit; }

$folder = $manual['folders'][$folderIdx] ?? null;
if (!$folder) { header('Location: manual.php?id=' . urlencode($manualId)); exit; }

$module = null;
if ($subfolderIdx !== null) {
    $subfolder = $folder['folders'][$subfolderIdx] ?? null;
    $module = $subfolder['modules'][$moduleIdx] ?? null;
} else {
    $module = $folder['modules'][$moduleIdx] ?? null;
}
if (!$module) { header('Location: manual.php?id=' . urlencode($manualId)); exit; }

// Check level lock
$userLevel = getUserLevel();
$requiredLevel = $folder['level'] ?? 0;
$isLocked = $userLevel < $requiredLevel;

$progress = getTrainProgress();
$isCompleted = in_array($module['id'], $progress);

// Handle completion POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete']) && !$isLocked) {
    markModuleComplete($module['id']);
    $isCompleted = true;
    
    // Check if all modules in this level are now complete
    $allDone = true;
    foreach ($folder['modules'] ?? [] as $m) {
        if (!in_array($m['id'], getTrainProgress())) { $allDone = false; break; }
    }
    
    // Redirect with completion flag
    $redirectUrl = $_SERVER['REQUEST_URI'];
    if ($allDone) {
        $redirectUrl = 'index.php?levelup=' . urlencode($folder['title']);
    }
    header('Location: ' . $redirectUrl . (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'completed=1');
    exit;
}

$justCompleted = isset($_GET['completed']);
$sections = $module['sections'] ?? [];
$videos = $module['videos'] ?? [];

// Find next module
$nextModule = null;
$nextUrl = null;
if ($subfolderIdx !== null) {
    $sf = $folder['folders'][$subfolderIdx];
    if (isset($sf['modules'][$moduleIdx + 1])) {
        $nextModule = $sf['modules'][$moduleIdx + 1];
        $nextUrl = "module.php?manual=" . urlencode($manualId) . "&folder={$folderIdx}&subfolder={$subfolderIdx}&module=" . ($moduleIdx + 1);
    }
} else {
    if (isset($folder['modules'][$moduleIdx + 1])) {
        $nextModule = $folder['modules'][$moduleIdx + 1];
        $nextUrl = "module.php?manual=" . urlencode($manualId) . "&folder={$folderIdx}&module=" . ($moduleIdx + 1);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($module['title']) ?> | Become</title>
  <link rel="icon" type="image/png" href="../img/logo.png">
  <link rel="stylesheet" href="style.css">
</head>
<body class="module-page">

<header class="train-header">
  <div class="train-header__inner">
    <div class="train-header__left">
      <a href="manual.php?id=<?= urlencode($manualId) ?>" class="train-header__back">← <?= esc($folder['title']) ?></a>
    </div>
    <div class="train-header__right">
      <span class="train-header__level">Level <?= esc(formatLevel($userLevel)) ?></span>
    </div>
  </div>
  <div class="header-waves">
    <div class="header-waves__layer header-waves__layer--1"></div>
    <div class="header-waves__layer header-waves__layer--2"></div>
  </div>
</header>

<?php if ($isLocked): ?>
<!-- LOCKED STATE -->
<div class="module-locked">
  <div class="module-locked__icon">🔒</div>
  <h2>This module is locked</h2>
  <p>Reach Level <?= esc(formatLevel($requiredLevel)) ?> to unlock this content.</p>
  <a href="index.php" class="module-btn">Back to Dashboard</a>
</div>

<?php else: ?>
<!-- MODULE CONTENT -->
<div class="module-wrapper">
  <!-- Module Header -->
  <div class="module-hero">
    <div class="module-hero__type">
      <?php
      $typeLabel = match($module['type'] ?? 'lesson') {
          'quiz' => '📝 Quiz',
          'passoff' => '🎯 Passoff',
          default => '📄 Lesson'
      };
      echo $typeLabel;
      ?>
    </div>
    <h1><?= esc($module['title']) ?></h1>
    <?php if (!empty($module['description'])): ?>
      <p class="module-hero__desc"><?= esc($module['description']) ?></p>
    <?php endif; ?>
    <?php if ($isCompleted): ?>
      <div class="module-hero__badge">✓ Completed</div>
    <?php endif; ?>
  </div>

  <!-- Videos -->
  <?php if (!empty($videos)): ?>
  <div class="module-videos">
    <?php foreach ($videos as $video):
      $embedUrl = $video['url'] ?? '';
      // Convert YouTube watch URLs to embed
      if (strpos($embedUrl, 'youtube.com/watch') !== false) {
          preg_match('/v=([^&]+)/', $embedUrl, $matches);
          if (!empty($matches[1])) $embedUrl = 'https://www.youtube.com/embed/' . $matches[1];
      } elseif (strpos($embedUrl, 'youtu.be/') !== false) {
          $embedUrl = 'https://www.youtube.com/embed/' . basename(parse_url($embedUrl, PHP_URL_PATH));
      } elseif (strpos($embedUrl, 'vimeo.com/') !== false) {
          preg_match('/vimeo\.com\/(\d+)/', $embedUrl, $matches);
          if (!empty($matches[1])) $embedUrl = 'https://player.vimeo.com/video/' . $matches[1];
      }
    ?>
    <div class="module-video">
      <?php if (!empty($video['title'])): ?>
        <h3 class="module-video__title"><?= esc($video['title']) ?></h3>
      <?php endif; ?>
      <div class="module-video__embed">
        <iframe src="<?= esc($embedUrl) ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Sections (Quote / Response / Tip) -->
  <?php if (!empty($sections)): ?>
  <div class="module-sections">
    <?php foreach ($sections as $si => $section): ?>
    <div class="section-card" style="animation-delay: <?= $si * 0.1 ?>s">
      <?php if (!empty($section['customer_quote'])): ?>
        <div class="section-card__quote">
          <span class="section-card__quote-mark">"</span>
          <?= esc($section['customer_quote']) ?>
          <span class="section-card__quote-mark">"</span>
        </div>
      <?php endif; ?>
      
      <?php if (!empty($section['rep_response'])): ?>
        <div class="section-card__response">
          <?= nl2br(esc($section['rep_response'])) ?>
        </div>
      <?php endif; ?>
      
      <?php if (!empty($section['tip'])): ?>
        <div class="section-card__tip">
          <em><?= esc($section['tip']) ?></em>
        </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Next Step Guidance -->
  <?php if (!empty($module['next_step'])): ?>
  <div class="module-nextstep">
    <strong>What's Next:</strong> <?= esc($module['next_step']) ?>
  </div>
  <?php endif; ?>

  <!-- Completion -->
  <div class="module-complete">
    <?php if ($isCompleted): ?>
      <div class="module-complete__done">
        <span>✓ Module Complete</span>
      </div>
      <?php if ($nextModule): ?>
        <a href="<?= esc($nextUrl) ?>" class="module-btn module-btn--next">
          Next: <?= esc($nextModule['title']) ?> →
        </a>
      <?php else: ?>
        <a href="manual.php?id=<?= urlencode($manualId) ?>" class="module-btn">
          Back to <?= esc($manual['title']) ?>
        </a>
      <?php endif; ?>
    <?php else: ?>
      <form method="POST" style="text-align:center;">
        <input type="hidden" name="complete" value="1">
        <button type="submit" class="module-btn module-btn--complete">
          Mark as Complete 🏄
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php if ($justCompleted && !$isCompleted === false): ?>
<script>
// Completion celebration
document.addEventListener('DOMContentLoaded', () => {
  const badge = document.querySelector('.module-complete__done');
  if (badge) {
    badge.classList.add('celebrate');
    // Small confetti burst
    for (let i = 0; i < 20; i++) {
      const c = document.createElement('div');
      c.className = 'confetti';
      c.style.left = (30 + Math.random() * 40) + '%';
      c.style.backgroundColor = ['#22A8B3','#FB9B47','#38BEC9','#FFD700','#06D6A0'][Math.floor(Math.random()*5)];
      c.style.animationDelay = Math.random() * 0.3 + 's';
      c.style.animationDuration = (1 + Math.random()) + 's';
      document.querySelector('.module-complete').appendChild(c);
      setTimeout(() => c.remove(), 2500);
    }
  }
});
</script>
<?php endif; ?>

<?php endif; ?>
</body>
</html>
