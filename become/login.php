<?php
/**
 * /become/login.php — SECURED Training Portal Login
 * 
 * Features:
 *   ✅ Rate limiting (5 attempts per 15 minutes per IP)
 *   ✅ Password hashing (bcrypt) with auto-upgrade from plain text
 *   ✅ CSRF protection
 *   ✅ Security logging
 *   ✅ Session fixation prevention
 */

require_once __DIR__ . '/../security-config.php';
set_security_headers();

// ─── If already logged in, redirect to dashboard ───
if (!empty($_SESSION['portal_user'])) {
    header('Location: /become/');
    exit;
}

$error = '';
$csrf = csrf_token();

// ─── Handle login POST ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    $submitted_token = $_POST['_csrf_token'] ?? '';
    if (empty($submitted_token) || !hash_equals($csrf, $submitted_token)) {
        $error = 'Session expired. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            $result = portal_login($username, $password);
            
            if ($result['success']) {
                security_log('portal_login_success', ['username' => $username]);
                header('Location: /become/');
                exit;
            } else {
                security_log('portal_login_failed', ['username' => $username]);
                $error = $result['error'];
            }
        }
    }
    // Regenerate CSRF after failed attempt
    $csrf = csrf_token();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become — Login | Your Energy Best</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* ─── Your existing dark wave theme ─── */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'DM Sans', sans-serif;
            background: #0a0a0a;
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Animated wave background */
        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                radial-gradient(ellipse at 20% 80%, rgba(34, 168, 179, 0.08) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(251, 155, 71, 0.05) 0%, transparent 60%);
            z-index: 0;
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 2rem;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(34, 168, 179, 0.15);
            border-radius: 20px;
            padding: 3rem 2.5rem;
            backdrop-filter: blur(10px);
        }

        .login-card h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #22A8B3, #FB9B47);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-card p {
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-group input {
            width: 100%;
            padding: 0.85rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(34, 168, 179, 0.2);
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            font-family: 'DM Sans', sans-serif;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #22A8B3;
            box-shadow: 0 0 0 3px rgba(34, 168, 179, 0.1);
        }

        .login-btn {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, #22A8B3, #1a8a93);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 0.5rem;
        }

        .login-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(34, 168, 179, 0.3);
        }

        .error-msg {
            background: rgba(255, 59, 48, 0.1);
            border: 1px solid rgba(255, 59, 48, 0.3);
            color: #ff6b6b;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .logo-area {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-area img {
            height: 40px;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-area">
                <!-- Replace with your logo if desired -->
                <h1>Become</h1>
                <p>Training Portal — Your Energy Best</p>
            </div>

            <?php if ($error): ?>
                <div class="error-msg"><?php echo clean($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="/become/login.php" autocomplete="off">
                <input type="hidden" name="_csrf_token" value="<?php echo $csrf; ?>">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required
                           value="<?php echo clean($_POST['username'] ?? ''); ?>"
                           autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required
                           autocomplete="current-password">
                </div>

                <button type="submit" class="login-btn">Log In</button>
            </form>
        </div>
    </div>
</body>
</html>
