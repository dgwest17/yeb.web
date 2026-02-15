<?php
require_once __DIR__ . '/includes/auth.php';
requireTrainAuth();
refreshUserFromFile(); // Pick up any admin-side level changes

$user = currentTrainUser();
$tc = getTrainContent();
$manuals = $tc['manuals'] ?? [];
$progress = getTrainProgress();
$userLevel = getUserLevel();
$totalModules = 0;
$completedModules = count($progress);

foreach ($manuals as $m) {
    foreach ($m['folders'] ?? [] as $f) {
        if (($f['level'] ?? 0) <= $userLevel) {
            $totalModules += count($f['modules'] ?? []);
            foreach ($f['folders'] ?? [] as $sf) {
                $totalModules += count($sf['modules'] ?? []);
            }
        }
    }
}

$progressPct = $totalModules > 0 ? min(100, round(($completedModules / $totalModules) * 100)) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Become | Dashboard</title>
  <link rel="icon" type="image/png" href="../img/logo.png">
  <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-page">

<header class="train-header">
  <div class="train-header__inner">
    <div class="train-header__left">
      <h1 class="train-header__title">Become</h1>
      <span class="train-header__level">Level <?= esc(formatLevel($userLevel)) ?></span>
    </div>
    <div class="train-header__right">
      <span class="train-header__user"><?= esc($user['first_name']) ?></span>
      <a href="logout.php" class="train-header__logout">Log Out</a>
    </div>
  </div>
  <div class="header-waves">
    <div class="header-waves__layer header-waves__layer--1"></div>
    <div class="header-waves__layer header-waves__layer--2"></div>
  </div>
</header>

<!-- Personalized Greeting -->
<section class="dash-greeting">
  <div class="dash-greeting__inner">
    <h2 class="dash-greeting__title">Time to Level Up, <em><?= esc($user['first_name']) ?></em></h2>
    <p class="dash-greeting__sub">You're on Level <?= esc(formatLevel($userLevel)) ?>. Keep grinding — the next wave is coming.</p>
  </div>
</section>

<!-- Progress Overview -->
<section class="dash-progress">
  <div class="dash-progress__inner">
    <div class="dash-progress__stats">
      <div class="dash-stat">
        <span class="dash-stat__number"><?= esc(formatLevel($userLevel)) ?></span>
        <span class="dash-stat__label">Current Level</span>
      </div>
      <div class="dash-stat">
        <span class="dash-stat__number"><?= $completedModules ?></span>
        <span class="dash-stat__label">Completed</span>
      </div>
      <div class="dash-stat">
        <span class="dash-stat__number"><?= $totalModules ?></span>
        <span class="dash-stat__label">Available</span>
      </div>
      <div class="dash-stat">
        <span class="dash-stat__number"><?= $progressPct ?>%</span>
        <span class="dash-stat__label">Progress</span>
      </div>
    </div>
    <div class="dash-progress__bar">
      <div class="dash-progress__fill" style="width: <?= $progressPct ?>%"></div>
    </div>
  </div>
</section>

<!-- Manuals Grid -->
<section class="dash-manuals">
  <?php foreach ($manuals as $manual): 
    // Count accessible modules for this manual
    $manualTotal = 0;
    $manualDone = 0;
    foreach ($manual['folders'] ?? [] as $f) {
        if (($f['level'] ?? 0) <= $userLevel) {
            $manualTotal += count($f['modules'] ?? []);
            foreach ($f['modules'] ?? [] as $mod) {
                if (in_array($mod['id'], $progress)) $manualDone++;
            }
        }
    }
  ?>
  <a href="manual.php?id=<?= urlencode($manual['id']) ?>" class="manual-card">
    <div class="manual-card__icon"><?= $manual['icon'] ?? '📘' ?></div>
    <div class="manual-card__info">
      <h2><?= esc($manual['title']) ?></h2>
      <p><?= esc($manual['description'] ?? '') ?></p>
      <div class="manual-card__meta">
        <span class="manual-card__count"><?= count($manual['folders'] ?? []) ?> levels</span>
        <?php if ($manualTotal > 0): ?>
          <span class="manual-card__progress"><?= $manualDone ?>/<?= $manualTotal ?> done</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="manual-card__arrow">→</div>
  </a>
  <?php endforeach; ?>
</section>

<!-- Level Path (Builder Manual) -->
<?php
$builder = null;
foreach ($manuals as $m) {
    if ($m['id'] === 'builder') { $builder = $m; break; }
}
if ($builder):
?>
<section class="level-path">
  <h2 class="level-path__title">Your Path</h2>
  <div class="level-path__timeline">
    <?php foreach ($builder['folders'] ?? [] as $i => $folder):
      $folderLevel = $folder['level'] ?? 0;
      $isUnlocked = $userLevel >= $folderLevel;
      $isCurrent = false;
      
      $nextFolder = $builder['folders'][$i + 1] ?? null;
      if ($isUnlocked && (!$nextFolder || $userLevel < ($nextFolder['level'] ?? 999))) {
          $isCurrent = true;
      }
      
      $stateClass = $isCurrent ? 'current' : ($isUnlocked ? 'completed' : 'locked');
      $moduleCount = count($folder['modules'] ?? []);
      $completedInLevel = 0;
      foreach ($folder['modules'] ?? [] as $mod) {
          if (in_array($mod['id'], $progress)) $completedInLevel++;
      }
    ?>
    <div class="level-node level-node--<?= $stateClass ?>">
      <div class="level-node__dot">
        <?php if ($stateClass === 'completed'): ?>✓
        <?php elseif ($stateClass === 'current'): ?>🏄
        <?php else: ?>🔒<?php endif; ?>
      </div>
      <div class="level-node__info">
        <h3><?= esc($folder['title']) ?></h3>
        <p><?= esc($folder['description'] ?? '') ?></p>
        <?php if ($isUnlocked && $moduleCount > 0): ?>
          <span class="level-node__progress"><?= $completedInLevel ?>/<?= $moduleCount ?> modules</span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- Level Up Modal -->
<div class="level-up-modal" id="levelUpModal">
  <div class="level-up-modal__content">
    <div class="level-up-modal__icon">🏄‍♂️</div>
    <h2>LEVEL UP!</h2>
    <p id="levelUpText">You've reached a new level!</p>
    <button onclick="closeLevelUp()">Let's Go →</button>
  </div>
</div>

<script>
function showLevelUp(levelName) {
  document.getElementById('levelUpText').textContent = 'Welcome to ' + levelName + ', <?= esc($user['first_name']) ?>!';
  document.getElementById('levelUpModal').classList.add('visible');
  createConfetti();
}
function closeLevelUp() {
  document.getElementById('levelUpModal').classList.remove('visible');
}
function createConfetti() {
  const modal = document.querySelector('.level-up-modal__content');
  const colors = ['#22A8B3', '#FB9B47', '#38BEC9', '#FFD700', '#06D6A0'];
  for (let i = 0; i < 50; i++) {
    const c = document.createElement('div');
    c.className = 'confetti';
    c.style.left = Math.random() * 100 + '%';
    c.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
    c.style.animationDelay = Math.random() * 0.5 + 's';
    c.style.animationDuration = (1 + Math.random() * 2) + 's';
    modal.appendChild(c);
    setTimeout(() => c.remove(), 3000);
  }
}
const params = new URLSearchParams(window.location.search);
if (params.get('levelup')) {
  setTimeout(() => showLevelUp(params.get('levelup')), 500);
}
</script>
</body>
</html>
