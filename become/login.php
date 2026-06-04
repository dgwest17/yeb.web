<?php
/**
 * become/login.php — Portal Login (email-based)
 * Location: public_html/become/login.php
 *
 * Reps log in with their EMAIL. For backward compatibility we also match
 * the legacy `username` column, so existing accounts that don't yet have an
 * email set are not locked out. Once every account has an email, you can make
 * this strict by removing the "OR username = ?" clause below.
 */
session_start();

if (!empty($_SESSION['portal_user_id'])) {
    header('Location: /become/');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['email'] ?? $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login && $password) {
        try {
            require_once __DIR__ . '/includes/db.php';
            $db = Database::getInstance();
            // Email first; legacy username as a safety fallback.
            $s = $db->prepare("SELECT * FROM training_users WHERE (email = ? OR username = ?) AND is_active = 1 LIMIT 1");
            $s->execute([$login, $login]);
            $user = $s->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['portal_user_id']     = (int)$user['id'];
                $_SESSION['portal_user']        = $user['username'];
                $_SESSION['portal_email']       = $user['email'] ?? '';
                $_SESSION['portal_role']        = $user['role'];
                $_SESSION['portal_full_access'] = !empty($user['full_access']) ? 1 : 0;
                $_SESSION['portal_login_time']  = time();

                require_once __DIR__ . '/includes/ProgressionEngine.php';
                $engine = new ProgressionEngine();
                $engine->getUserProgress((int)$user['id']);

                header('Location: /become/');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (Exception $e) {
            $error = 'Connection error. Please try again.';
        }
    } else {
        $error = 'Please enter your email and password.';
    }
}

$expired = isset($_GET['expired']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Become</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#0a0a0f; --teal:#22A8B3; --orange:#FB9B47; --card:rgba(255,255,255,0.03); --border:rgba(34,168,179,0.15); --text:#fff; --dim:rgba(255,255,255,0.5); }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; display:flex; align-items:center; justify-content:center; }
        body::before { content:''; position:fixed; top:0;left:0;right:0;bottom:0; background:radial-gradient(ellipse at 30% 70%,rgba(34,168,179,0.06) 0%,transparent 60%),radial-gradient(ellipse at 70% 30%,rgba(251,155,71,0.04) 0%,transparent 60%); pointer-events:none; }
        .login-card { position:relative; z-index:1; background:var(--card); border:1px solid var(--border); border-radius:20px; padding:2.5rem; width:100%; max-width:400px; margin:1rem; backdrop-filter:blur(10px); }
        .login-card h1 { font-family:'Playfair Display',serif; font-size:1.8rem; margin-bottom:0.25rem; background:linear-gradient(135deg,var(--teal),var(--orange)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .login-card p.sub { color:var(--dim); margin-bottom:1.5rem; font-size:0.9rem; }
        .field { margin-bottom:1rem; }
        .field label { display:block; font-size:0.85rem; font-weight:600; color:var(--dim); margin-bottom:0.3rem; }
        .field input { width:100%; padding:0.75rem 1rem; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:10px; color:var(--text); font-size:1rem; font-family:inherit; outline:none; transition:border 0.2s; }
        .field input:focus { border-color:var(--teal); }
        .login-btn { width:100%; padding:0.85rem; background:linear-gradient(135deg,var(--teal),#1a8a93); color:#fff; border:none; border-radius:10px; font-size:1rem; font-weight:700; cursor:pointer; font-family:inherit; transition:transform 0.2s,box-shadow 0.2s; }
        .login-btn:hover { transform:translateY(-1px); box-shadow:0 4px 20px rgba(34,168,179,0.3); }
        .error-msg { background:rgba(239,71,111,0.15); border:1px solid rgba(239,71,111,0.3); color:#ff6b8a; padding:0.6rem 1rem; border-radius:8px; font-size:0.85rem; margin-bottom:1rem; }
        .back-link { display:block; text-align:center; margin-top:1.5rem; color:var(--dim); text-decoration:none; font-size:0.85rem; }
        .back-link:hover { color:var(--teal); }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>Become</h1>
        <p class="sub">Your Energy Best Training Portal</p>

        <?php if ($expired): ?>
            <div class="error-msg">Session expired. Please log in again.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="field">
                <label>Email</label>
                <input type="text" name="email" required autofocus autocomplete="username" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="login-btn">Log In →</button>
        </form>
        <a href="/" class="back-link">← Back to yourenergybest.com</a>
    </div>
</body>
</html>
