<?php
require_once __DIR__ . '/auth.php';

if (is_setup_done()) {
    header('Location: login.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if (!$username || !$password || !$confirm) {
        $error = 'All fields are required.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $data = ['username' => htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
                 'hash'     => password_hash($password, PASSWORD_BCRYPT)];
        file_put_contents(AUTH_FILE, json_encode($data));
        header('Location: login.php?setup=1'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup Admin — Makgwati Security CMS</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:100vh;}
.card{background:#fff;border-radius:16px;padding:2.5rem;width:100%;max-width:420px;box-shadow:0 10px 40px rgba(0,0,0,0.1);}
.logo{text-align:center;margin-bottom:2rem;}
.logo span{display:block;font-size:1.3rem;font-weight:800;color:#1a365d;letter-spacing:2px;}
.logo small{font-size:0.75rem;color:#FF6B35;letter-spacing:2px;font-weight:600;}
h2{font-size:1.3rem;color:#1a365d;margin-bottom:0.4rem;font-weight:700;}
p.sub{color:#64748b;font-size:0.88rem;margin-bottom:1.6rem;}
label{display:block;font-size:0.82rem;font-weight:600;color:#1a365d;margin-bottom:0.3rem;}
input{width:100%;padding:11px 14px;border:2px solid #e2e8f0;border-radius:8px;font-size:0.9rem;font-family:'Inter',sans-serif;outline:none;margin-bottom:1rem;transition:border-color .2s;}
input:focus{border-color:#FF6B35;}
.btn{width:100%;padding:13px;background:linear-gradient(135deg,#FF6B35,#F7931E);color:#fff;border:none;border-radius:8px;font-size:0.95rem;font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;transition:all .3s;}
.btn:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(255,107,53,.3);}
.error{background:#fff5f5;border:1px solid #fc8181;color:#c53030;border-radius:8px;padding:0.8rem 1rem;font-size:0.85rem;margin-bottom:1rem;}
</style>
</head>
<body>
<div class="card">
    <div class="logo">
        <span>MAKGWATI</span>
        <small>SECURITY · CMS SETUP</small>
    </div>
    <h2>Create Admin Account</h2>
    <p class="sub">This runs once. Set your login credentials to manage the website content.</p>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
        <label>Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autocomplete="username" required>
        <label>Password <small style="color:#64748b;font-weight:400">(min 8 characters)</small></label>
        <input type="password" name="password" autocomplete="new-password" required>
        <label>Confirm Password</label>
        <input type="password" name="confirm" autocomplete="new-password" required>
        <button type="submit" class="btn">Create Account &amp; Go to Login</button>
    </form>
</div>
</body>
</html>
