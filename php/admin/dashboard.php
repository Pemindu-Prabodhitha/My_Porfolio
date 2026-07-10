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

if ($pdo !== null) {
    try {
        $projectCount = (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
        $timelineCount = (int) $pdo->query('SELECT COUNT(*) FROM timeline_items')->fetchColumn();
        $skillsCount = (int) $pdo->query('SELECT COUNT(*) FROM skills')->fetchColumn();
        $messageCount = (int) $pdo->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn();
        $unreadCount = (int) $pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
    } catch (PDOException $e) {
        $dbError = true;
    }
} else {
    $dbError = true;
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
