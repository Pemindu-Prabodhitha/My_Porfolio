<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo = get_db_connection();
$dbError = ($pdo === null);

// Handle mark-as-read / delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo !== null) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        header('Location: messages.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    try {
        if ($action === 'mark_read' && $id > 0) {
            $stmt = $pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = :id');
            $stmt->execute([':id' => $id]);
        } elseif ($action === 'delete' && $id > 0) {
            $stmt = $pdo->prepare('DELETE FROM contact_messages WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }
    } catch (PDOException $e) {
        error_log('Message action failed: ' . $e->getMessage());
    }

    header('Location: messages.php');
    exit;
}

$messages = [];
if ($pdo !== null) {
    try {
        $messages = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();
    } catch (PDOException $e) {
        $dbError = true;
    }
}

$pageTitle = 'Messages';
$activeNav = 'messages';
include __DIR__ . '/_header.php';
?>

<div class="admin-heading">
  <h1>Contact Messages</h1>
</div>

<?php if ($dbError): ?>
  <div class="flash error">
    Couldn't connect to the database. Check <code>php/config.php</code> and make sure
    <code>database/schema.sql</code> has been imported.
  </div>
<?php elseif (empty($messages)): ?>
  <p>No messages yet.</p>
<?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>From</th>
          <th>Message</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($messages as $msg): ?>
          <tr>
            <td><?php echo e($msg['created_at']); ?></td>
            <td>
              <?php echo e($msg['name']); ?><br>
              <span style="color:var(--ink-soft);font-size:0.82rem;"><?php echo e($msg['email']); ?></span>
              <?php if (!$msg['is_read']): ?><span class="unread-badge">NEW</span><?php endif; ?>
            </td>
            <td class="message-preview" title="<?php echo e($msg['message']); ?>">
              <?php echo e($msg['message']); ?>
            </td>
            <td class="row-actions">
              <?php if (!$msg['is_read']): ?>
                <form method="post" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                  <input type="hidden" name="action" value="mark_read">
                  <input type="hidden" name="id" value="<?php echo (int) $msg['id']; ?>">
                  <button type="submit" style="background:none;border:none;padding:0;font:inherit;cursor:pointer;color:var(--blueprint);">Mark Read</button>
                </form>
              <?php endif; ?>
              <form method="post" style="display:inline;" onsubmit="return confirm('Delete this message?');">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int) $msg['id']; ?>">
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
