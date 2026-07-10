<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo = get_db_connection();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isEdit = $id !== null;

$skill = [
    'category' => '',
    'skill_name' => '',
    'category_order' => 0,
    'sort_order' => 0,
];

$errors = [];
$existingCategories = [];

if ($pdo === null) {
    $errors[] = 'Database is not connected. Check php/config.php.';
} else {
    try {
        $catRows = $pdo->query('SELECT DISTINCT category, category_order FROM skills ORDER BY category_order ASC')->fetchAll();
        $existingCategories = $catRows;
    } catch (PDOException $e) {
        // non-fatal, just means the datalist suggestion list is empty
    }
}

if ($isEdit && $pdo !== null) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM skills WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $existing = $stmt->fetch();

        if ($existing) {
            $skill = $existing;
        } else {
            $errors[] = 'Skill not found.';
            $isEdit = false;
            $id = null;
        }
    } catch (PDOException $e) {
        $errors[] = 'Could not load skill.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $skill['category'] = trim($_POST['category'] ?? '');
        $skill['skill_name'] = trim($_POST['skill_name'] ?? '');
        $skill['category_order'] = (int) ($_POST['category_order'] ?? 0);
        $skill['sort_order'] = (int) ($_POST['sort_order'] ?? 0);

        if ($skill['category'] === '' || strlen($skill['category']) > 60) {
            $errors[] = 'Category is required (max 60 characters).';
        }
        if ($skill['skill_name'] === '' || strlen($skill['skill_name']) > 100) {
            $errors[] = 'Skill name is required (max 100 characters).';
        }

        if (empty($errors) && $pdo !== null) {
            try {
                if ($isEdit) {
                    $stmt = $pdo->prepare(
                        'UPDATE skills SET category=:category, skill_name=:skill_name,
                         category_order=:category_order, sort_order=:sort_order WHERE id=:id'
                    );
                    $stmt->execute([
                        ':category' => $skill['category'],
                        ':skill_name' => $skill['skill_name'],
                        ':category_order' => $skill['category_order'],
                        ':sort_order' => $skill['sort_order'],
                        ':id' => $id,
                    ]);
                    header('Location: skills.php?flash=updated');
                    exit;
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO skills (category, skill_name, category_order, sort_order)
                         VALUES (:category, :skill_name, :category_order, :sort_order)'
                    );
                    $stmt->execute([
                        ':category' => $skill['category'],
                        ':skill_name' => $skill['skill_name'],
                        ':category_order' => $skill['category_order'],
                        ':sort_order' => $skill['sort_order'],
                    ]);
                    header('Location: skills.php?flash=created');
                    exit;
                }
            } catch (PDOException $e) {
                $errors[] = 'Could not save skill. Please try again.';
                error_log('Skill save failed: ' . $e->getMessage());
            }
        }
    }
}

$pageTitle = $isEdit ? 'Edit Skill' : 'Add Skill';
$activeNav = 'skills';
include __DIR__ . '/_header.php';
?>

<div class="admin-heading">
  <h1><?php echo $isEdit ? 'Edit Skill' : 'Add Skill'; ?></h1>
  <a href="skills.php" class="btn btn-outline btn-sm">← Back to Skills</a>
</div>

<?php foreach ($errors as $err): ?>
  <div class="flash error"><?php echo e($err); ?></div>
<?php endforeach; ?>

<form method="post" class="admin-form">
  <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

  <div class="form-group">
    <label for="category">Category</label>
    <input type="text" id="category" name="category" value="<?php echo e($skill['category']); ?>" list="categoryList" placeholder="e.g. Frontend" required>
    <datalist id="categoryList">
      <?php foreach ($existingCategories as $cat): ?>
        <option value="<?php echo e($cat['category']); ?>">
      <?php endforeach; ?>
    </datalist>
    <p class="hint">Use an existing category name exactly to group this skill with others in it.</p>
  </div>

  <div class="form-group">
    <label for="skill_name">Skill Name</label>
    <input type="text" id="skill_name" name="skill_name" value="<?php echo e($skill['skill_name']); ?>" placeholder="e.g. JavaScript" required>
  </div>

  <div class="form-group">
    <label for="category_order">Category Order</label>
    <input type="number" id="category_order" name="category_order" value="<?php echo e($skill['category_order']); ?>">
    <p class="hint">Controls which order categories appear in. Give all skills in the same category the same number.</p>
  </div>

  <div class="form-group">
    <label for="sort_order">Skill Order (within category)</label>
    <input type="number" id="sort_order" name="sort_order" value="<?php echo e($skill['sort_order']); ?>">
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Save Changes' : 'Add Skill'; ?></button>
    <a href="skills.php" class="btn btn-outline">Cancel</a>
  </div>
</form>

<?php include __DIR__ . '/_footer.php'; ?>
