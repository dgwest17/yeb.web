<?php
require_once __DIR__ . '/includes/auth.php';

if (isTrainAuthenticated()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $user = findUser($username);
    
    if (!$user) {
        $error = 'Username not found. Check with your team leader.';
    } elseif (!checkUserPassword($user, $password)) {
        $error = 'Incorrect access code.';
    } else {
        // Success — set session
        $_SESSION['train_user_id'] = $user['id'];
        $_SESSION['train_username'] = $user['username'];
        $_SESSION['train_first_name'] = $user['first_name'] ?? '';
        $_SESSION['train_last_name'] = $user['last_name'] ?? '';
        $_SESSION['train_role'] = $user['role'] ?? 'rep';
        $_SESSION['train_unlocked_level'] = $user['unlocked_level'] ?? 0;
        
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Become | Training Portal</title>
  <link rel="icon" type="image/png" href="../img/logo.png">
  <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">

<div class="login-waves">
  <div class="login-waves__layer login-waves__layer--1"></div>
  <div class="login-waves__layer login-waves__layer--2"></div>
  <div class="login-waves__layer login-waves__layer--3"></div>
</div>

<div class="login-container">
  <div class="login-box">
    <div class="login-box__icon">🏄</div>
    <h1>Become</h1>
    <p class="login-box__sub">Training Portal</p>
    
    <?php if ($error): ?>
      <div class="login-error"><?= esc($error) ?></div>
    <?php endif; ?>
    
    <div class="login-form">
      <input type="text" name="username" id="usernameInput" placeholder="Username" autocomplete="username" autocapitalize="off" autofocus value="<?= esc($_POST['username'] ?? '') ?>">
      <input type="password" name="password" id="pwInput" placeholder="Access Code" autocomplete="current-password">
      <button id="loginBtn" onclick="submitLogin()">Enter the Wave →</button>
    </div>
    
    <p class="login-box__hint">Your team leader will provide your username.</p>
  </div>
</div>

<script>
document.getElementById('pwInput').addEventListener('keydown', e => {
  if (e.key === 'Enter') submitLogin();
});
document.getElementById('usernameInput').addEventListener('keydown', e => {
  if (e.key === 'Enter') document.getElementById('pwInput').focus();
});

function submitLogin() {
  const username = document.getElementById('usernameInput').value.trim();
  const pw = document.getElementById('pwInput').value;
  if (!username) { document.getElementById('usernameInput').focus(); return; }
  if (!pw) { document.getElementById('pwInput').focus(); return; }
  
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'login.php';
  
  const u = document.createElement('input');
  u.type = 'hidden'; u.name = 'username'; u.value = username;
  form.appendChild(u);
  
  const p = document.createElement('input');
  p.type = 'hidden'; p.name = 'password'; p.value = pw;
  form.appendChild(p);
  
  document.body.appendChild(form);
  form.submit();
}
</script>
</body>
</html>
