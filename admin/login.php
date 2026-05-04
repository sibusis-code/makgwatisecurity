<?php
require_once __DIR__ . '/auth.php';

// Redirect to setup if not configured yet
if (!is_setup_done()) {
    header('Location: setup.php'); exit;
}

// Already logged in
if (is_logged_in()) {
    header('Location: index.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mgw_session_start();
    $auth     = json_decode(file_get_contents(AUTH_FILE), true);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === ($auth['username'] ?? '') && password_verify($password, $auth['hash'] ?? '')) {
        session_regenerate_id(true);
        $_SESSION['mgw_auth'] = true;
        $_SESSION['mgw_user'] = $username;
        // Generate fresh CSRF
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        header('Location: index.php'); exit;
    } else {
        // Prevent timing attacks — always verify even on bad username
        password_verify('dummy', '$2y$10$invalidhashpadding000000000000000000000000000000000000000');
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — Makgwati Security CMS</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#1a365d,#2c5282,#3182ce);min-height:100vh;display:flex;align-items:center;justify-content:center;}
.card{background:#fff;border-radius:20px;padding:2.5rem;width:100%;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,0.25);}
.logo{text-align:center;margin-bottom:2rem;}
.logo-ring{width:70px;height:70px;background:linear-gradient(135deg,#FF6B35,#F7931E);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;}
.logo-ring i{font-size:1.8rem;color:#fff;}
.logo span{display:block;font-size:1.4rem;font-weight:800;color:#1a365d;letter-spacing:2px;}
.logo small{font-size:0.72rem;color:#FF6B35;letter-spacing:3px;font-weight:600;text-transform:uppercase;}
h2{font-size:1.2rem;color:#1a365d;text-align:center;margin-bottom:0.3rem;font-weight:700;}
.sub{color:#64748b;font-size:0.85rem;text-align:center;margin-bottom:1.8rem;}
label{display:block;font-size:0.82rem;font-weight:600;color:#1a365d;margin-bottom:0.35rem;}
input{width:100%;padding:12px 15px;border:2px solid #e2e8f0;border-radius:10px;font-size:0.92rem;font-family:'Inter',sans-serif;outline:none;margin-bottom:1.1rem;transition:border-color .2s;}
input:focus{border-color:#FF6B35;box-shadow:0 0 0 3px rgba(255,107,53,.1);}
.btn{width:100%;padding:14px;background:linear-gradient(135deg,#FF6B35,#F7931E);color:#fff;border:none;border-radius:10px;font-size:1rem;font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;transition:all .3s;}
.btn:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(255,107,53,.35);}
.error{background:#fff5f5;border:1px solid #fc8181;color:#c53030;border-radius:8px;padding:.75rem 1rem;font-size:.85rem;margin-bottom:1rem;text-align:center;}
.success{background:#f0fff4;border:1px solid #68d391;color:#276749;border-radius:8px;padding:.75rem 1rem;font-size:.85rem;margin-bottom:1rem;text-align:center;}
.back{display:block;text-align:center;margin-top:1.2rem;font-size:.82rem;color:#64748b;text-decoration:none;}
.back:hover{color:#FF6B35;}
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="card">
    <div class="logo">
        <div class="logo-ring"><i class="fas fa-shield-halved"></i></div>
        <span>MAKGWATI</span>
        <small>Content Manager</small>
    </div>
    <h2>Admin Login</h2>
    <p class="sub">Sign in to manage your website content</p>

    <?php if ($error): ?>
        <div class="error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['setup'])): ?>
        <div class="success"><i class="fas fa-check-circle"></i> Account created! You can now log in.</div>
    <?php endif; ?>
    <?php if (isset($_GET['out'])): ?>
        <div class="success">You have been logged out.</div>
    <?php endif; ?>

    <form method="POST">
        <label>Username</label>
        <input type="text" name="username" autocomplete="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        <label>Password</label>
        <input type="password" name="password" autocomplete="current-password" required>
        <button type="submit" class="btn"><i class="fas fa-sign-in-alt"></i> &nbsp;Sign In</button>
    </form>
    <a href="../index.html" class="back"><i class="fas fa-arrow-left"></i> Back to Website</a>
</div>
</body>
</html>
