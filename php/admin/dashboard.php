<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo = get_db_connection();
$projectCount = 0;
$timelineCount = 0;
$skillsCount = 0;
$messageCount = 0;
$unreadCount = 0;
$dbError = false;
$darkMode = false;

if ($pdo !== null) {
    try {
        $projectCount = (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
        $timelineCount = (int) $pdo->query('SELECT COUNT(*) FROM timeline_items')->fetchColumn();
        $skillsCount = (int) $pdo->query('SELECT COUNT(*) FROM skills')->fetchColumn();
        $messageCount = (int) $pdo->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn();
        $unreadCount = (int) $pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();

        $stmt = $pdo->prepare('SELECT content_value FROM site_content WHERE content_key = :k');
        $stmt->execute([':k' => 'dark_mode']);
        $darkMode = $stmt->fetchColumn() === '1';
    } catch (PDOException $e) {
        $dbError = true;
    }
} else {
    $dbError = true;
}

// Toggle dark mode for the live site
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_dark_mode']) && $pdo !== null) {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $newValue = $darkMode ? '0' : '1';
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO site_content (content_key, content_value) VALUES (:k, :v)
                 ON DUPLICATE KEY UPDATE content_value = :v2'
            );
            $stmt->execute([':k' => 'dark_mode', ':v' => $newValue, ':v2' => $newValue]);
            header('Location: dashboard.php');
            exit;
        } catch (PDOException $e) {
            error_log('Dark mode toggle failed: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
include __DIR__ . '/_header.php';
?>

<div class="admin-heading">
  <h1>Welcome back, <?php echo e($_SESSION['admin_username'] ?? 'Admin'); ?></h1>
</div>

<?php if ($dbError): ?>
  <div class="flash error">
    Couldn't connect to the database. Check <code>php/config.php</code> and make sure
    <code>database/schema.sql</code> has been imported.
  </div>
<?php else: ?>
  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-label">Total Projects</div>
      <div class="stat-value"><?php echo $projectCount; ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Timeline Entries</div>
      <div class="stat-value"><?php echo $timelineCount; ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Skills</div>
      <div class="stat-value"><?php echo $skillsCount; ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Contact Messages</div>
      <div class="stat-value"><?php echo $messageCount; ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Unread Messages</div>
      <div class="stat-value"><?php echo $unreadCount; ?></div>
    </div>
  </div>

  <div class="stat-card" style="max-width:420px;margin-bottom:1.5rem;">
    <div class="stat-label">Site Appearance</div>
    <p style="margin:0.5rem 0 1rem;color:var(--ink-soft, #4a5259);font-size:0.9rem;">
      Live site is currently in <strong><?php echo $darkMode ? 'Dark' : 'Light'; ?> Mode</strong>.
    </p>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
      <input type="hidden" name="toggle_dark_mode" value="1">
      <button type="submit" class="btn <?php echo $darkMode ? 'btn-outline' : 'btn-primary'; ?> btn-sm">
        <?php echo $darkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'; ?>
      </button>
    </form>
  </div>

  <p style="margin-bottom:1rem;">
    <a href="content.php" class="btn btn-primary btn-sm">Edit Site Content</a>
    &nbsp;
    <a href="timeline.php" class="btn btn-outline btn-sm">Manage Timeline</a>
    &nbsp;
    <a href="skills.php" class="btn btn-outline btn-sm">Manage Skills</a>
    &nbsp;
    <a href="projects.php" class="btn btn-outline btn-sm">Manage Projects</a>
    &nbsp;
    <a href="messages.php" class="btn btn-outline btn-sm">View Messages</a>
  </p>
<?php endif; ?>

<?php include __DIR__ . '/_footer.php'; ?>
