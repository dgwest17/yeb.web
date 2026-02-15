<?php
require_once __DIR__ . '/includes/auth.php';

// Already logged in? Go to dashboard
if (isTrainAuthenticated()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = $_POST['password'] ?? '';
    if ($pw === getPortalPassword()) {
        $_SESSION['train_auth'] = true;
        $_SESSION['train_level'] = $_SESSION['train_level'] ?? 0;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Incorrect access code. Try again.';
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
      <input type="password" id="pwInput" placeholder="Access Code" autofocus>
      <button id="loginBtn" onclick="submitLogin()">Enter the Wave →</button>
    </div>
    
    <p class="login-box__hint">Don't have access? Talk to your team leader.</p>
  </div>
</div>

<script>
document.getElementById('pwInput').addEventListener('keydown', e => {
  if (e.key === 'Enter') submitLogin();
});

function submitLogin() {
  const pw = document.getElementById('pwInput').value;
  if (!pw) return;
  
  // Submit via hidden form to keep it server-side
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'login.php';
  const input = document.createElement('input');
  input.type = 'hidden';
  input.name = 'password';
  input.value = pw;
  form.appendChild(input);
  document.body.appendChild(form);
  form.submit();
}
</script>
</body>
</html>
