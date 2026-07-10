<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo = get_db_connection();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if ($pdo === null) {
            $errors[] = 'Database is not connected.';
        } else {
            try {
                $stmt = $pdo->prepare('SELECT password_hash FROM admin_users WHERE id = :id');
                $stmt->execute([':id' => $_SESSION['admin_id']]);
                $row = $stmt->fetch();

                if (!$row || !password_verify($current, $row['password_hash'])) {
                    $errors[] = 'Current password is incorrect.';
                } elseif (strlen($new) < 8) {
                    $errors[] = 'New password must be at least 8 characters.';
                } elseif ($new !== $confirm) {
                    $errors[] = 'New password and confirmation do not match.';
                } else {
                    $newHash = password_hash($new, PASSWORD_DEFAULT);
                    $update = $pdo->prepare('UPDATE admin_users SET password_hash = :hash WHERE id = :id');
                    $update->execute([':hash' => $newHash, ':id' => $_SESSION['admin_id']]);
                    $success = true;
                }
            } catch (PDOException $e) {
                $errors[] = 'Could not update password. Please try again.';
                error_log('Password change failed: ' . $e->getMessage());
            }
        }
    }
}

$pageTitle = 'Change Password';
$activeNav = 'password';
include __DIR__ . '/_header.php';
?>

<div class="admin-heading">
  <h1>Change Password</h1>
</div>

<?php if ($success): ?>
  <div class="flash success">Password updated successfully.</div>
<?php endif; ?>

<?php foreach ($errors as $err): ?>
  <div class="flash error"><?php echo e($err); ?></div>
<?php endforeach; ?>

<form method="post" class="admin-form">
  <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

  <div class="form-group">
    <label for="current_password">Current Password</label>
    <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
  </div>

  <div class="form-group">
    <label for="new_password">New Password</label>
    <input type="password" id="new_password" name="new_password" required autocomplete="new-password">
    <p class="hint">At least 8 characters.</p>
  </div>

  <div class="form-group">
    <label for="confirm_password">Confirm New Password</label>
    <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Update Password</button>
  </div>
</form>

<?php include __DIR__ . '/_footer.php'; ?>
