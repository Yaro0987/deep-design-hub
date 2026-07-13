<?php
require_once __DIR__ . '/config.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($user === getAdminUser() && $pass === getAdminPass()) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $user;
        $_SESSION['admin_login_time'] = time();
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Deep Design Hubs — Admin Login</title>
<link rel="icon" type="image/png" href="https://deep-design.netlify.app/assets/imgs/logo/favicon.png">
<link rel="shortcut icon" href="https://deep-design.netlify.app/assets/imgs/logo/favicon.png">
<link rel="apple-touch-icon" sizes="180x180" href="https://deep-design.netlify.app/assets/imgs/logo/favicon.png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#0a0a0a;color:#e5e5e5;min-height:100vh;display:flex;align-items:center;justify-content:center}
.login-card{background:#111;border:1px solid #222;border-radius:16px;padding:40px;width:100%;max-width:400px;margin:20px}
.login-logo{text-align:center;margin-bottom:32px}
.login-logo h1{font-size:24px;font-weight:700;color:#fff;letter-spacing:-0.5px}
.login-logo p{font-size:12px;color:#666;text-transform:uppercase;letter-spacing:2px;margin-top:4px}
.login-logo .logo-icon{width:48px;height:48px;background:#fff;border-radius:12px;margin:0 auto 16px;display:flex;align-items:center;justify-content:center}
.login-logo .logo-icon svg{width:24px;height:24px}
.form-group{margin-bottom:20px}
.form-group label{display:block;font-size:12px;font-weight:600;color:#666;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
.form-input{width:100%;padding:12px 16px;background:#0a0a0a;border:1px solid #333;border-radius:8px;color:#fff;font-family:inherit;font-size:14px;transition:border .15s}
.form-input:focus{outline:none;border-color:#666}
.btn-login{width:100%;padding:12px;background:#fff;color:#000;border:none;border-radius:8px;font-family:inherit;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s}
.btn-login:hover{background:#e5e5e5}
.alert-error{background:#450a0a;border:1px solid #991b1b;color:#fca5a5;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:20px}
.login-footer{text-align:center;margin-top:24px}
.login-footer a{font-size:12px;color:#555;text-decoration:none}
.login-footer a:hover{color:#888}
</style>
</head>
<body>
<div class="login-card">
    <div class="login-logo">
        <div class="logo-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
        </div>
        <h1>Deep Design Hubs</h1>
        <p>Admin Panel</p>
    </div>

    <?php if ($error): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-input" placeholder="Enter username" required autofocus>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-input" placeholder="Enter password" required>
        </div>
        <button type="submit" class="btn-login">Sign In</button>
    </form>

    <div class="login-footer">
        <a href="../">&larr; Back to site</a>
    </div>
</div>
</body>
</html>
