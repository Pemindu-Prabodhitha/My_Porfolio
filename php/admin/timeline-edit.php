<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo = get_db_connection();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isEdit = $id !== null;

$item = [
    'title' => '',
    'organization' => '',
    'date_range' => '',
    'description' => '',
    'icon_type' => 'work',
    'sort_order' => 0,
];

$errors = [];

if ($pdo === null) {
    $errors[] = 'Database is not connected. Check php/config.php.';
}

if ($isEdit && $pdo !== null) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM timeline_items WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $existing = $stmt->fetch();

        if ($existing) {
            $item = $existing;
        } else {
            $errors[] = 'Timeline entry not found.';
            $isEdit = false;
            $id = null;
        }
    } catch (PDOException $e) {
        $errors[] = 'Could not load timeline entry.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $item['title'] = trim($_POST['title'] ?? '');
        $item['organization'] = trim($_POST['organization'] ?? '');
        $item['date_range'] = trim($_POST['date_range'] ?? '');
        $item['description'] = trim($_POST['description'] ?? '');
        $item['icon_type'] = in_array($_POST['icon_type'] ?? '', ['work', 'education'], true) ? $_POST['icon_type'] : 'work';
        $item['sort_order'] = (int) ($_POST['sort_order'] ?? 0);

        if ($item['title'] === '' || strlen($item['title']) > 150) {
            $errors[] = 'Title is required (max 150 characters).';
        }
        if ($item['organization'] === '') {
            $errors[] = 'Organization is required.';
        }
        if ($item['date_range'] === '') {
            $errors[] = 'Date range is required (e.g. "2024 — Present").';
        }
        if ($item['description'] === '') {
            $errors[] = 'Description is required.';
        }

        if (empty($errors) && $pdo !== null) {
            try {
                if ($isEdit) {
                    $stmt = $pdo->prepare(
                        'UPDATE timeline_items SET title=:title, organization=:organization, date_range=:date_range,
                         description=:description, icon_type=:icon_type, sort_order=:sort_order WHERE id=:id'
                    );
                    $stmt->execute([
                        ':title' => $item['title'],
                        ':organization' => $item['organization'],
                        ':date_range' => $item['date_range'],
                        ':description' => $item['description'],
                        ':icon_type' => $item['icon_type'],
                        ':sort_order' => $item['sort_order'],
                        ':id' => $id,
                    ]);
                    header('Location: timeline.php?flash=updated');
                    exit;
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO timeline_items (title, organization, date_range, description, icon_type, sort_order)
                         VALUES (:title, :organization, :date_range, :description, :icon_type, :sort_order)'
                    );
                    $stmt->execute([
                        ':title' => $item['title'],
                        ':organization' => $item['organization'],
                        ':date_range' => $item['date_range'],
                        ':description' => $item['description'],
                        ':icon_type' => $item['icon_type'],
                        ':sort_order' => $item['sort_order'],
                    ]);
                    header('Location: timeline.php?flash=created');
                    exit;
                }
            } catch (PDOException $e) {
                $errors[] = 'Could not save timeline entry. Please try again.';
                error_log('Timeline save failed: ' . $e->getMessage());
            }
        }
    }
}

$pageTitle = $isEdit ? 'Edit Timeline Entry' : 'Add Timeline Entry';
$activeNav = 'timeline';
include __DIR__ . '/_header.php';
?>

<div class="admin-heading">
  <h1><?php echo $isEdit ? 'Edit Timeline Entry' : 'Add Timeline Entry'; ?></h1>
  <a href="timeline.php" class="btn btn-outline btn-sm">← Back to Timeline</a>
</div>

<?php foreach ($errors as $err): ?>
  <div class="flash error"><?php echo e($err); ?></div>
<?php endforeach; ?>

<form method="post" class="admin-form">
  <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

  <div class="form-group">
    <label for="title">Title</label>
    <input type="text" id="title" name="title" value="<?php echo e($item['title']); ?>" placeholder="e.g. Under Graduate" required>
  </div>

  <div class="form-group">
    <label for="organization">Organization</label>
    <input type="text" id="organization" name="organization" value="<?php echo e($item['organization']); ?>" placeholder="e.g. University of Kelaniya" required>
  </div>

  <div class="form-group">
    <label for="date_range">Date Range</label>
    <input type="text" id="date_range" name="date_range" value="<?php echo e($item['date_range']); ?>" placeholder="e.g. 2024 — Present" required>
  </div>

  <div class="form-group">
    <label for="description">Description</label>
    <textarea id="description" name="description" rows="4" required><?php echo e($item['description']); ?></textarea>
  </div>

  <div class="form-group">
    <label for="icon_type">Icon Type</label>
    <select id="icon_type" name="icon_type" style="width:100%;padding:0.75rem;border:1px solid var(--line);border-radius:var(--radius);background:var(--paper);font-family:var(--font-body);font-size:0.95rem;">
      <option value="education" <?php echo $item['icon_type'] === 'education' ? 'selected' : ''; ?>>Education (graduation cap icon)</option>
      <option value="work" <?php echo $item['icon_type'] === 'work' ? 'selected' : ''; ?>>Work (briefcase icon)</option>
    </select>
  </div>

  <div class="form-group">
    <label for="sort_order">Sort Order</label>
    <input type="number" id="sort_order" name="sort_order" value="<?php echo e($item['sort_order']); ?>">
    <p class="hint">Lower numbers appear first (most recent usually first).</p>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Save Changes' : 'Create Entry'; ?></button>
    <a href="timeline.php" class="btn btn-outline">Cancel</a>
  </div>
</form>

<?php include __DIR__ . '/_footer.php'; ?>
