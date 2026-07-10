<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo = get_db_connection();
$items = [];
$dbError = false;

if ($pdo !== null) {
    try {
        $items = $pdo->query('SELECT * FROM timeline_items ORDER BY sort_order ASC, id ASC')->fetchAll();
    } catch (PDOException $e) {
        $dbError = true;
    }
} else {
    $dbError = true;
}

$flash = $_GET['flash'] ?? '';

$pageTitle = 'Timeline';
$activeNav = 'timeline';
include __DIR__ . '/_header.php';
?>

<div class="admin-heading">
  <h1>Timeline</h1>
  <a href="timeline-edit.php" class="btn btn-primary btn-sm">+ Add Timeline Entry</a>
</div>

<?php if ($flash === 'created'): ?>
  <div class="flash success">Timeline entry created.</div>
<?php elseif ($flash === 'updated'): ?>
  <div class="flash success">Timeline entry updated.</div>
<?php elseif ($flash === 'deleted'): ?>
  <div class="flash success">Timeline entry deleted.</div>
<?php endif; ?>

<?php if ($dbError): ?>
  <div class="flash error">
    Couldn't connect to the database. Check <code>php/config.php</code> and make sure
    <code>database/schema.sql</code> has been imported.
  </div>
<?php elseif (empty($items)): ?>
  <p>No timeline entries yet. <a href="timeline-edit.php">Add your first one</a>.</p>
<?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Order</th>
          <th>Title</th>
          <th>Organization</th>
          <th>Date Range</th>
          <th>Type</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <tr>
            <td><?php echo e($item['sort_order']); ?></td>
            <td><?php echo e($item['title']); ?></td>
            <td><?php echo e($item['organization']); ?></td>
            <td><?php echo e($item['date_range']); ?></td>
            <td><?php echo e(ucfirst($item['icon_type'])); ?></td>
            <td class="row-actions">
              <a href="timeline-edit.php?id=<?php echo (int) $item['id']; ?>">Edit</a>
              <form method="post" action="timeline-delete.php" style="display:inline;"
                    onsubmit="return confirm('Delete &quot;<?php echo e(addslashes($item['title'])); ?>&quot;? This cannot be undone.');">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
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
