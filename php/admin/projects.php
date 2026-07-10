<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo = get_db_connection();
$projects = [];
$dbError = false;

if ($pdo !== null) {
    try {
        $projects = $pdo->query('SELECT * FROM projects ORDER BY sort_order ASC, id ASC')->fetchAll();
    } catch (PDOException $e) {
        $dbError = true;
    }
} else {
    $dbError = true;
}

// Flash message passed via query string after redirect (see project-edit.php / project-delete.php)
$flash = $_GET['flash'] ?? '';

$pageTitle = 'Projects';
$activeNav = 'projects';
include __DIR__ . '/_header.php';
?>

<div class="admin-heading">
  <h1>Projects</h1>
  <a href="project-edit.php" class="btn btn-primary btn-sm">+ Add New Project</a>
</div>

<?php if ($flash === 'created'): ?>
  <div class="flash success">Project created.</div>
<?php elseif ($flash === 'updated'): ?>
  <div class="flash success">Project updated.</div>
<?php elseif ($flash === 'deleted'): ?>
  <div class="flash success">Project deleted.</div>
<?php endif; ?>

<?php if ($dbError): ?>
  <div class="flash error">
    Couldn't connect to the database. Check <code>php/config.php</code> and make sure
    <code>database/schema.sql</code> has been imported.
  </div>
<?php elseif (empty($projects)): ?>
  <p>No projects yet. <a href="project-edit.php">Add your first one</a>.</p>
<?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Order</th>
          <th>Title</th>
          <th>Tags</th>
          <th>Links</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($projects as $project): ?>
          <tr>
            <td><?php echo e($project['sort_order']); ?></td>
            <td><?php echo e($project['title']); ?></td>
            <td><?php echo e($project['tags']); ?></td>
            <td>
              <?php if (!empty($project['live_url'])): ?>Live &nbsp;<?php endif; ?>
              <?php if (!empty($project['github_url'])): ?>GitHub<?php endif; ?>
            </td>
            <td class="row-actions">
              <a href="project-edit.php?id=<?php echo (int) $project['id']; ?>">Edit</a>
              <form method="post" action="project-delete.php" style="display:inline;"
                    onsubmit="return confirm('Delete &quot;<?php echo e(addslashes($project['title'])); ?>&quot;? This cannot be undone.');">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="id" value="<?php echo (int) $project['id']; ?>">
                <button type="submit" class="delete-link" style="background:none;border:none;padding:0;font:inherit;cursor:pointer;">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/_footer.php'; ?>
