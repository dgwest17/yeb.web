<?php
require_once __DIR__ . '/includes/auth.php';
requireTrainAuth();

$tc = getTrainContent();
$manualId = $_GET['id'] ?? '';
$manual = null;

foreach ($tc['manuals'] ?? [] as $m) {
    if ($m['id'] === $manualId) { $manual = $m; break; }
}

if (!$manual) {
    header('Location: index.php');
    exit;
}

$userLevel = getUserLevel();
$progress = getTrainProgress();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($manual['title']) ?> | Become</title>
  <link rel="icon" type="image/png" href="../img/logo.png">
  <link rel="stylesheet" href="style.css">
</head>
<body class="manual-page">

<header class="train-header">
  <div class="train-header__inner">
    <div class="train-header__left">
      <a href="index.php" class="train-header__back">← Back</a>
      <h1 class="train-header__title"><?= esc($manual['icon'] ?? '') ?> <?= esc($manual['title']) ?></h1>
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

<div class="manual-layout">
  <!-- Folder/Level Navigation -->
  <nav class="manual-nav" id="manualNav">
    <?php foreach ($manual['folders'] ?? [] as $fi => $folder):
      $folderLevel = $folder['level'] ?? 0;
      $isUnlocked = $userLevel >= $folderLevel;
      $moduleCount = count($folder['modules'] ?? []);
      $completedCount = 0;
      foreach ($folder['modules'] ?? [] as $mod) {
          if (in_array($mod['id'], $progress)) $completedCount++;
      }
      $allDone = $moduleCount > 0 && $completedCount >= $moduleCount;
    ?>
    <div class="nav-folder <?= $isUnlocked ? ($allDone ? 'nav-folder--done' : 'nav-folder--open') : 'nav-folder--locked' ?>">
      <div class="nav-folder__header" onclick="<?= $isUnlocked ? "toggleFolder('folder-{$fi}')" : '' ?>">
        <span class="nav-folder__icon">
          <?php if (!$isUnlocked): ?>🔒
          <?php elseif ($allDone): ?>✓
          <?php else: ?>📂<?php endif; ?>
        </span>
        <span class="nav-folder__title"><?= esc($folder['title']) ?></span>
        <?php if ($isUnlocked && $moduleCount > 0): ?>
          <span class="nav-folder__count"><?= $completedCount ?>/<?= $moduleCount ?></span>
        <?php endif; ?>
      </div>
      
      <?php if ($isUnlocked): ?>
      <div class="nav-folder__modules" id="folder-<?= $fi ?>">
        <?php foreach ($folder['modules'] ?? [] as $modi => $mod):
          $isDone = in_array($mod['id'], $progress);
          $typeIcon = match($mod['type'] ?? 'lesson') {
              'quiz' => '📝',
              'passoff' => '🎯',
              default => '📄'
          };
        ?>
        <a href="module.php?manual=<?= urlencode($manualId) ?>&folder=<?= $fi ?>&module=<?= $modi ?>"
           class="nav-module <?= $isDone ? 'nav-module--done' : '' ?>">
          <span class="nav-module__icon"><?= $isDone ? '✓' : $typeIcon ?></span>
          <span class="nav-module__title"><?= esc($mod['title']) ?></span>
        </a>
        <?php endforeach; ?>
        
        <?php foreach ($folder['folders'] ?? [] as $sfi => $subfolder): ?>
        <div class="nav-subfolder">
          <div class="nav-subfolder__header" onclick="toggleFolder('sf-<?= $fi ?>-<?= $sfi ?>')">
            📁 <?= esc($subfolder['title']) ?>
          </div>
          <div class="nav-subfolder__modules" id="sf-<?= $fi ?>-<?= $sfi ?>">
            <?php foreach ($subfolder['modules'] ?? [] as $smodi => $smod):
              $isDone = in_array($smod['id'], $progress);
            ?>
            <a href="module.php?manual=<?= urlencode($manualId) ?>&folder=<?= $fi ?>&subfolder=<?= $sfi ?>&module=<?= $smodi ?>"
               class="nav-module <?= $isDone ? 'nav-module--done' : '' ?>">
              <span class="nav-module__icon"><?= $isDone ? '✓' : '📄' ?></span>
              <span class="nav-module__title"><?= esc($smod['title']) ?></span>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="nav-folder__locked-msg">
        Unlocks at Level <?= esc(formatLevel($folderLevel)) ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </nav>

  <!-- Content Area (shows when a module is selected on desktop, or full-screen on mobile) -->
  <div class="manual-content" id="manualContent">
    <div class="manual-content__empty">
      <div class="manual-content__empty-icon">🏄</div>
      <h2>Select a module to begin</h2>
      <p>Choose a lesson from the left to start learning.</p>
    </div>
  </div>
</div>

<script>
function toggleFolder(id) {
  const el = document.getElementById(id);
  if (el) el.classList.toggle('open');
}

// Auto-open first unlocked folder
document.addEventListener('DOMContentLoaded', () => {
  const firstOpen = document.querySelector('.nav-folder--open .nav-folder__modules');
  if (firstOpen) firstOpen.classList.add('open');
});
</script>
</body>
</html>
