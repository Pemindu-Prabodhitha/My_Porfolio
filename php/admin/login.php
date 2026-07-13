<?php
require_once __DIR__ . '/auth.php';

// If already logged in, skip straight to the dashboard
if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        $adminId = verify_admin_login($username, $password);

        if ($adminId !== false) {
            // Regenerate session ID on login to prevent session fixation
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $adminId;
            $_SESSION['admin_username'] = $username;
            header('Location: dashboard.php');
            exit;
        }

        $error = 'Incorrect username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../css/style.css">
<link rel="stylesheet" href="../../css/admin.css">
</head>
<body class="admin-body">

<div class="login-wrap">
  <div class="login-card bracket">
    <span class="eyebrow">Admin Access</span>
    <h1>Log in to manage your portfolio</h1>

    <?php if ($error): ?>
      <div class="flash error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="post" class="admin-form" style="border:none;padding:0;">
      <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus autocomplete="username">
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Log In</button>
      </div>
    </form>
  </div>
</div>

</body>
</html>
