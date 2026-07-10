<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo = get_db_connection();
$skills = [];
$dbError = false;

if ($pdo !== null) {
    try {
        $skills = $pdo->query('SELECT * FROM skills ORDER BY category_order ASC, category ASC, sort_order ASC, id ASC')->fetchAll();
    } catch (PDOException $e) {
        $dbError = true;
    }
} else {
    $dbError = true;
}

$flash = $_GET['flash'] ?? '';

$pageTitle = 'Skills';
$activeNav = 'skills';
include __DIR__ . '/_header.php';
?>

<div class="admin-heading">
  <h1>Skills</h1>
  <a href="skills-edit.php" class="btn btn-primary btn-sm">+ Add Skill</a>
</div>

<?php if ($flash === 'created'): ?>
  <div class="flash success">Skill added.</div>
<?php elseif ($flash === 'updated'): ?>
  <div class="flash success">Skill updated.</div>
<?php elseif ($flash === 'deleted'): ?>
  <div class="flash success">Skill deleted.</div>
<?php endif; ?>

<?php if ($dbError): ?>
  <div class="flash error">
    Couldn't connect to the database. Check <code>php/config.php</code> and make sure
    <code>database/schema.sql</code> has been imported.
  </div>
<?php elseif (empty($skills)): ?>
  <p>No skills yet. <a href="skills-edit.php">Add your first one</a>.</p>
<?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Category</th>
          <th>Skill</th>
          <th>Order</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($skills as $skill): ?>
          <tr>
            <td><?php echo e($skill['category']); ?></td>
            <td><?php echo e($skill['skill_name']); ?></td>
            <td><?php echo e($skill['category_order']); ?> / <?php echo e($skill['sort_order']); ?></td>
            <td class="row-actions">
              <a href="skills-edit.php?id=<?php echo (int) $skill['id']; ?>">Edit</a>
              <form method="post" action="skills-delete.php" style="display:inline;"
                    onsubmit="return confirm('Delete &quot;<?php echo e(addslashes($skill['skill_name'])); ?>&quot;?');">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="id" value="<?php echo (int) $skill['id']; ?>">
                <button type="submit" class="delete-link" style="background:none;border:none;padding:0;font:inherit;cursor:pointer;">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="hint" style="margin-top:1rem;">"Order" shows Category Order / Skill Order — lower numbers appear first. Give skills in the same category matching Category Order numbers to group them together.</p>
<?php endif; ?>

<?php include __DIR__ . '/_footer.php'; ?>
